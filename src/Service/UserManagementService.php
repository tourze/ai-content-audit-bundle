<?php

declare(strict_types=1);

namespace AIContentAuditBundle\Service;

use AIContentAuditBundle\Entity\ViolationRecord;
use AIContentAuditBundle\Enum\ViolationType;
use AIContentAuditBundle\Repository\ViolationRecordRepository;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;

/**
 * 用户管理服务类
 */
#[WithMonologChannel(channel: 'ai_content_audit')]
readonly class UserManagementService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ViolationRecordRepository $violationRecordRepository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * 禁用用户账号
     *
     * @param int|string $userId   用户ID
     * @param string     $reason   禁用原因
     * @param string     $operator 操作人员
     */
    public function disableUser(mixed $userId, string $reason, string $operator): void
    {
        $this->logger->warning('禁用用户账号', [
            'userId' => $userId,
            'reason' => $reason,
            'operator' => $operator,
        ]);

        // 创建违规记录
        $violationRecord = new ViolationRecord();
        $violationRecord->setUser($userId);
        $violationRecord->setViolationContent($reason);
        $violationRecord->setViolationType(ViolationType::REPEATED_VIOLATION);
        $violationRecord->setProcessResult('账号已禁用');
        $violationRecord->setProcessedBy($operator);

        $this->entityManager->persist($violationRecord);
        $this->entityManager->flush();

        $this->logger->info('用户账号已禁用', [
            'userId' => $userId,
            'violationId' => $violationRecord->getId(),
        ]);
    }

    /**
     * 解除用户账号禁用
     *
     * @param int|string $userId   用户ID
     * @param string     $reason   解禁原因
     * @param string     $operator 操作人员
     */
    public function enableUser(mixed $userId, string $reason, string $operator): void
    {
        $this->logger->info('解除用户账号禁用', [
            'userId' => $userId,
            'reason' => $reason,
            'operator' => $operator,
        ]);

        // 创建解禁记录
        $violationRecord = new ViolationRecord();
        $violationRecord->setUser($userId);
        $violationRecord->setViolationContent('账号解禁: ' . $reason);
        $violationRecord->setViolationType(ViolationType::USER_REPORT);
        $violationRecord->setProcessResult('账号已恢复正常');
        $violationRecord->setProcessedBy($operator);

        $this->entityManager->persist($violationRecord);
        $this->entityManager->flush();

        $this->logger->info('用户账号已解禁', [
            'userId' => $userId,
            'violationId' => $violationRecord->getId(),
        ]);
    }

    /**
     * 复核用户申诉
     *
     * @param int|string $userId        用户ID
     * @param string     $appealContent 申诉内容
     * @param bool       $approved      是否通过
     * @param string     $result        处理结果
     * @param string     $operator      操作人员
     */
    public function reviewAppeal(mixed $userId, string $appealContent, bool $approved, string $result, string $operator): void
    {
        $this->logger->info('开始复核用户申诉', [
            'userId' => $userId,
            'approved' => $approved,
            'operator' => $operator,
        ]);

        // 创建申诉记录
        $violationRecord = new ViolationRecord();
        $violationRecord->setUser($userId);
        $violationRecord->setViolationContent('用户申诉: ' . $appealContent);
        $violationRecord->setViolationType(ViolationType::MANUAL_DELETE);
        $violationRecord->setProcessResult($result);
        $violationRecord->setProcessedBy($operator);

        $this->entityManager->persist($violationRecord);

        $this->entityManager->flush();
    }

    /**
     * 获取用户的违规记录
     *
     * @param int|string $userId 用户ID
     *
     * @return array<int, ViolationRecord> 违规记录列表
     */
    public function getUserViolationRecords(mixed $userId): array
    {
        return $this->violationRecordRepository->findByUser($userId);
    }
}
