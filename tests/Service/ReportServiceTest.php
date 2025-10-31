<?php

namespace AIContentAuditBundle\Tests\Service;

use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Entity\Report;
use AIContentAuditBundle\Enum\ProcessStatus;
use AIContentAuditBundle\Enum\RiskLevel;
use AIContentAuditBundle\Repository\ReportRepository;
use AIContentAuditBundle\Service\ReportService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(ReportService::class)]
#[RunTestsInSeparateProcesses]
final class ReportServiceTest extends AbstractIntegrationTestCase
{
    private ReportService $service;

    protected function onSetUp(): void
    {
        $this->service = self::getService(ReportService::class);
    }

    public function testServiceExists(): void
    {
        // 验证服务可以正确获取
        $this->assertInstanceOf(ReportService::class, $this->service);
    }

    public function testCreateReport(): void
    {
        // 创建测试数据
        $content = new GeneratedContent();
        $content->setUser('test_user');
        $content->setInputText('Test input');
        $content->setOutputText('Test output');
        $content->setMachineAuditResult(RiskLevel::NO_RISK);
        $content->setMachineAuditTime(new \DateTimeImmutable());
        self::getEntityManager()->persist($content);
        self::getEntityManager()->flush();

        // 创建报告
        $report = new Report();
        $report->setReporterUser('test_reporter');
        $report->setReportedContent($content);
        $report->setReportReason('Test reason');
        $report->setProcessStatus(ProcessStatus::PENDING);
        $report->setReportTime(new \DateTimeImmutable());
        self::getEntityManager()->persist($report);
        self::getEntityManager()->flush();

        // 验证报告创建成功
        $this->assertInstanceOf(Report::class, $report);
        $this->assertEquals($content, $report->getReportedContent());
        $this->assertEquals(ProcessStatus::PENDING, $report->getProcessStatus());

        // 验证数据库中保存了报告
        $savedReport = self::getService(ReportRepository::class)
            ->findOneBy(['reporterUser' => 'test_reporter'])
        ;
        $this->assertNotNull($savedReport);
        $this->assertEquals(ProcessStatus::PENDING, $savedReport->getProcessStatus());
    }

    public function testCheckMaliciousReporting(): void
    {
        // 创建测试内容
        $content = new GeneratedContent();
        $content->setUser('content_user');
        $content->setInputText('Test input');
        $content->setOutputText('Test output');
        $content->setMachineAuditResult(RiskLevel::NO_RISK);
        $content->setMachineAuditTime(new \DateTimeImmutable());
        self::getEntityManager()->persist($content);
        self::getEntityManager()->flush();

        // 创建多个不属实的举报记录
        for ($i = 0; $i < 6; ++$i) {
            $report = new Report();
            $report->setReporterUser('malicious_user');
            $report->setReportedContent($content);
            $report->setReportReason('False report ' . $i);
            $report->setProcessStatus(ProcessStatus::COMPLETED);
            $report->setProcessResult('处理结果：不属实');
            $report->setReportTime(new \DateTimeImmutable('-' . (20 + $i) . ' days'));
            $report->setProcessTime(new \DateTimeImmutable('-' . (15 + $i) . ' days'));
            self::getEntityManager()->persist($report);
        }
        self::getEntityManager()->flush();

        // 测试检查恶意举报
        $isMalicious = $this->service->checkMaliciousReporting('malicious_user');
        $this->assertTrue($isMalicious);

        // 测试正常用户
        $isNormalMalicious = $this->service->checkMaliciousReporting('normal_user');
        $this->assertFalse($isNormalMalicious);
    }

    public function testCompleteProcessing(): void
    {
        // 创建测试数据
        $content = new GeneratedContent();
        $content->setUser('test_user');
        $content->setInputText('Test input');
        $content->setOutputText('Test output');
        $content->setMachineAuditResult(RiskLevel::NO_RISK);
        $content->setMachineAuditTime(new \DateTimeImmutable());
        self::getEntityManager()->persist($content);

        $report = new Report();
        $report->setReporterUser('test_reporter');
        $report->setReportedContent($content);
        $report->setReportReason('Test reason');
        $report->setProcessStatus(ProcessStatus::PROCESSING);
        self::getEntityManager()->persist($report);
        self::getEntityManager()->flush();

        // 执行完成处理
        $result = $this->service->completeProcessing($report, 'Processed successfully', 'admin_user');

        // 断言结果
        $this->assertEquals(ProcessStatus::COMPLETED, $result->getProcessStatus());
        $this->assertEquals('Processed successfully', $result->getProcessResult());
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->getProcessTime());
    }

    public function testFindPendingReports(): void
    {
        // 清理现有的举报数据
        $this->clearReportData();

        // 创建测试内容
        $content = new GeneratedContent();
        $content->setUser('test_user');
        $content->setInputText('Test input');
        $content->setOutputText('Test output');
        $content->setMachineAuditResult(RiskLevel::NO_RISK);
        $content->setMachineAuditTime(new \DateTimeImmutable());
        self::getEntityManager()->persist($content);

        // 创建待处理的举报
        $pendingReport = new Report();
        $pendingReport->setReporterUser('test_reporter1');
        $pendingReport->setReportedContent($content);
        $pendingReport->setReportReason('Pending report');
        $pendingReport->setProcessStatus(ProcessStatus::PENDING);
        self::getEntityManager()->persist($pendingReport);

        // 创建已处理的举报
        $completedReport = new Report();
        $completedReport->setReporterUser('test_reporter2');
        $completedReport->setReportedContent($content);
        $completedReport->setReportReason('Completed report');
        $completedReport->setProcessStatus(ProcessStatus::COMPLETED);
        self::getEntityManager()->persist($completedReport);

        self::getEntityManager()->flush();

        // 执行查找待处理举报
        $pendingReports = $this->service->findPendingReports();

        // 断言结果
        $this->assertCount(1, $pendingReports);
        $this->assertEquals(ProcessStatus::PENDING, $pendingReports[0]->getProcessStatus());
        $this->assertEquals('test_reporter1', $pendingReports[0]->getReporterUser());
    }

    private function clearReportData(): void
    {
        // 清理所有举报数据
        self::getEntityManager()->createQuery('DELETE FROM AIContentAuditBundle\Entity\Report')->execute();
        self::getEntityManager()->createQuery('DELETE FROM AIContentAuditBundle\Entity\GeneratedContent')->execute();
        self::getEntityManager()->flush();
    }

    public function testFindReport(): void
    {
        // 创建测试内容
        $content = new GeneratedContent();
        $content->setUser('test_user');
        $content->setInputText('Test input');
        $content->setOutputText('Test output');
        $content->setMachineAuditResult(RiskLevel::NO_RISK);
        $content->setMachineAuditTime(new \DateTimeImmutable());
        self::getEntityManager()->persist($content);

        $report = new Report();
        $report->setReporterUser('test_reporter');
        $report->setReportedContent($content);
        $report->setReportReason('Test reason');
        $report->setProcessStatus(ProcessStatus::PENDING);
        self::getEntityManager()->persist($report);
        self::getEntityManager()->flush();

        $reportId = $report->getId();
        $this->assertNotNull($reportId);

        // 执行查找举报
        $foundReport = $this->service->findReport($reportId);

        // 断言结果
        $this->assertNotNull($foundReport);
        $this->assertEquals($reportId, $foundReport->getId());
        $this->assertEquals('test_reporter', $foundReport->getReporterUser());
        $this->assertEquals('Test reason', $foundReport->getReportReason());

        // 测试不存在的ID
        $notFoundReport = $this->service->findReport(999999);
        $this->assertNull($notFoundReport);
    }

    public function testFindReportsByContent(): void
    {
        // 创建测试内容
        $content1 = new GeneratedContent();
        $content1->setUser('test_user1');
        $content1->setInputText('Test input 1');
        $content1->setOutputText('Test output 1');
        $content1->setMachineAuditResult(RiskLevel::NO_RISK);
        $content1->setMachineAuditTime(new \DateTimeImmutable());
        self::getEntityManager()->persist($content1);

        $content2 = new GeneratedContent();
        $content2->setUser('test_user2');
        $content2->setInputText('Test input 2');
        $content2->setOutputText('Test output 2');
        $content2->setMachineAuditResult(RiskLevel::NO_RISK);
        $content2->setMachineAuditTime(new \DateTimeImmutable());
        self::getEntityManager()->persist($content2);

        // 为内容1创建2个举报
        $report1 = new Report();
        $report1->setReporterUser('reporter1');
        $report1->setReportedContent($content1);
        $report1->setReportReason('Report 1');
        $report1->setProcessStatus(ProcessStatus::PENDING);
        self::getEntityManager()->persist($report1);

        $report2 = new Report();
        $report2->setReporterUser('reporter2');
        $report2->setReportedContent($content1);
        $report2->setReportReason('Report 2');
        $report2->setProcessStatus(ProcessStatus::PENDING);
        self::getEntityManager()->persist($report2);

        // 为内容2创建1个举报
        $report3 = new Report();
        $report3->setReporterUser('reporter3');
        $report3->setReportedContent($content2);
        $report3->setReportReason('Report 3');
        $report3->setProcessStatus(ProcessStatus::PENDING);
        self::getEntityManager()->persist($report3);

        self::getEntityManager()->flush();

        // 执行查找内容1的举报
        $content1Reports = $this->service->findReportsByContent($content1);
        $this->assertCount(2, $content1Reports);

        // 执行查找内容2的举报
        $content2Reports = $this->service->findReportsByContent($content2);
        $this->assertCount(1, $content2Reports);
        $this->assertEquals('reporter3', $content2Reports[0]->getReporterUser());

        // 测试没有ID的内容
        $newContent = new GeneratedContent();
        $emptyReports = $this->service->findReportsByContent($newContent);
        $this->assertCount(0, $emptyReports);
    }

    public function testFindReportsByUser(): void
    {
        // 创建测试内容
        $content = new GeneratedContent();
        $content->setUser('test_user');
        $content->setInputText('Test input');
        $content->setOutputText('Test output');
        $content->setMachineAuditResult(RiskLevel::NO_RISK);
        $content->setMachineAuditTime(new \DateTimeImmutable());
        self::getEntityManager()->persist($content);

        // 为用户创建多个举报
        $report1 = new Report();
        $report1->setReporterUser('target_user');
        $report1->setReportedContent($content);
        $report1->setReportReason('User report 1');
        $report1->setProcessStatus(ProcessStatus::PENDING);
        self::getEntityManager()->persist($report1);

        $report2 = new Report();
        $report2->setReporterUser('target_user');
        $report2->setReportedContent($content);
        $report2->setReportReason('User report 2');
        $report2->setProcessStatus(ProcessStatus::COMPLETED);
        self::getEntityManager()->persist($report2);

        // 创建其他用户的举报
        $report3 = new Report();
        $report3->setReporterUser('other_user');
        $report3->setReportedContent($content);
        $report3->setReportReason('Other user report');
        $report3->setProcessStatus(ProcessStatus::PENDING);
        self::getEntityManager()->persist($report3);

        self::getEntityManager()->flush();

        // 执行查找特定用户的举报
        $userReports = $this->service->findReportsByUser('target_user');
        $this->assertCount(2, $userReports);

        // 验证都是目标用户的举报
        foreach ($userReports as $report) {
            $this->assertEquals('target_user', $report->getReporterUser());
        }

        // 测试不存在的用户
        $noReports = $this->service->findReportsByUser('nonexistent_user');
        $this->assertCount(0, $noReports);
    }

    public function testProcessReport(): void
    {
        // 创建测试数据
        $content = new GeneratedContent();
        $content->setUser('test_user');
        $content->setInputText('Test input');
        $content->setOutputText('Test output');
        $content->setMachineAuditResult(RiskLevel::NO_RISK);
        $content->setMachineAuditTime(new \DateTimeImmutable());
        self::getEntityManager()->persist($content);

        $report = new Report();
        $report->setReporterUser('test_reporter');
        $report->setReportedContent($content);
        $report->setReportReason('Test reason');
        $report->setProcessStatus(ProcessStatus::PENDING);
        self::getEntityManager()->persist($report);
        self::getEntityManager()->flush();

        // 执行处理举报
        $result = $this->service->processReport($report, 'Report processed', 'admin_user');

        // 断言结果
        $this->assertEquals(ProcessStatus::COMPLETED, $result->getProcessStatus());
        $this->assertEquals('Report processed', $result->getProcessResult());
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->getProcessTime());
    }

    public function testStartProcessing(): void
    {
        // 创建测试数据
        $content = new GeneratedContent();
        $content->setUser('test_user');
        $content->setInputText('Test input');
        $content->setOutputText('Test output');
        $content->setMachineAuditResult(RiskLevel::NO_RISK);
        $content->setMachineAuditTime(new \DateTimeImmutable());
        self::getEntityManager()->persist($content);

        $report = new Report();
        $report->setReporterUser('test_reporter');
        $report->setReportedContent($content);
        $report->setReportReason('Test reason');
        $report->setProcessStatus(ProcessStatus::PENDING);
        self::getEntityManager()->persist($report);
        self::getEntityManager()->flush();

        // 执行开始处理
        $result = $this->service->startProcessing($report, 'admin_user');

        // 断言结果
        $this->assertEquals(ProcessStatus::PROCESSING, $result->getProcessStatus());

        // 测试对非待审核状态的举报进行处理
        $result2 = $this->service->startProcessing($report, 'admin_user');
        $this->assertEquals(ProcessStatus::PROCESSING, $result2->getProcessStatus());
    }

    public function testSubmitReport(): void
    {
        // 创建测试内容
        $content = new GeneratedContent();
        $content->setUser('content_user');
        $content->setInputText('Reported content input');
        $content->setOutputText('Reported content output');
        $content->setMachineAuditResult(RiskLevel::NO_RISK);
        $content->setMachineAuditTime(new \DateTimeImmutable());
        self::getEntityManager()->persist($content);
        self::getEntityManager()->flush();

        // 执行提交举报
        $result = $this->service->submitReport($content, 'reporting_user', 'This content is inappropriate');

        // 断言结果
        $this->assertInstanceOf(Report::class, $result);
        $this->assertEquals($content, $result->getReportedContent());
        $this->assertEquals('reporting_user', $result->getReporterUser());
        $this->assertEquals('This content is inappropriate', $result->getReportReason());
        $this->assertEquals(ProcessStatus::PENDING, $result->getProcessStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->getReportTime());
        $this->assertNotNull($result->getId());

        // 验证数据库中保存了举报
        $savedReport = self::getService(ReportRepository::class)
            ->findOneBy(['reporterUser' => 'reporting_user'])
        ;
        $this->assertNotNull($savedReport);
        $this->assertEquals(ProcessStatus::PENDING, $savedReport->getProcessStatus());
    }
}
