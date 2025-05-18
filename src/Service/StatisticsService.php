<?php

namespace AIContentAuditBundle\Service;

use AIContentAuditBundle\Repository\GeneratedContentRepository;
use AIContentAuditBundle\Repository\RiskKeywordRepository;
use Psr\Log\LoggerInterface;

/**
 * 数据统计服务类
 */
class StatisticsService
{
    public function __construct(
        private readonly GeneratedContentRepository $generatedContentRepository,
        private readonly RiskKeywordRepository $riskKeywordRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 获取审核效率统计
     *
     * @return array 审核效率数据
     */
    public function getAuditEfficiencyStatistics(): array
    {
        $this->logger->info('获取审核效率统计数据');

        // 获取过去7天的人工审核内容
        $sevenDaysAgo = new \DateTimeImmutable('-7 days');
        $auditedContents = $this->generatedContentRepository->createQueryBuilder('g')
            ->andWhere('g.manualAuditResult IS NOT NULL')
            ->andWhere('g.manualAuditTime >= :timeLimit')
            ->setParameter('timeLimit', $sevenDaysAgo)
            ->getQuery()
            ->getResult();

        // 计算平均审核时间
        $totalAuditTime = 0;
        $auditCount = count($auditedContents);

        if ($auditCount > 0) {
            foreach ($auditedContents as $content) {
                // 计算从机器审核到人工审核的时间差（秒）
                $machineTime = $content->getMachineAuditTime()->getTimestamp();
                $manualTime = $content->getManualAuditTime()->getTimestamp();
                $auditTime = $manualTime - $machineTime;
                $totalAuditTime += $auditTime;
            }
            $avgAuditTime = $totalAuditTime / $auditCount;
        } else {
            $avgAuditTime = 0;
        }

        // 获取过去7天内各审核结果的数量
        $auditResults = $this->generatedContentRepository->createQueryBuilder('g')
            ->select('g.manualAuditResult as result, COUNT(g.id) as count')
            ->andWhere('g.manualAuditResult IS NOT NULL')
            ->andWhere('g.manualAuditTime >= :timeLimit')
            ->setParameter('timeLimit', $sevenDaysAgo)
            ->groupBy('g.manualAuditResult')
            ->getQuery()
            ->getResult();

        $resultCounts = [];
        foreach ($auditResults as $result) {
            $resultCounts[$result['result']] = $result['count'];
        }

        return [
            'auditCount' => $auditCount,
            'avgAuditTimeSeconds' => $avgAuditTime,
            'auditResults' => $resultCounts
        ];
    }

    /**
     * 获取风险关键词统计
     *
     * @return array 关键词统计数据
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
            'recentKeywords' => $recentKeywords
        ];
    }

    /**
     * 辅助方法：填充缺失日期
     *
     * @param array $data 原始数据
     * @param \DateTimeImmutable $startDate 开始日期
     * @param \DateTimeImmutable $endDate 结束日期
     * @return array 填充后的数据
     */
    private function fillMissingDates(array $data, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        $filledData = [];
        $currentDate = $startDate;

        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('Y-m-d');
            $filledData[$dateString] = $data[$dateString] ?? 0;
            $currentDate = $currentDate->modify('+1 day');
        }

        return $filledData;
    }

    /**
     * 辅助方法：创建日期时间范围用于条件查询
     *
     * @param \DateTimeImmutable $startDate 开始日期
     * @param \DateTimeImmutable $endDate 结束日期
     * @return string 范围条件
     */
    private function dateTimeRange(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): string
    {
        return ">= {$startDate->format('Y-m-d H:i:s')} AND <= {$endDate->format('Y-m-d H:i:s')}";
    }
}
