<?php

namespace AIContentAuditBundle\Repository;

use AIContentAuditBundle\Entity\Report;
use AIContentAuditBundle\Enum\ProcessStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * 举报仓库类
 * 
 * @method Report|null find($id, $lockMode = null, $lockVersion = null)
 * @method Report|null findOneBy(array $criteria, array $orderBy = null)
 * @method Report[] findAll()
 * @method Report[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Report::class);
    }

    /**
     * 查找待处理的举报
     *
     * @return Report[] 返回待处理的举报列表
     */
    public function findPendingReports(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.processStatus = :status')
            ->setParameter('status', ProcessStatus::PENDING)
            ->orderBy('r.reportTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找正在处理的举报
     *
     * @return Report[] 返回正在处理的举报列表
     */
    public function findProcessingReports(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.processStatus = :status')
            ->setParameter('status', ProcessStatus::PROCESSING)
            ->orderBy('r.reportTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找已处理的举报
     *
     * @return Report[] 返回已处理的举报列表
     */
    public function findCompletedReports(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.processStatus = :status')
            ->setParameter('status', ProcessStatus::COMPLETED)
            ->orderBy('r.processTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找特定用户提交的举报
     *
     * @param int|string $userId 用户ID
     * @return Report[] 返回举报列表
     */
    public function findByReporterUser(int|string $userId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.reporterUser = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('r.reportTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找对特定内容的举报
     *
     * @param int $contentId 内容ID
     * @return Report[] 返回举报列表
     */
    public function findByReportedContent(int $contentId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.reportedContent = :contentId')
            ->setParameter('contentId', $contentId)
            ->orderBy('r.reportTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 统计各处理状态的举报数量
     *
     * @return array 返回统计结果
     */
    public function countByStatus(): array
    {
        $results = $this->createQueryBuilder('r')
            ->select('r.processStatus as status, COUNT(r.id) as count')
            ->groupBy('r.processStatus')
            ->getQuery()
            ->getResult();

        // 格式化结果为关联数组
        $counts = [];
        foreach ($results as $result) {
            $counts[$result['status']->value] = $result['count'];
        }

        return $counts;
    }

    /**
     * 按日期范围查找举报
     *
     * @param \DateTimeImmutable $startDate 开始日期
     * @param \DateTimeImmutable $endDate 结束日期
     * @return Report[] 返回举报列表
     */
    public function findByDateRange(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.reportTime >= :startDate')
            ->andWhere('r.reportTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('r.reportTime', 'DESC')
            ->getQuery()
            ->getResult();
    }
} 