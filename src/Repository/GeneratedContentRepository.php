<?php

namespace AIContentAuditBundle\Repository;

use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Enum\RiskLevel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * 生成内容仓库类
 * 
 * @method GeneratedContent|null find($id, $lockMode = null, $lockVersion = null)
 * @method GeneratedContent|null findOneBy(array $criteria, array $orderBy = null)
 * @method GeneratedContent[] findAll()
 * @method GeneratedContent[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GeneratedContentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GeneratedContent::class);
    }

    /**
     * 查找需要人工审核的内容（机器审核结果为中风险的内容）
     *
     * @return GeneratedContent[] 返回需要人工审核的内容列表
     */
    public function findNeedManualAudit(): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.machineAuditResult = :riskLevel')
            ->andWhere('g.manualAuditResult IS NULL')
            ->setParameter('riskLevel', RiskLevel::MEDIUM_RISK)
            ->orderBy('g.machineAuditTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据机器审核结果查找内容
     *
     * @param RiskLevel $riskLevel 风险等级
     * @return GeneratedContent[] 返回内容列表
     */
    public function findByMachineAuditResult(RiskLevel $riskLevel): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.machineAuditResult = :riskLevel')
            ->setParameter('riskLevel', $riskLevel)
            ->orderBy('g.machineAuditTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 统计各风险等级内容数量
     *
     * @return array 返回统计结果
     */
    public function countByRiskLevel(): array
    {
        $results = $this->createQueryBuilder('g')
            ->select('g.machineAuditResult as riskLevel, COUNT(g.id) as count')
            ->groupBy('g.machineAuditResult')
            ->getQuery()
            ->getResult();

        // 格式化结果为关联数组
        $counts = [];
        foreach ($results as $result) {
            $counts[$result['riskLevel']->value] = $result['count'];
        }

        return $counts;
    }

    /**
     * 查找指定用户的生成内容
     *
     * @param int|string $userId 用户ID
     * @return GeneratedContent[] 返回内容列表
     */
    public function findByUser(int|string $userId): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('g.machineAuditTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 按日期范围查找内容
     *
     * @param \DateTimeImmutable $startDate 开始日期
     * @param \DateTimeImmutable $endDate 结束日期
     * @return GeneratedContent[] 返回内容列表
     */
    public function findByDateRange(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.machineAuditTime >= :startDate')
            ->andWhere('g.machineAuditTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('g.machineAuditTime', 'DESC')
            ->getQuery()
            ->getResult();
    }
} 