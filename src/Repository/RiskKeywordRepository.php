<?php

namespace AIContentAuditBundle\Repository;

use AIContentAuditBundle\Entity\RiskKeyword;
use AIContentAuditBundle\Enum\RiskLevel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * 风险关键词仓库类
 *
 * @method RiskKeyword|null find($id, $lockMode = null, $lockVersion = null)
 * @method RiskKeyword|null findOneBy(array $criteria, array $orderBy = null)
 * @method RiskKeyword[] findAll()
 * @method RiskKeyword[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RiskKeywordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RiskKeyword::class);
    }

    /**
     * 根据风险等级查找关键词
     *
     * @param RiskLevel $riskLevel 风险等级
     * @return RiskKeyword[] 返回关键词列表
     */
    public function findByRiskLevel(RiskLevel $riskLevel): array
    {
        return $this->createQueryBuilder('k')
            ->andWhere('k.riskLevel = :riskLevel')
            ->setParameter('riskLevel', $riskLevel)
            ->orderBy('k.keyword', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据关键词模糊查询
     *
     * @param string $keyword 关键词
     * @return RiskKeyword[] 返回关键词列表
     */
    public function findByKeywordLike(string $keyword): array
    {
        return $this->createQueryBuilder('k')
            ->andWhere('k.keyword LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('k.keyword', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据分类查找关键词
     *
     * @param string $category 分类
     * @return RiskKeyword[] 返回关键词列表
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('k')
            ->andWhere('k.category = :category')
            ->setParameter('category', $category)
            ->orderBy('k.keyword', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找所有分类
     *
     * @return array 返回分类列表
     */
    public function findAllCategories(): array
    {
        $results = $this->createQueryBuilder('k')
            ->select('DISTINCT k.category')
            ->where('k.category IS NOT NULL')
            ->orderBy('k.category', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_column($results, 'category');
    }

    /**
     * 检查关键词是否存在
     *
     * @param string $keyword 关键词
     * @return bool 是否存在
     */
    public function existsByKeyword(string $keyword): bool
    {
        $count = $this->createQueryBuilder('k')
            ->select('COUNT(k.id)')
            ->andWhere('k.keyword = :keyword')
            ->setParameter('keyword', $keyword)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * 统计各风险等级的关键词数量
     *
     * @return array 返回统计结果
     */
    public function countByRiskLevel(): array
    {
        $results = $this->createQueryBuilder('k')
            ->select('k.riskLevel as riskLevel, COUNT(k.id) as count')
            ->groupBy('k.riskLevel')
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
     * 统计不同分类的关键词数量
     *
     * @return array 返回统计结果
     */
    public function countByCategory(): array
    {
        $results = $this->createQueryBuilder('k')
            ->select('k.category as category, COUNT(k.id) as count')
            ->groupBy('k.category')
            ->getQuery()
            ->getResult();

        // 格式化结果为关联数组
        $counts = [];
        foreach ($results as $result) {
            $counts[$result['category']] = $result['count'];
        }

        return $counts;
    }

    /**
     * 查找最近更新的关键词
     *
     * @param int $limit 限制数量
     * @return RiskKeyword[] 返回关键词列表
     */
    public function findRecentUpdated(int $limit = 10): array
    {
        return $this->createQueryBuilder('k')
            ->orderBy('k.updateTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找由特定人员添加的关键词
     *
     * @param string $addedBy 添加人
     * @return RiskKeyword[] 返回关键词列表
     */
    public function findByAddedBy(string $addedBy): array
    {
        return $this->createQueryBuilder('k')
            ->andWhere('k.addedBy = :addedBy')
            ->setParameter('addedBy', $addedBy)
            ->orderBy('k.updateTime', 'DESC')
            ->getQuery()
            ->getResult();
    }
} 