<?php

declare(strict_types=1);

namespace AIContentAuditBundle\Repository;

use AIContentAuditBundle\Entity\RiskKeyword;
use AIContentAuditBundle\Enum\RiskLevel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * 风险关键词仓库类
 *
 * @extends ServiceEntityRepository<RiskKeyword>
 */
#[AsRepository(entityClass: RiskKeyword::class)]
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
     *
     * @return RiskKeyword[] 返回关键词列表
     */
    public function findByRiskLevel(RiskLevel $riskLevel): array
    {
        /** @var RiskKeyword[] */
        return $this->createQueryBuilder('k')
            ->andWhere('k.riskLevel = :riskLevel')
            ->setParameter('riskLevel', $riskLevel)
            ->orderBy('k.keyword', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据关键词模糊查询
     *
     * @param string $keyword 关键词
     *
     * @return RiskKeyword[] 返回关键词列表
     */
    public function findByKeywordLike(string $keyword): array
    {
        /** @var RiskKeyword[] */
        return $this->createQueryBuilder('k')
            ->andWhere('k.keyword LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('k.keyword', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据分类查找关键词
     *
     * @param string $category 分类
     *
     * @return RiskKeyword[] 返回关键词列表
     */
    public function findByCategory(string $category): array
    {
        /** @var RiskKeyword[] */
        return $this->createQueryBuilder('k')
            ->andWhere('k.category = :category')
            ->setParameter('category', $category)
            ->orderBy('k.keyword', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找所有分类
     *
     * @return array<int, string> 返回分类列表
     */
    public function findAllCategories(): array
    {
        /** @var list<array{category: string}> $results */
        $results = $this->createQueryBuilder('k')
            ->select('DISTINCT k.category')
            ->where('k.category IS NOT NULL')
            ->orderBy('k.category', 'ASC')
            ->getQuery()
            ->getScalarResult()
        ;

        /** @var array<int, string> */
        return array_column($results, 'category');
    }

    /**
     * 检查关键词是否存在
     *
     * @param string $keyword 关键词
     *
     * @return bool 是否存在
     */
    public function existsByKeyword(string $keyword): bool
    {
        $count = $this->createQueryBuilder('k')
            ->select('COUNT(k.id)')
            ->andWhere('k.keyword = :keyword')
            ->setParameter('keyword', $keyword)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return $count > 0;
    }

    /**
     * 统计各风险等级的关键词数量
     *
     * @return array<string, int> 返回统计结果
     */
    public function countByRiskLevel(): array
    {
        /** @var list<array{riskLevel: RiskLevel, count: int}> $results */
        $results = $this->createQueryBuilder('k')
            ->select('k.riskLevel as riskLevel, COUNT(k.id) as count')
            ->groupBy('k.riskLevel')
            ->getQuery()
            ->getResult()
        ;

        // 格式化结果为关联数组
        $counts = [];
        foreach ($results as $result) {
            if (isset($result['riskLevel']) && $result['riskLevel'] instanceof RiskLevel && isset($result['count'])) {
                $counts[$result['riskLevel']->value] = (int) $result['count'];
            }
        }

        return $counts;
    }

    /**
     * 统计不同分类的关键词数量
     *
     * @return array<string, int> 返回统计结果
     */
    public function countByCategory(): array
    {
        /** @var list<array{category: string, count: int}> $results */
        $results = $this->createQueryBuilder('k')
            ->select('k.category as category, COUNT(k.id) as count')
            ->groupBy('k.category')
            ->getQuery()
            ->getResult()
        ;

        // 格式化结果为关联数组
        $counts = [];
        foreach ($results as $result) {
            if (isset($result['category']) && is_string($result['category']) && isset($result['count'])) {
                $counts[$result['category']] = (int) $result['count'];
            }
        }

        return $counts;
    }

    /**
     * 查找最近更新的关键词
     *
     * @param int $limit 限制数量
     *
     * @return RiskKeyword[] 返回关键词列表
     */
    public function findRecentUpdated(int $limit = 10): array
    {
        /** @var RiskKeyword[] */
        return $this->createQueryBuilder('k')
            ->orderBy('k.updateTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找由特定人员添加的关键词
     *
     * @param string $addedBy 添加人
     *
     * @return RiskKeyword[] 返回关键词列表
     */
    public function findByAddedBy(string $addedBy): array
    {
        /** @var RiskKeyword[] */
        return $this->createQueryBuilder('k')
            ->andWhere('k.addedBy = :addedBy')
            ->setParameter('addedBy', $addedBy)
            ->orderBy('k.updateTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 保存实体
     */
    public function save(RiskKeyword $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除实体
     */
    public function remove(RiskKeyword $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
