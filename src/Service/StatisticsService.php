<?php

declare(strict_types=1);

namespace AIContentAuditBundle\Service;

use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Enum\AuditResult;
use AIContentAuditBundle\Repository\GeneratedContentRepository;
use AIContentAuditBundle\Repository\RiskKeywordRepository;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;

/**
 * 数据统计服务类
 */
#[WithMonologChannel(channel: 'ai_content_audit')]
readonly class StatisticsService
{
    public function __construct(
        private GeneratedContentRepository $generatedContentRepository,
        private RiskKeywordRepository $riskKeywordRepository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * 获取审核效率统计
     *
     * @return array<string, mixed> 审核效率数据
     */
    public function getAuditEfficiencyStatistics(): array
    {
        $this->logger->info('获取审核效率统计数据');

        $sevenDaysAgo = new \DateTimeImmutable('-7 days');
        $auditedContents = $this->fetchAuditedContents($sevenDaysAgo);
        $avgAuditTime = $this->calculateAverageAuditTime($auditedContents);
        $resultCounts = $this->fetchAuditResultCounts($sevenDaysAgo);

        return [
            'auditCount' => count($auditedContents),
            'avgAuditTimeSeconds' => $avgAuditTime,
            'auditResults' => $resultCounts,
        ];
    }

    /**
     * @return array<int, GeneratedContent>
     */
    private function fetchAuditedContents(\DateTimeImmutable $timeLimit): array
    {
        /** @var array<int, GeneratedContent> */
        return $this->generatedContentRepository->createQueryBuilder('g')
            ->andWhere('g.manualAuditResult IS NOT NULL')
            ->andWhere('g.manualAuditTime >= :timeLimit')
            ->setParameter('timeLimit', $timeLimit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param array<int, GeneratedContent> $auditedContents
     */
    private function calculateAverageAuditTime(array $auditedContents): float
    {
        $auditCount = count($auditedContents);
        if (0 === $auditCount) {
            return 0;
        }

        $totalAuditTime = 0;
        foreach ($auditedContents as $content) {
            $auditTime = $this->getContentAuditTime($content);
            if (null !== $auditTime) {
                $totalAuditTime += $auditTime;
            }
        }

        return $totalAuditTime / $auditCount;
    }

    private function getContentAuditTime(GeneratedContent $content): ?int
    {
        $machineTime = $content->getMachineAuditTime();
        $manualTime = $content->getManualAuditTime();

        if (null === $machineTime || null === $manualTime) {
            return null;
        }

        return $manualTime->getTimestamp() - $machineTime->getTimestamp();
    }

    /**
     * @return array<string, int>
     */
    private function fetchAuditResultCounts(\DateTimeImmutable $timeLimit): array
    {
        /** @var array<int, array{result: AuditResult, count: int}> $auditResults */
        $auditResults = $this->generatedContentRepository->createQueryBuilder('g')
            ->select('g.manualAuditResult as result, COUNT(g.id) as count')
            ->andWhere('g.manualAuditResult IS NOT NULL')
            ->andWhere('g.manualAuditTime >= :timeLimit')
            ->setParameter('timeLimit', $timeLimit)
            ->groupBy('g.manualAuditResult')
            ->getQuery()
            ->getResult()
        ;

        $resultCounts = [];
        foreach ($auditResults as $result) {
            if (isset($result['result'], $result['count']) && $result['result'] instanceof AuditResult) {
                $resultCounts[$result['result']->value] = (int) $result['count'];
            }
        }

        return $resultCounts;
    }

    /**
     * 获取风险关键词统计
     *
     * @return array<string, mixed> 关键词统计数据
     */
    public function getKeywordStatistics(): array
    {
        $this->logger->info('获取风险关键词统计数据');

        // 按风险等级统计关键词数量
        $keywordByRiskLevel = $this->riskKeywordRepository->countByRiskLevel();

        // 按分类统计关键词数量
        $keywordByCategory = $this->riskKeywordRepository->countByCategory();

        // 获取最近更新的10个关键词
        $recentKeywords = $this->riskKeywordRepository->findRecentUpdated(10);

        return [
            'keywordByRiskLevel' => $keywordByRiskLevel,
            'keywordByCategory' => $keywordByCategory,
            'recentKeywords' => $recentKeywords,
        ];
    }
}
