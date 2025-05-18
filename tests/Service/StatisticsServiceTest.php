<?php

namespace AIContentAuditBundle\Tests\Service;

use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Repository\GeneratedContentRepository;
use AIContentAuditBundle\Repository\RiskKeywordRepository;
use AIContentAuditBundle\Service\StatisticsService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class StatisticsServiceTest extends TestCase
{
    private StatisticsService $service;
    private EntityManagerInterface|MockObject $entityManager;
    private GeneratedContentRepository|MockObject $contentRepository;
    private RiskKeywordRepository|MockObject $riskKeywordRepository;
    private LoggerInterface|MockObject $logger;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->contentRepository = $this->createMock(GeneratedContentRepository::class);
        $this->riskKeywordRepository = $this->createMock(RiskKeywordRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->service = new StatisticsService(
            $this->contentRepository,
            $this->riskKeywordRepository,
            $this->logger
        );
    }
    
    public function testGetAuditEfficiencyStatistics_returnsEfficiencyData()
    {
        // 模拟查询构建器
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(\Doctrine\ORM\Query::class);
            
        // 创建模拟审核内容
        $content1 = $this->createPartialMock(GeneratedContent::class, ['getMachineAuditTime', 'getManualAuditTime']);
        $content1->method('getMachineAuditTime')->willReturn(new \DateTimeImmutable('-2 days -3 hours'));
        $content1->method('getManualAuditTime')->willReturn(new \DateTimeImmutable('-2 days -2 hours'));
        
        $content2 = $this->createPartialMock(GeneratedContent::class, ['getMachineAuditTime', 'getManualAuditTime']);
        $content2->method('getMachineAuditTime')->willReturn(new \DateTimeImmutable('-1 day -4 hours'));
        $content2->method('getManualAuditTime')->willReturn(new \DateTimeImmutable('-1 day -2 hours'));
        
        $auditedContents = [$content1, $content2];
        
        // 配置查询构建器行为
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('groupBy')->willReturnSelf();
        $qb->method('select')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);
        
        $query->method('getResult')->willReturn($auditedContents);
        
        $query2 = $this->createMock(\Doctrine\ORM\Query::class);
            
        $query2->method('getResult')->willReturn([
            ['result' => '通过', 'count' => 15],
            ['result' => '修改', 'count' => 5],
            ['result' => '删除', 'count' => 3]
        ]);
        
        $qb2 = $this->createMock(QueryBuilder::class);
        $qb2->method('andWhere')->willReturnSelf();
        $qb2->method('setParameter')->willReturnSelf();
        $qb2->method('groupBy')->willReturnSelf();
        $qb2->method('select')->willReturnSelf();
        $qb2->method('getQuery')->willReturn($query2);
        
        // 设置contentRepository的行为
        $this->contentRepository->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($qb, $qb2);
            
        // 执行方法
        $result = $this->service->getAuditEfficiencyStatistics();
        
        // 断言结果
        $this->assertIsArray($result);
        $this->assertArrayHasKey('auditCount', $result);
        $this->assertArrayHasKey('avgAuditTimeSeconds', $result);
        $this->assertArrayHasKey('auditResults', $result);
        
        // 两条内容的审核时间分别是1小时和2小时，平均是1.5小时 = 5400秒
        $this->assertEquals(2, $result['auditCount']);
        $this->assertEquals(5400, $result['avgAuditTimeSeconds']);
        
        // 验证审核结果统计
        $this->assertEquals(15, $result['auditResults']['通过']);
        $this->assertEquals(5, $result['auditResults']['修改']);
        $this->assertEquals(3, $result['auditResults']['删除']);
    }
    
    public function testGetKeywordStatistics_returnsKeywordStats()
    {
        // 设置riskKeywordRepository的行为
        $this->riskKeywordRepository->method('countByRiskLevel')
            ->willReturn([
                '高风险' => 10,
                '中风险' => 15,
                '低风险' => 25
            ]);
            
        $this->riskKeywordRepository->method('countByCategory')
            ->willReturn([
                '色情' => 5,
                '暴力' => 8,
                '政治' => 12,
                '其他' => 25
            ]);
            
        $this->riskKeywordRepository->method('findRecentUpdated')
            ->with(10)
            ->willReturn(['keyword1', 'keyword2', 'keyword3']); // 简化返回值
            
        // 执行方法
        $result = $this->service->getKeywordStatistics();
        
        // 断言结果
        $this->assertIsArray($result);
        $this->assertArrayHasKey('keywordByRiskLevel', $result);
        $this->assertArrayHasKey('keywordByCategory', $result);
        $this->assertArrayHasKey('recentKeywords', $result);
        
        // 验证统计数据
        $this->assertEquals(10, $result['keywordByRiskLevel']['高风险']);
        $this->assertEquals(15, $result['keywordByRiskLevel']['中风险']);
        $this->assertEquals(25, $result['keywordByRiskLevel']['低风险']);
        
        $this->assertEquals(5, $result['keywordByCategory']['色情']);
        $this->assertEquals(8, $result['keywordByCategory']['暴力']);
        $this->assertEquals(12, $result['keywordByCategory']['政治']);
        $this->assertEquals(25, $result['keywordByCategory']['其他']);
        
        $this->assertCount(3, $result['recentKeywords']);
    }
} 