<?php

namespace AIContentAuditBundle\Tests\Repository;

use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Enum\RiskLevel;
use AIContentAuditBundle\Repository\GeneratedContentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GeneratedContentRepositoryTest extends TestCase
{
    private GeneratedContentRepository $repository;
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
            
        $this->repository = new GeneratedContentRepository($this->managerRegistry);
        
        // 设置QueryBuilder的默认行为
        $this->queryBuilder->method('andWhere')->willReturnSelf();
        $this->queryBuilder->method('setParameter')->willReturnSelf();
        $this->queryBuilder->method('orderBy')->willReturnSelf();
        $this->queryBuilder->method('select')->willReturnSelf();
        $this->queryBuilder->method('groupBy')->willReturnSelf();
        $this->queryBuilder->method('getQuery')->willReturn($this->query);
    }
    
    public function testFindNeedManualAudit()
    {
        $expectedContents = [
            $this->createGeneratedContent(1, RiskLevel::MEDIUM_RISK),
            $this->createGeneratedContent(2, RiskLevel::MEDIUM_RISK)
        ];
        
        // 使用反射来模拟createQueryBuilder方法
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('createQueryBuilder');
        $method->setAccessible(true);
        
        // Mock repository的createQueryBuilder方法
        $repositoryMock = $this->getMockBuilder(GeneratedContentRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('g')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->expects($this->exactly(2))
            ->method('andWhere')
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('riskLevel', RiskLevel::MEDIUM_RISK)
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('g.machineAuditTime', 'ASC')
            ->willReturnSelf();
            
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedContents);
            
        $result = $repositoryMock->findNeedManualAudit();
        
        $this->assertEquals($expectedContents, $result);
        $this->assertCount(2, $result);
    }
    
    public function testFindByMachineAuditResult()
    {
        $riskLevel = RiskLevel::HIGH_RISK;
        $expectedContents = [
            $this->createGeneratedContent(1, $riskLevel),
            $this->createGeneratedContent(2, $riskLevel)
        ];
        
        $repositoryMock = $this->getMockBuilder(GeneratedContentRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('g')
            ->willReturn($this->queryBuilder);
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('g')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('g.machineAuditResult = :riskLevel')
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('riskLevel', $riskLevel)
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('g.machineAuditTime', 'DESC')
            ->willReturnSelf();
            
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedContents);
            
        $result = $repositoryMock->findByMachineAuditResult($riskLevel);
        
        $this->assertEquals($expectedContents, $result);
        $this->assertCount(2, $result);
    }
    
    public function testCountByRiskLevel()
    {
        $queryResults = [
            ['riskLevel' => RiskLevel::HIGH_RISK, 'count' => 5],
            ['riskLevel' => RiskLevel::MEDIUM_RISK, 'count' => 10],
            ['riskLevel' => RiskLevel::LOW_RISK, 'count' => 15]
        ];
        
        $expectedCounts = [
            '高风险' => 5,
            '中风险' => 10,
            '低风险' => 15
        ];
        
        $repositoryMock = $this->getMockBuilder(GeneratedContentRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('g')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->expects($this->once())
            ->method('select')
            ->with('g.machineAuditResult as riskLevel, COUNT(g.id) as count')
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('groupBy')
            ->with('g.machineAuditResult')
            ->willReturnSelf();
            
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($queryResults);
            
        $result = $repositoryMock->countByRiskLevel();
        
        $this->assertEquals($expectedCounts, $result);
    }
    
    public function testFindByUser()
    {
        $userId = 123;
        $expectedContents = [
            $this->createGeneratedContent(1, RiskLevel::NO_RISK),
            $this->createGeneratedContent(2, RiskLevel::LOW_RISK)
        ];
        
        $repositoryMock = $this->getMockBuilder(GeneratedContentRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('g')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('g.user = :userId')
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('userId', $userId)
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('g.machineAuditTime', 'DESC')
            ->willReturnSelf();
            
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedContents);
            
        $result = $repositoryMock->findByUser($userId);
        
        $this->assertEquals($expectedContents, $result);
        $this->assertCount(2, $result);
    }
    
    public function testFindByDateRange()
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');
        $expectedContents = [
            $this->createGeneratedContent(1, RiskLevel::NO_RISK),
            $this->createGeneratedContent(2, RiskLevel::MEDIUM_RISK)
        ];
        
        $repositoryMock = $this->getMockBuilder(GeneratedContentRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('g')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->expects($this->exactly(2))
            ->method('andWhere')
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('g.machineAuditTime', 'DESC')
            ->willReturnSelf();
            
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedContents);
            
        $result = $repositoryMock->findByDateRange($startDate, $endDate);
        
        $this->assertEquals($expectedContents, $result);
        $this->assertCount(2, $result);
    }
    
    public function testFindNeedManualAudit_withEmptyResult()
    {
        $repositoryMock = $this->getMockBuilder(GeneratedContentRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);
            
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);
            
        $result = $repositoryMock->findNeedManualAudit();
        
        $this->assertEquals([], $result);
        $this->assertCount(0, $result);
    }
    
    public function testCountByRiskLevel_withEmptyResult()
    {
        $repositoryMock = $this->getMockBuilder(GeneratedContentRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);
            
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);
            
        $result = $repositoryMock->countByRiskLevel();
        
        $this->assertEquals([], $result);
    }
    
    public function testFindByUser_withStringUserId()
    {
        $userId = 'user123';
        $expectedContents = [
            $this->createGeneratedContent(1, RiskLevel::NO_RISK)
        ];
        
        $repositoryMock = $this->getMockBuilder(GeneratedContentRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('userId', $userId)
            ->willReturnSelf();
            
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedContents);
            
        $result = $repositoryMock->findByUser($userId);
        
        $this->assertEquals($expectedContents, $result);
    }
    
    /**
     * 创建测试用的GeneratedContent实例
     */
    private function createGeneratedContent(int $id, RiskLevel $riskLevel): GeneratedContent
    {
        $content = new GeneratedContent();
        $content->setInputText('Test input ' . $id);
        $content->setOutputText('Test output ' . $id);
        $content->setMachineAuditResult($riskLevel);
        $content->setMachineAuditTime(new \DateTimeImmutable());
        
        // 使用反射设置ID
        $reflection = new \ReflectionClass($content);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($content, $id);
        
        return $content;
    }
} 