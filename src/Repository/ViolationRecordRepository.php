<?php

namespace AIContentAuditBundle\Repository;

use AIContentAuditBundle\Entity\ViolationRecord;
use AIContentAuditBundle\Enum\ViolationType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * 违规记录仓库类
 *
 * @method ViolationRecord|null find($id, $lockMode = null, $lockVersion = null)
 * @method ViolationRecord|null findOneBy(array $criteria, array $orderBy = null)
 * @method ViolationRecord[] findAll()
 * @method ViolationRecord[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ViolationRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ViolationRecord::class);
    }

    /**
     * 查找特定用户的违规记录
     *
     * @param int|string $userId 用户ID
     * @return ViolationRecord[] 返回违规记录列表
     */
    public function findByUser(int|string $userId): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('v.violationTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找特定类型的违规记录
     *
     * @param ViolationType $type 违规类型
     * @return ViolationRecord[] 返回违规记录列表
     */
    public function findByViolationType(ViolationType $type): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.violationType = :type')
            ->setParameter('type', $type)
            ->orderBy('v.violationTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 统计各违规类型的数量
     *
     * @return array 返回统计结果
     */
    public function countByViolationType(): array
    {
        $results = $this->createQueryBuilder('v')
            ->select('v.violationType as type, COUNT(v.id) as count')
            ->groupBy('v.violationType')
            ->getQuery()
            ->getResult();

        // 格式化结果为关联数组
        $counts = [];
        foreach ($results as $result) {
            $counts[$result['type']->value] = $result['count'];
        }

        return $counts;
    }

    /**
     * 查找特定日期范围内的违规记录
     *
     * @param \DateTimeImmutable $startDate 开始日期
     * @param \DateTimeImmutable $endDate 结束日期
     * @return ViolationRecord[] 返回违规记录列表
     */
    public function findByDateRange(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.violationTime >= :startDate')
            ->andWhere('v.violationTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('v.violationTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 统计特定时间段内的违规记录数量
     *
     * @param \DateTimeImmutable $startDate 开始日期
     * @param \DateTimeImmutable $endDate 结束日期
     * @return int 返回违规记录数量
     */
    public function countByDateRange(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->andWhere('v.violationTime >= :startDate')
            ->andWhere('v.violationTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * 统计指定用户的违规次数
     *
     * @param int|string $userId 用户ID
     * @return int 返回违规次数
     */
    public function countByUser(int|string $userId): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->andWhere('v.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * 查找最近一段时间内的违规记录
     *
     * @param int $days 天数
     * @return ViolationRecord[] 返回违规记录列表
     */
    public function findRecent(int $days = 30): array
    {
        $date = new \DateTimeImmutable("-{$days} days");

        return $this->createQueryBuilder('v')
            ->andWhere('v.violationTime >= :date')
            ->setParameter('date', $date)
            ->orderBy('v.violationTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找被处理人员处理的违规记录
     *
     * @param string $processedBy 处理人员
     * @return ViolationRecord[] 返回违规记录列表
     */
    public function findByProcessedBy(string $processedBy): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.processedBy = :processedBy')
            ->setParameter('processedBy', $processedBy)
            ->orderBy('v.processTime', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
