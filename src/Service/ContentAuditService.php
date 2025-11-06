<?php

declare(strict_types=1);

namespace AIContentAuditBundle\Service;

use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Entity\RiskKeyword;
use AIContentAuditBundle\Entity\ViolationRecord;
use AIContentAuditBundle\Enum\AuditResult;
use AIContentAuditBundle\Enum\RiskLevel;
use AIContentAuditBundle\Enum\ViolationType;
use AIContentAuditBundle\Repository\GeneratedContentRepository;
use AIContentAuditBundle\Repository\RiskKeywordRepository;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;

/**
 * 内容审核服务类
 */
#[WithMonologChannel(channel: 'ai_content_audit')]
readonly class ContentAuditService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GeneratedContentRepository $generatedContentRepository,
        private RiskKeywordRepository $riskKeywordRepository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * 机器审核内容
     *
     * @param string     $inputText  用户输入文本
     * @param string     $outputText AI输出文本
     * @param int|string $userId     用户ID
     *
     * @return GeneratedContent 审核后的内容实体
     */
    public function machineAudit(string $inputText, string $outputText, mixed $userId): GeneratedContent
    {
        $this->logger->info('开始机器审核内容', [
            'userId' => $userId,
            'inputLength' => strlen($inputText),
            'outputLength' => strlen($outputText),
        ]);

        // 创建生成内容记录
        $content = new GeneratedContent();
        $content->setUser($userId);
        $content->setInputText($inputText);
        $content->setOutputText($outputText);
        $content->setMachineAuditTime(new \DateTimeImmutable());

        // 审核用户输入和AI输出
        $inputRiskLevel = $this->evaluateRiskLevel($inputText);
        $outputRiskLevel = $this->evaluateRiskLevel($outputText);

        // 取两者中较高的风险等级
        $riskLevel = RiskLevel::getHigher($inputRiskLevel, $outputRiskLevel);
        $content->setMachineAuditResult($riskLevel);

        // 保存审核结果
        $this->entityManager->persist($content);
        $this->entityManager->flush();

        $this->logger->info('完成机器审核内容', [
            'contentId' => $content->getId(),
            'riskLevel' => $riskLevel->value,
        ]);

        // 处理高风险内容
        if (RiskLevel::HIGH_RISK === $riskLevel) {
            $this->handleHighRiskContent($content, $userId);
        }

        return $content;
    }

    /**
     * 查找生成内容
     */
    public function findGeneratedContent(int $id): ?GeneratedContent
    {
        return $this->generatedContentRepository->find($id);
    }

    /**
     * 人工审核内容
     *
     * @param GeneratedContent $content     内容
     * @param AuditResult      $auditResult 审核结果（通过、修改、删除等）
     * @param string           $operator    操作人员
     *
     * @return GeneratedContent 审核后的内容实体
     */
    public function manualAudit(GeneratedContent $content, AuditResult $auditResult, string $operator): GeneratedContent
    {
        $this->logger->info('开始人工审核内容', [
            'contentId' => $content->getId(),
            'auditResult' => $auditResult->value,
            'operator' => $operator,
        ]);

        // 更新内容的人工审核结果
        $content->setManualAuditResult($auditResult);
        $content->setManualAuditTime(new \DateTimeImmutable());

        // 处理不同的审核结果
        switch ($auditResult) {
            case AuditResult::PASS:
                // 无需额外处理
                break;
            case AuditResult::MODIFY:
                // 可以在这里实现修改内容的逻辑
                break;
            case AuditResult::DELETE:
                // 记录违规记录，但不会真正删除数据库中的内容，只是标记为删除
                $this->createViolationRecord($content, $operator);
                break;
        }

        // 保存审核结果
        $this->entityManager->flush();

        $this->logger->info('完成人工审核内容', [
            'contentId' => $content->getId(),
            'auditResult' => $auditResult->value,
        ]);

        return $content;
    }

    /**
     * 处理高风险内容
     *
     * @param GeneratedContent $content 内容
     * @param int|string       $userId  用户ID
     */
    private function handleHighRiskContent(GeneratedContent $content, mixed $userId): void
    {
        $this->logger->warning('处理高风险内容', [
            'contentId' => $content->getId(),
            'userId' => $userId,
        ]);

        // 创建违规记录
        $violationRecord = new ViolationRecord();
        $violationRecord->setUser($userId);
        $violationRecord->setViolationContent($content->getInputText() . "\n" . $content->getOutputText());
        $violationRecord->setViolationType(ViolationType::MACHINE_HIGH_RISK);
        $violationRecord->setProcessResult('系统自动禁用账号');
        $violationRecord->setProcessedBy('系统');

        $this->entityManager->persist($violationRecord);
        $this->entityManager->flush();

        $this->logger->info('高风险内容处理完成', [
            'violationId' => $violationRecord->getId(),
        ]);
    }

    /**
     * 创建违规记录
     *
     * @param GeneratedContent $content  内容
     * @param string           $operator 操作人员
     *
     * @return ViolationRecord 违规记录实体
     */
    private function createViolationRecord(GeneratedContent $content, string $operator): ViolationRecord
    {
        $user = $content->getUser();

        // 创建违规记录
        $violationRecord = new ViolationRecord();
        $violationRecord->setUser($user);
        $violationRecord->setViolationContent($content->getInputText() . "\n" . $content->getOutputText());
        $violationRecord->setViolationType(ViolationType::MANUAL_DELETE);
        $violationRecord->setProcessResult('内容删除');
        $violationRecord->setProcessedBy($operator);

        $this->entityManager->persist($violationRecord);
        $this->entityManager->flush();

        return $violationRecord;
    }

    /**
     * 评估文本的风险等级
     *
     * @param string $text 文本内容
     *
     * @return RiskLevel 风险等级
     */
    private function evaluateRiskLevel(string $text): RiskLevel
    {
        // 获取各风险等级的关键词
        /** @var array<int, RiskKeyword> $lowRiskKeywords */
        $lowRiskKeywords = $this->riskKeywordRepository->findByRiskLevel(RiskLevel::LOW_RISK);
        /** @var array<int, RiskKeyword> $mediumRiskKeywords */
        $mediumRiskKeywords = $this->riskKeywordRepository->findByRiskLevel(RiskLevel::MEDIUM_RISK);
        /** @var array<int, RiskKeyword> $highRiskKeywords */
        $highRiskKeywords = $this->riskKeywordRepository->findByRiskLevel(RiskLevel::HIGH_RISK);

        // 计算匹配的关键词数量
        $lowRiskMatches = $this->countKeywordMatches($text, $lowRiskKeywords);
        $mediumRiskMatches = $this->countKeywordMatches($text, $mediumRiskKeywords);
        $highRiskMatches = $this->countKeywordMatches($text, $highRiskKeywords);

        // 根据匹配数量判断风险等级
        if ($highRiskMatches > 0) {
            return RiskLevel::HIGH_RISK;
        }
        if ($mediumRiskMatches > 0) {
            return RiskLevel::MEDIUM_RISK;
        }
        if ($lowRiskMatches > 0) {
            return RiskLevel::LOW_RISK;
        }

        return RiskLevel::NO_RISK;
    }

    /**
     * 统计文本中匹配的关键词数量
     *
     * @param string                                             $text     文本内容
     * @param array<int, RiskKeyword> $keywords 关键词列表
     *
     * @return int 匹配数量
     */
    private function countKeywordMatches(string $text, array $keywords): int
    {
        $count = 0;
        foreach ($keywords as $keyword) {
            $keywordText = $keyword->getKeyword();
            if (null !== $keywordText && false !== strpos($text, $keywordText)) {
                ++$count;
            }
        }

        return $count;
    }
}
