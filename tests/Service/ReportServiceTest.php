<?php

namespace AIContentAuditBundle\Tests\Service;

use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Entity\Report;
use AIContentAuditBundle\Enum\ProcessStatus;
use AIContentAuditBundle\Repository\ReportRepository;
use AIContentAuditBundle\Service\ReportService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ReportServiceTest extends TestCase
{
    private ReportService $service;
    private MockObject $entityManager;
    private MockObject $reportRepository;
    private MockObject $logger;
    private UserInterface $user;
    private $content;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->reportRepository = $this->createMock(ReportRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        // 创建一个简单的User实现来避免Mock问题
        $this->user = new class implements UserInterface {
            public function getId(): int { return 123; }
            public function getUserIdentifier(): string { return 'test_user'; }
            public function getRoles(): array { return ['ROLE_USER']; }
            public function eraseCredentials(): void { }
        };
        
        // 创建一个简单的内容对象用于测试
        $this->content = $this->createMock(GeneratedContent::class);
        $this->content->method('getId')->willReturn(123);
        
        
        // 设置EntityManager返回Repository
        $this->entityManager->method('getRepository')
            ->with(Report::class)
            ->willReturn($this->reportRepository);
        
        $this->service = new ReportService(
            $this->entityManager,
            $this->reportRepository,
            $this->logger
        );
    }
    
    public function testSubmitReport()
    {
        $reportReason = '内容不当';
        
        // 设置logger期望 - 分别设置两次调用
        $this->logger->expects($this->exactly(2))
            ->method('info');
            
        // 设置entityManager期望
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($report) use ($reportReason) {
                return $report instanceof Report
                    && $report->getReportReason() === $reportReason
                    && $report->getProcessStatus() === ProcessStatus::PENDING;
            }));
            
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        // 执行方法
        $result = $this->service->submitReport($this->content, $this->user, $reportReason);
        
        // 断言结果
        $this->assertInstanceOf(Report::class, $result);
        $this->assertEquals($reportReason, $result->getReportReason());
        $this->assertEquals(ProcessStatus::PENDING, $result->getProcessStatus());
    }
    
    public function testProcessReport()
    {
        $report = new Report();
        $processResult = '举报属实，已处理';
        $operator = 'admin';
        
        // 使用反射设置ID
        $reflection = new \ReflectionClass($report);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($report, 456);
        
        // 设置logger期望
        $this->logger->expects($this->exactly(2))
            ->method('info');
            
        // 设置entityManager期望
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        // 执行方法
        $result = $this->service->processReport($report, $processResult, $operator);
        
        // 断言结果
        $this->assertEquals(ProcessStatus::COMPLETED, $result->getProcessStatus());
        $this->assertEquals($processResult, $result->getProcessResult());
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->getProcessTime());
    }
    
    public function testStartProcessing_withPendingStatus()
    {
        $report = new Report();
        $report->setProcessStatus(ProcessStatus::PENDING);
        $operator = 'admin';
        
        // 使用反射设置ID
        $reflection = new \ReflectionClass($report);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($report, 789);
        
        // 设置logger期望
        $this->logger->expects($this->once())
            ->method('info');
            
        // 设置entityManager期望
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        // 执行方法
        $result = $this->service->startProcessing($report, $operator);
        
        // 断言结果
        $this->assertEquals(ProcessStatus::PROCESSING, $result->getProcessStatus());
    }
    
    public function testStartProcessing_withNonPendingStatus()
    {
        $report = new Report();
        $report->setProcessStatus(ProcessStatus::COMPLETED);
        $operator = 'admin';
        
        // 使用反射设置ID
        $reflection = new \ReflectionClass($report);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($report, 789);
        
        // 设置logger期望
        $this->logger->expects($this->once())
            ->method('warning');
            
        // 设置entityManager期望 - 不应该调用flush
        $this->entityManager->expects($this->never())
            ->method('flush');
            
        // 执行方法
        $result = $this->service->startProcessing($report, $operator);
        
        // 断言结果 - 状态不应该改变
        $this->assertEquals(ProcessStatus::COMPLETED, $result->getProcessStatus());
    }
    
    public function testFindPendingReports()
    {
        $expectedReports = [new Report(), new Report()];
        
        $this->reportRepository->expects($this->once())
            ->method('findPendingReports')
            ->willReturn($expectedReports);
            
        $result = $this->service->findPendingReports();
        
        $this->assertEquals($expectedReports, $result);
    }
    
    public function testFindReportsByUser()
    {
        $expectedReports = [new Report(), new Report()];
        
        $this->reportRepository->expects($this->once())
            ->method('findByReporterUser')
            ->with('test_user')
            ->willReturn($expectedReports);
            
        $result = $this->service->findReportsByUser($this->user);
        
        $this->assertEquals($expectedReports, $result);
    }
    
    public function testFindReportsByContent()
    {
        $expectedReports = [new Report()];
        
        $this->reportRepository->expects($this->once())
            ->method('findByReportedContent')
            ->with(123)
            ->willReturn($expectedReports);
            
        $result = $this->service->findReportsByContent($this->content);
        
        $this->assertEquals($expectedReports, $result);
    }
    
    public function testGetReportStatistics()
    {
        $statusCounts = [
            'pending' => 5,
            'processing' => 2,
            'completed' => 10
        ];
        
        // Mock QueryBuilder for date statistics
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        
        $this->reportRepository->expects($this->once())
            ->method('countByStatus')
            ->willReturn($statusCounts);
            
        $this->entityManager->expects($this->exactly(7))
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);
            
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);
        
        $query->method('getSingleScalarResult')->willReturn(2);
        
        $result = $this->service->getReportStatistics();
        
        $this->assertArrayHasKey('statusCounts', $result);
        $this->assertArrayHasKey('dateStats', $result);
        $this->assertEquals($statusCounts, $result['statusCounts']);
        $this->assertCount(7, $result['dateStats']); // 7天的数据
    }
    
    public function testCheckMaliciousReporting_withMaliciousUser()
    {
        $reports = array_fill(0, 6, new Report()); // 6个不属实举报
        
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        
        $this->reportRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('r')
            ->willReturn($queryBuilder);
            
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);
        
        $query->method('getResult')->willReturn($reports);
        
        // 设置logger期望
        $this->logger->expects($this->once())
            ->method('warning');
            
        $result = $this->service->checkMaliciousReporting($this->user);
        
        $this->assertTrue($result);
    }
    
    public function testCheckMaliciousReporting_withNormalUser()
    {
        $reports = array_fill(0, 2, new Report()); // 只有2个不属实举报
        
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        
        $this->reportRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('r')
            ->willReturn($queryBuilder);
            
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);
        
        $query->method('getResult')->willReturn($reports);
        
        // 不应该记录警告日志
        $this->logger->expects($this->never())
            ->method('warning');
            
        $result = $this->service->checkMaliciousReporting($this->user);
        
        $this->assertFalse($result);
    }
    
    public function testSubmitReport_withEmptyReason()
    {
        $reportReason = '';
        
        // 设置logger期望
        $this->logger->expects($this->exactly(2))
            ->method('info');
            
        // 设置entityManager期望
        $this->entityManager->expects($this->once())
            ->method('persist');
            
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        // 执行方法
        $result = $this->service->submitReport($this->content, $this->user, $reportReason);
        
        // 断言结果
        $this->assertInstanceOf(Report::class, $result);
        $this->assertEquals('', $result->getReportReason());
    }
    
    public function testProcessReport_withLongProcessResult()
    {
        $report = new Report();
        $processResult = str_repeat('很长的处理结果 ', 100);
        $operator = 'admin';
        
        // 使用反射设置ID
        $reflection = new \ReflectionClass($report);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($report, 456);
        
        // 设置logger期望
        $this->logger->expects($this->exactly(2))
            ->method('info');
            
        // 设置entityManager期望
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        // 执行方法
        $result = $this->service->processReport($report, $processResult, $operator);
        
        // 断言结果
        $this->assertEquals(ProcessStatus::COMPLETED, $result->getProcessStatus());
        $this->assertEquals($processResult, $result->getProcessResult());
    }
}
