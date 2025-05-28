<?php

namespace AIContentAuditBundle\Tests\Service;

use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Entity\RiskKeyword;
use AIContentAuditBundle\Repository\GeneratedContentRepository;
use AIContentAuditBundle\Repository\RiskKeywordRepository;
use AIContentAuditBundle\Service\StatisticsService;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class StatisticsServiceTest extends TestCase
{
    private StatisticsService $service;
    private MockObject $generatedContentRepository;
    private MockObject $riskKeywordRepository;
    private MockObject $logger;

    protected function setUp(): void
    {
        $this->generatedContentRepository = $this->createMock(GeneratedContentRepository::class);
        $this->riskKeywordRepository = $this->createMock(RiskKeywordRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->service = new StatisticsService(
            $this->generatedContentRepository,
            $this->riskKeywordRepository,
            $this->logger
        );
    }
    
    public function testGetAuditEfficiencyStatistics_withAuditedContent()
    {
        // 创建模拟的审核内容
        $content1 = $this->createMock(GeneratedContent::class);
        $content2 = $this->createMock(GeneratedContent::class);
        
        $machineTime = new \DateTimeImmutable('2024-01-01 10:00:00');
        $manualTime1 = new \DateTimeImmutable('2024-01-01 10:05:00'); // 5分钟后
        $manualTime2 = new \DateTimeImmutable('2024-01-01 10:10:00'); // 10分钟后
        
        $content1->method('getMachineAuditTime')->willReturn($machineTime);
        $content1->method('getManualAuditTime')->willReturn($manualTime1);
        
        $content2->method('getMachineAuditTime')->willReturn($machineTime);
        $content2->method('getManualAuditTime')->willReturn($manualTime2);
        
        $auditedContents = [$content1, $content2];
        
        // Mock QueryBuilder for audited contents
        $queryBuilder1 = $this->createMock(QueryBuilder::class);
        $query1 = $this->createMock(Query::class);
        
        $this->generatedContentRepository->expects($this->exactly(2))
            ->method('createQueryBuilder')
            ->with('g')
            ->willReturn($queryBuilder1);
            
        $queryBuilder1->method('andWhere')->willReturnSelf();
        $queryBuilder1->method('setParameter')->willReturnSelf();
        $queryBuilder1->method('select')->willReturnSelf();
        $queryBuilder1->method('groupBy')->willReturnSelf();
        $queryBuilder1->method('getQuery')->willReturn($query1);
        
        // 第一次调用返回审核内容，第二次调用返回审核结果统计
        $query1->method('getResult')
            ->willReturnOnConsecutiveCalls(
                $auditedContents,
                [
                    ['result' => 'PASS', 'count' => 1],
                    ['result' => 'MODIFY', 'count' => 1]
                ]
            );
        
        // 设置logger期望
        $this->logger->expects($this->once())
            ->method('info')
            ->with('获取审核效率统计数据');
            
        // 执行方法
        $result = $this->service->getAuditEfficiencyStatistics();
        
        // 断言结果
        $this->assertArrayHasKey('auditCount', $result);
        $this->assertArrayHasKey('avgAuditTimeSeconds', $result);
        $this->assertArrayHasKey('auditResults', $result);
        
        $this->assertEquals(2, $result['auditCount']);
        $this->assertEquals(450, $result['avgAuditTimeSeconds']); // (300 + 600) / 2 = 450秒
        $this->assertEquals(['PASS' => 1, 'MODIFY' => 1], $result['auditResults']);
    }
    
    public function testGetAuditEfficiencyStatistics_withNoAuditedContent()
    {
        // Mock QueryBuilder for empty results
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        
        $this->generatedContentRepository->expects($this->exactly(2))
            ->method('createQueryBuilder')
            ->with('g')
            ->willReturn($queryBuilder);
            
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('groupBy')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);
        
        // 返回空结果
        $query->method('getResult')->willReturn([]);
        
        // 设置logger期望
        $this->logger->expects($this->once())
            ->method('info')
            ->with('获取审核效率统计数据');
            
        // 执行方法
        $result = $this->service->getAuditEfficiencyStatistics();
        
        // 断言结果
        $this->assertEquals(0, $result['auditCount']);
        $this->assertEquals(0, $result['avgAuditTimeSeconds']);
        $this->assertEquals([], $result['auditResults']);
    }
    
    public function testGetKeywordStatistics()
    {
        $keywordByRiskLevel = [
            'HIGH_RISK' => 10,
            'MEDIUM_RISK' => 15,
            'LOW_RISK' => 20
        ];
        
        $keywordByCategory = [
            '暴力' => 5,
            '色情' => 8,
            '政治' => 12
        ];
        
        $recentKeywords = [
            $this->createMock(RiskKeyword::class),
            $this->createMock(RiskKeyword::class)
        ];
        
        // 设置repository期望
        $this->riskKeywordRepository->expects($this->once())
            ->method('countByRiskLevel')
            ->willReturn($keywordByRiskLevel);
            
        $this->riskKeywordRepository->expects($this->once())
            ->method('countByCategory')
            ->willReturn($keywordByCategory);
            
        $this->riskKeywordRepository->expects($this->once())
            ->method('findRecentUpdated')
            ->with(10)
            ->willReturn($recentKeywords);
        
        // 设置logger期望
        $this->logger->expects($this->once())
            ->method('info')
            ->with('获取风险关键词统计数据');
            
        // 执行方法
        $result = $this->service->getKeywordStatistics();
        
        // 断言结果
        $this->assertArrayHasKey('keywordByRiskLevel', $result);
        $this->assertArrayHasKey('keywordByCategory', $result);
        $this->assertArrayHasKey('recentKeywords', $result);
        
        $this->assertEquals($keywordByRiskLevel, $result['keywordByRiskLevel']);
        $this->assertEquals($keywordByCategory, $result['keywordByCategory']);
        $this->assertEquals($recentKeywords, $result['recentKeywords']);
    }
    
    public function testGetAuditEfficiencyStatistics_withSingleContent()
    {
        // 创建单个模拟内容
        $content = $this->createMock(GeneratedContent::class);
        
        $machineTime = new \DateTimeImmutable('2024-01-01 10:00:00');
        $manualTime = new \DateTimeImmutable('2024-01-01 10:03:00'); // 3分钟后
        
        $content->method('getMachineAuditTime')->willReturn($machineTime);
        $content->method('getManualAuditTime')->willReturn($manualTime);
        
        $auditedContents = [$content];
        
        // Mock QueryBuilder
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        
        $this->generatedContentRepository->expects($this->exactly(2))
            ->method('createQueryBuilder')
            ->with('g')
            ->willReturn($queryBuilder);
            
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('groupBy')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);
        
        $query->method('getResult')
            ->willReturnOnConsecutiveCalls(
                $auditedContents,
                [['result' => 'PASS', 'count' => 1]]
            );
        
        // 设置logger期望
        $this->logger->expects($this->once())
            ->method('info');
            
        // 执行方法
        $result = $this->service->getAuditEfficiencyStatistics();
        
        // 断言结果
        $this->assertEquals(1, $result['auditCount']);
        $this->assertEquals(180, $result['avgAuditTimeSeconds']); // 3分钟 = 180秒
        $this->assertEquals(['PASS' => 1], $result['auditResults']);
    }
    
    public function testGetKeywordStatistics_withEmptyResults()
    {
        // 设置repository返回空结果
        $this->riskKeywordRepository->expects($this->once())
            ->method('countByRiskLevel')
            ->willReturn([]);
            
        $this->riskKeywordRepository->expects($this->once())
            ->method('countByCategory')
            ->willReturn([]);
            
        $this->riskKeywordRepository->expects($this->once())
            ->method('findRecentUpdated')
            ->with(10)
            ->willReturn([]);
        
        // 设置logger期望
        $this->logger->expects($this->once())
            ->method('info');
            
        // 执行方法
        $result = $this->service->getKeywordStatistics();
        
        // 断言结果
        $this->assertEquals([], $result['keywordByRiskLevel']);
        $this->assertEquals([], $result['keywordByCategory']);
        $this->assertEquals([], $result['recentKeywords']);
    }
    
    public function testGetAuditEfficiencyStatistics_withZeroAuditTime()
    {
        // 创建审核时间为0的内容（机器审核时间和人工审核时间相同）
        $content = $this->createMock(GeneratedContent::class);
        
        $auditTime = new \DateTimeImmutable('2024-01-01 10:00:00');
        
        $content->method('getMachineAuditTime')->willReturn($auditTime);
        $content->method('getManualAuditTime')->willReturn($auditTime);
        
        $auditedContents = [$content];
        
        // Mock QueryBuilder
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        
        $this->generatedContentRepository->expects($this->exactly(2))
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);
            
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('groupBy')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);
        
        $query->method('getResult')
            ->willReturnOnConsecutiveCalls(
                $auditedContents,
                [['result' => 'PASS', 'count' => 1]]
            );
        
        // 设置logger期望
        $this->logger->expects($this->once())
            ->method('info');
            
        // 执行方法
        $result = $this->service->getAuditEfficiencyStatistics();
        
        // 断言结果
        $this->assertEquals(1, $result['auditCount']);
        $this->assertEquals(0, $result['avgAuditTimeSeconds']); // 审核时间为0
        $this->assertEquals(['PASS' => 1], $result['auditResults']);
    }
} 