<?php

namespace AIContentAuditBundle\Service;

use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Entity\Report;
use AIContentAuditBundle\Enum\ProcessStatus;
use AIContentAuditBundle\Repository\ReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * 举报服务类
 */
class ReportService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ReportRepository $reportRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 提交举报
     *
     * @param GeneratedContent $content 被举报内容
     * @param int|string $reporterUserId 举报用户ID
     * @param string $reportReason 举报理由
     * @return Report 创建的举报实体
     */
    public function submitReport(
        GeneratedContent $content, 
        int|string $reporterUserId, 
        string $reportReason
    ): Report {
        $this->logger->info('用户提交举报', [
            'reporterUserId' => $reporterUserId,
            'contentId' => $content->getId(),
        ]);

        // 创建举报记录
        $report = new Report();
        $report->setReportedContent($content);
        $report->setReporterUser($reporterUserId);
        $report->setReportReason($reportReason);
        $report->setProcessStatus(ProcessStatus::PENDING);

        $this->entityManager->persist($report);
        $this->entityManager->flush();

        $this->logger->info('举报提交成功', [
            'reportId' => $report->getId(),
        ]);

        return $report;
    }

    /**
     * 处理举报
     *
     * @param Report $report 举报实体
     * @param string $processResult 处理结果
     * @param string $operator 操作人员
     * @return Report 处理后的举报实体
     */
    public function processReport(Report $report, string $processResult, string $operator): Report
    {
        $this->logger->info('开始处理举报', [
            'reportId' => $report->getId(),
            'operator' => $operator,
        ]);

        // 更新举报状态
        $report->setProcessStatus(ProcessStatus::COMPLETED);
        $report->setProcessResult($processResult);
        $report->setProcessTime(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->logger->info('举报处理完成', [
            'reportId' => $report->getId(),
            'processResult' => $processResult,
        ]);

        return $report;
    }

    /**
     * 开始处理举报（设置为审核中状态）
     *
     * @param Report $report 举报实体
     * @param string $operator 操作人员
     * @return Report 处理后的举报实体
     */
    public function startProcessing(Report $report, string $operator): Report
    {
        if ($report->getProcessStatus() !== ProcessStatus::PENDING) {
            $this->logger->warning('尝试处理非待审核状态的举报', [
                'reportId' => $report->getId(),
                'currentStatus' => $report->getProcessStatus()->value,
                'operator' => $operator,
            ]);
            return $report;
        }

        $this->logger->info('标记举报为审核中状态', [
            'reportId' => $report->getId(),
            'operator' => $operator,
        ]);

        $report->setProcessStatus(ProcessStatus::PROCESSING);
        $this->entityManager->flush();

        return $report;
    }

    /**
     * 查找待处理的举报
     *
     * @return Report[] 待处理的举报列表
     */
    public function findPendingReports(): array
    {
        return $this->reportRepository->findPendingReports();
    }

    /**
     * 查找特定用户的举报
     *
     * @param int|string $userId 用户ID
     * @return Report[] 举报列表
     */
    public function findReportsByUser(int|string $userId): array
    {
        return $this->reportRepository->findByReporterUser($userId);
    }

    /**
     * 查找针对特定内容的举报
     *
     * @param GeneratedContent $content 内容
     * @return Report[] 举报列表
     */
    public function findReportsByContent(GeneratedContent $content): array
    {
        return $this->reportRepository->findByReportedContent($content->getId());
    }

    /**
     * 获取举报统计数据
     *
     * @return array 统计数据
     */
    public function getReportStatistics(): array
    {
        // 获取不同状态的举报数量
        $statusCounts = $this->reportRepository->countByStatus();

        // 按日期统计举报数量（最近7天）
        $dateStats = $this->getReportDateStatistics();

        return [
            'statusCounts' => $statusCounts,
            'dateStats' => $dateStats,
        ];
    }

    /**
     * 获取按日期统计的举报数据（最近7天）
     *
     * @return array 日期统计数据
     */
    private function getReportDateStatistics(): array
    {
        $stats = [];
        $endDate = new \DateTimeImmutable();
        
        // 获取最近7天的数据
        for ($i = 6; $i >= 0; $i--) {
            $date = $endDate->modify("-$i days");
            $startOfDay = $date->setTime(0, 0, 0);
            $endOfDay = $date->setTime(23, 59, 59);
            
            $count = $this->entityManager->createQueryBuilder()
                ->select('COUNT(r.id)')
                ->from(Report::class, 'r')
                ->where('r.reportTime >= :start')
                ->andWhere('r.reportTime <= :end')
                ->setParameter('start', $startOfDay)
                ->setParameter('end', $endOfDay)
                ->getQuery()
                ->getSingleScalarResult();
            
            $stats[$date->format('Y-m-d')] = (int)$count;
        }
        
        return $stats;
    }

    /**
     * 检查用户是否有恶意举报行为
     *
     * @param int|string $userId 用户ID
     * @return bool 是否恶意举报
     */
    public function checkMaliciousReporting(int|string $userId): bool
    {
        // 获取用户最近30天的举报
        $thirtyDaysAgo = new \DateTimeImmutable('-30 days');
        $reports = $this->reportRepository->createQueryBuilder('r')
            ->andWhere('r.reporterUser = :user')
            ->andWhere('r.reportTime >= :timeLimit')
            ->andWhere('r.processStatus = :status')
            ->andWhere('r.processResult LIKE :result')
            ->setParameter('user', $userId)
            ->setParameter('timeLimit', $thirtyDaysAgo)
            ->setParameter('status', '已处理')
            ->setParameter('result', '%不属实%')
            ->getQuery()
            ->getResult();

        // 如果30天内有5次或以上不属实的举报，认为是恶意举报
        if (count($reports) >= 5) {
            $this->logger->warning('检测到恶意举报用户', [
                'userId' => $userId,
                'falseReportsCount' => count($reports)
            ]);
            
            return true;
        }
        
        return false;
    }
} 