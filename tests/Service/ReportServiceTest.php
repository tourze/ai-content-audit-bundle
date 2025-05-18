<?php

namespace AIContentAuditBundle\Tests\Service;

use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Entity\Report;
use AIContentAuditBundle\Enum\ProcessStatus;
use AIContentAuditBundle\Repository\ReportRepository;
use AIContentAuditBundle\Service\ContentAuditService;
use AIContentAuditBundle\Service\ReportService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ReportServiceTest extends TestCase
{
    private ReportService $service;
    private EntityManagerInterface|MockObject $entityManager;
    private ReportRepository|MockObject $reportRepository;
    private ContentAuditService|MockObject $contentAuditService;
    private LoggerInterface|MockObject $logger;
    private UserInterface|MockObject $user;
    private GeneratedContent|MockObject $content;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->reportRepository = $this->createMock(ReportRepository::class);
        $this->contentAuditService = $this->createMock(ContentAuditService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->user = $this->createMock(UserInterface::class);
        $this->content = $this->createMock(GeneratedContent::class);
        
        $this->service = new ReportService(
            $this->entityManager,
            $this->reportRepository,
            $this->contentAuditService,
            $this->logger
        );
        
        // 配置UserInterface方法
        $this->user->method('getUserIdentifier')->willReturn('test_user');
        $this->content->method('getId')->willReturn(123);
    }
    
    public function testSubmitReport_createsNewReport()
    {
        // 设置entityManager的期望行为
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($report) {
                return $report instanceof Report
                    && $report->getReportReason() === '违规内容'
                    && $report->getProcessStatus() === ProcessStatus::PENDING;
            }));
            
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        // 执行方法
        $result = $this->service->submitReport(
            $this->content,
            $this->user,
            '违规内容'
        );
        
        // 断言结果
        $this->assertInstanceOf(Report::class, $result);
        $this->assertEquals($this->content, $result->getReportedContent());
        $this->assertEquals($this->user, $result->getReporterUser());
        $this->assertEquals('违规内容', $result->getReportReason());
        $this->assertEquals(ProcessStatus::PENDING, $result->getProcessStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->getReportTime());
    }
    
    public function testProcessReport_updatesReportStatus()
    {
        // 创建测试报告
        $report = new Report();
        $report->setReportedContent($this->content);
        $report->setReporterUser($this->user);
        $report->setReportReason('违规内容');
        $report->setProcessStatus(ProcessStatus::PENDING);
        
        // 设置entityManager的期望行为
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        // 执行方法
        $result = $this->service->processReport(
            $report,
            '举报成立，内容已删除',
            'admin'
        );
        
        // 断言结果
        $this->assertInstanceOf(Report::class, $result);
        $this->assertEquals(ProcessStatus::COMPLETED, $result->getProcessStatus());
        $this->assertEquals('举报成立，内容已删除', $result->getProcessResult());
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->getProcessTime());
    }
    
    public function testStartProcessing_setsStatusToProcessing()
    {
        // 创建测试报告
        $report = new Report();
        $report->setReportedContent($this->content);
        $report->setReporterUser($this->user);
        $report->setReportReason('违规内容');
        $report->setProcessStatus(ProcessStatus::PENDING);
        
        // 设置entityManager的期望行为
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        // 执行方法
        $result = $this->service->startProcessing($report, 'admin');
        
        // 断言结果
        $this->assertInstanceOf(Report::class, $result);
        $this->assertEquals(ProcessStatus::PROCESSING, $result->getProcessStatus());
    }
    
    public function testStartProcessing_ignoresNonPendingReports()
    {
        // 创建已经在处理中的报告
        $report = new Report();
        $report->setReportedContent($this->content);
        $report->setReporterUser($this->user);
        $report->setReportReason('违规内容');
        $report->setProcessStatus(ProcessStatus::PROCESSING);
        
        // 设置entityManager的期望行为
        $this->entityManager->expects($this->never())
            ->method('flush');
            
        // 执行方法
        $result = $this->service->startProcessing($report, 'admin');
        
        // 断言结果
        $this->assertInstanceOf(Report::class, $result);
        $this->assertEquals(ProcessStatus::PROCESSING, $result->getProcessStatus());
    }
    
    public function testFindPendingReports_returnsPendingReports()
    {
        // 创建测试报告列表
        $pendingReport1 = new Report();
        $pendingReport2 = new Report();
        $pendingReports = [$pendingReport1, $pendingReport2];
        
        // 设置repository的期望行为
        $this->reportRepository->expects($this->once())
            ->method('findPendingReports')
            ->willReturn($pendingReports);
            
        // 执行方法
        $result = $this->service->findPendingReports();
        
        // 断言结果
        $this->assertCount(2, $result);
        $this->assertSame($pendingReports, $result);
    }
    
    public function testFindReportsByUser_returnsUserReports()
    {
        // 创建测试报告列表
        $userReport1 = new Report();
        $userReport2 = new Report();
        $userReports = [$userReport1, $userReport2];
        
        // 设置repository的期望行为
        $this->reportRepository->expects($this->once())
            ->method('findByReporterUser')
            ->with('test_user')
            ->willReturn($userReports);
            
        // 执行方法
        $result = $this->service->findReportsByUser($this->user);
        
        // 断言结果
        $this->assertCount(2, $result);
        $this->assertSame($userReports, $result);
    }
    
    public function testFindReportsByContent_returnsContentReports()
    {
        // 创建测试报告列表
        $contentReport1 = new Report();
        $contentReport2 = new Report();
        $contentReports = [$contentReport1, $contentReport2];
        
        // 设置repository的期望行为
        $this->reportRepository->expects($this->once())
            ->method('findByReportedContent')
            ->with(123)
            ->willReturn($contentReports);
            
        // 执行方法
        $result = $this->service->findReportsByContent($this->content);
        
        // 断言结果
        $this->assertCount(2, $result);
        $this->assertSame($contentReports, $result);
    }
    
    public function testCheckMaliciousReporting_returnsTrueForMaliciousUser()
    {
        // 创建一个特殊的User类进行测试
        $mockUser = new class implements UserInterface {
            private int $id = 1001;
            
            public function getRoles(): array
            {
                return ['ROLE_USER'];
            }
            
            public function getPassword(): ?string
            {
                return null;
            }
            
            public function getSalt(): ?string
            {
                return null;
            }
            
            public function eraseCredentials(): void
            {
            }
            
            public function getUserIdentifier(): string
            {
                return 'test_user';
            }
            
            public function getId(): int
            {
                return $this->id;
            }
        };
        
        // 创建查询构建器
        $qb = $this->createMock(QueryBuilder::class);
        
        // 使用createMock创建Query的模拟
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        
        // 设置查询返回5个可疑举报
        $maliciousReports = [
            new Report(), new Report(), new Report(), new Report(), new Report()
        ];
        
        $query->method('getResult')->willReturn($maliciousReports);
        
        // 配置查询构建器行为
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);
        
        // 设置repository的期望行为
        $this->reportRepository->method('createQueryBuilder')
            ->willReturn($qb);
            
        // 执行方法
        $result = $this->service->checkMaliciousReporting($mockUser);
        
        // 断言结果
        $this->assertTrue($result);
    }
    
    public function testCheckMaliciousReporting_returnsFalseForNormalUser()
    {
        // 创建一个特殊的User类进行测试
        $mockUser = new class implements UserInterface {
            private int $id = 1002;
            
            public function getRoles(): array
            {
                return ['ROLE_USER'];
            }
            
            public function getPassword(): ?string
            {
                return null;
            }
            
            public function getSalt(): ?string
            {
                return null;
            }
            
            public function eraseCredentials(): void
            {
            }
            
            public function getUserIdentifier(): string
            {
                return 'normal_user';
            }
            
            public function getId(): int
            {
                return $this->id;
            }
        };
        
        // 创建查询构建器
        $qb = $this->createMock(QueryBuilder::class);
        
        // 使用createMock创建Query的模拟
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        
        // 设置查询返回少于5个可疑举报
        $reports = [new Report(), new Report()];
        
        $query->method('getResult')->willReturn($reports);
        
        // 配置查询构建器行为
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);
        
        // 设置repository的期望行为
        $this->reportRepository->method('createQueryBuilder')
            ->willReturn($qb);
            
        // 执行方法
        $result = $this->service->checkMaliciousReporting($mockUser);
        
        // 断言结果
        $this->assertFalse($result);
    }
} 