<?php

namespace AIContentAuditBundle\Tests\Repository;

use AIContentAuditBundle\Entity\RiskKeyword;
use AIContentAuditBundle\Enum\RiskLevel;
use AIContentAuditBundle\Repository\RiskKeywordRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RiskKeywordRepositoryTest extends TestCase
{
    private MockObject $entityManager;
    private MockObject $queryBuilder;
    private MockObject $query;
    private MockObject $managerRegistry;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        
        // 设置ManagerRegistry返回EntityManager
        $this->managerRegistry->method('getManagerForClass')
            ->willReturn($this->entityManager);
            
        // 设置QueryBuilder的默认行为
        $this->queryBuilder->method('andWhere')->willReturnSelf();
        $this->queryBuilder->method('where')->willReturnSelf();
        $this->queryBuilder->method('setParameter')->willReturnSelf();
        $this->queryBuilder->method('orderBy')->willReturnSelf();
        $this->queryBuilder->method('select')->willReturnSelf();
        $this->queryBuilder->method('groupBy')->willReturnSelf();
        $this->queryBuilder->method('setMaxResults')->willReturnSelf();
        $this->queryBuilder->method('getQuery')->willReturn($this->query);
    }
    
    public function testFindByRiskLevel()
    {
        $riskLevel = RiskLevel::HIGH_RISK;
        $expectedKeywords = [
            $this->createRiskKeyword(1, '暴力', $riskLevel),
            $this->createRiskKeyword(2, '血腥', $riskLevel)
        ];
        
        $repositoryMock = $this->getMockBuilder(RiskKeywordRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('k')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('k.riskLevel = :riskLevel')
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('riskLevel', $riskLevel)
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('k.keyword', 'ASC')
            ->willReturnSelf();
            
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedKeywords);
            
        $result = $repositoryMock->findByRiskLevel($riskLevel);
        
        $this->assertEquals($expectedKeywords, $result);
        $this->assertCount(2, $result);
    }
    
    public function testFindByKeywordLike()
    {
        $keyword = '暴力';
        $expectedKeywords = [
            $this->createRiskKeyword(1, '暴力', RiskLevel::HIGH_RISK),
            $this->createRiskKeyword(2, '暴力倾向', RiskLevel::MEDIUM_RISK)
        ];
        
        $repositoryMock = $this->getMockBuilder(RiskKeywordRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('k')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('k.keyword LIKE :keyword')
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('keyword', '%' . $keyword . '%')
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('k.keyword', 'ASC')
            ->willReturnSelf();
            
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedKeywords);
            
        $result = $repositoryMock->findByKeywordLike($keyword);
        
        $this->assertEquals($expectedKeywords, $result);
        $this->assertCount(2, $result);
    }
    
    public function testFindByCategory()
    {
        $category = '色情';
        $expectedKeywords = [
            $this->createRiskKeyword(1, '色情内容', RiskLevel::HIGH_RISK, $category),
            $this->createRiskKeyword(2, '成人内容', RiskLevel::MEDIUM_RISK, $category)
        ];
        
        $repositoryMock = $this->getMockBuilder(RiskKeywordRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('k')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('k.category = :category')
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('category', $category)
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('k.keyword', 'ASC')
            ->willReturnSelf();
            
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedKeywords);
            
        $result = $repositoryMock->findByCategory($category);
        
        $this->assertEquals($expectedKeywords, $result);
        $this->assertCount(2, $result);
    }
    
    public function testFindAllCategories()
    {
        $queryResults = [
            ['category' => '色情'],
            ['category' => '暴力'],
            ['category' => '政治']
        ];
        
        $expectedCategories = ['色情', '暴力', '政治'];
        
        $repositoryMock = $this->getMockBuilder(RiskKeywordRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('k')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->expects($this->once())
            ->method('select')
            ->with('DISTINCT k.category')
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('where')
            ->with('k.category IS NOT NULL')
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('k.category', 'ASC')
            ->willReturnSelf();
            
        $this->query->expects($this->once())
            ->method('getScalarResult')
            ->willReturn($queryResults);
            
        $result = $repositoryMock->findAllCategories();
        
        $this->assertEquals($expectedCategories, $result);
        $this->assertCount(3, $result);
    }
    
    public function testExistsByKeyword()
    {
        $keyword = '暴力';
        
        $repositoryMock = $this->getMockBuilder(RiskKeywordRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('k')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->expects($this->once())
            ->method('select')
            ->with('COUNT(k.id)')
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('k.keyword = :keyword')
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('keyword', $keyword)
            ->willReturnSelf();
            
        $this->query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(1);
            
        $result = $repositoryMock->existsByKeyword($keyword);
        
        $this->assertTrue($result);
    }
    
    public function testExistsByKeyword_notExists()
    {
        $keyword = '不存在的关键词';
        
        $repositoryMock = $this->getMockBuilder(RiskKeywordRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);
            
        $this->query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(0);
            
        $result = $repositoryMock->existsByKeyword($keyword);
        
        $this->assertFalse($result);
    }
    
    public function testCountByRiskLevel()
    {
        $queryResults = [
            ['riskLevel' => RiskLevel::HIGH_RISK, 'count' => 10],
            ['riskLevel' => RiskLevel::MEDIUM_RISK, 'count' => 15],
            ['riskLevel' => RiskLevel::LOW_RISK, 'count' => 20]
        ];
        
        $expectedCounts = [
            '高风险' => 10,
            '中风险' => 15,
            '低风险' => 20
        ];
        
        $repositoryMock = $this->getMockBuilder(RiskKeywordRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('k')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->expects($this->once())
            ->method('select')
            ->with('k.riskLevel as riskLevel, COUNT(k.id) as count')
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('groupBy')
            ->with('k.riskLevel')
            ->willReturnSelf();
            
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($queryResults);
            
        $result = $repositoryMock->countByRiskLevel();
        
        $this->assertEquals($expectedCounts, $result);
    }
    
    public function testCountByCategory()
    {
        $queryResults = [
            ['category' => '色情', 'count' => 5],
            ['category' => '暴力', 'count' => 8],
            ['category' => '政治', 'count' => 12]
        ];
        
        $expectedCounts = [
            '色情' => 5,
            '暴力' => 8,
            '政治' => 12
        ];
        
        $repositoryMock = $this->getMockBuilder(RiskKeywordRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('k')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->expects($this->once())
            ->method('select')
            ->with('k.category as category, COUNT(k.id) as count')
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('groupBy')
            ->with('k.category')
            ->willReturnSelf();
            
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($queryResults);
            
        $result = $repositoryMock->countByCategory();
        
        $this->assertEquals($expectedCounts, $result);
    }
    
    public function testFindRecentUpdated()
    {
        $limit = 5;
        $expectedKeywords = [
            $this->createRiskKeyword(1, '最新关键词1', RiskLevel::HIGH_RISK),
            $this->createRiskKeyword(2, '最新关键词2', RiskLevel::MEDIUM_RISK)
        ];
        
        $repositoryMock = $this->getMockBuilder(RiskKeywordRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('k')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('k.updateTime', 'DESC')
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();
            
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedKeywords);
            
        $result = $repositoryMock->findRecentUpdated($limit);
        
        $this->assertEquals($expectedKeywords, $result);
        $this->assertCount(2, $result);
    }
    
    public function testFindRecentUpdated_withDefaultLimit()
    {
        $expectedKeywords = [
            $this->createRiskKeyword(1, '最新关键词1', RiskLevel::HIGH_RISK)
        ];
        
        $repositoryMock = $this->getMockBuilder(RiskKeywordRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(10) // 默认限制
            ->willReturnSelf();
            
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedKeywords);
            
        $result = $repositoryMock->findRecentUpdated();
        
        $this->assertEquals($expectedKeywords, $result);
    }
    
    public function testFindByAddedBy()
    {
        $addedBy = 'admin';
        $expectedKeywords = [
            $this->createRiskKeyword(1, '管理员添加1', RiskLevel::HIGH_RISK),
            $this->createRiskKeyword(2, '管理员添加2', RiskLevel::MEDIUM_RISK)
        ];
        
        $repositoryMock = $this->getMockBuilder(RiskKeywordRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('k')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('k.addedBy = :addedBy')
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('addedBy', $addedBy)
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('k.updateTime', 'DESC')
            ->willReturnSelf();
            
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedKeywords);
            
        $result = $repositoryMock->findByAddedBy($addedBy);
        
        $this->assertEquals($expectedKeywords, $result);
        $this->assertCount(2, $result);
    }
    
    public function testFindByRiskLevel_withEmptyResult()
    {
        $repositoryMock = $this->getMockBuilder(RiskKeywordRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);
            
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);
            
        $result = $repositoryMock->findByRiskLevel(RiskLevel::NO_RISK);
        
        $this->assertEquals([], $result);
        $this->assertCount(0, $result);
    }
    
    public function testFindAllCategories_withEmptyResult()
    {
        $repositoryMock = $this->getMockBuilder(RiskKeywordRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);
            
        $this->query->expects($this->once())
            ->method('getScalarResult')
            ->willReturn([]);
            
        $result = $repositoryMock->findAllCategories();
        
        $this->assertEquals([], $result);
    }
    
    /**
     * 创建测试用的RiskKeyword实例
     */
    private function createRiskKeyword(int $id, string $keyword, RiskLevel $riskLevel, string $category = '默认分类'): RiskKeyword
    {
        $riskKeyword = new RiskKeyword();
        $riskKeyword->setKeyword($keyword);
        $riskKeyword->setRiskLevel($riskLevel);
        $riskKeyword->setCategory($category);
        $riskKeyword->setAddedBy('test_user');
        $riskKeyword->setUpdateTime(new \DateTimeImmutable());
        
        // 使用反射设置ID
        $reflection = new \ReflectionClass($riskKeyword);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($riskKeyword, $id);
        
        return $riskKeyword;
    }
} 