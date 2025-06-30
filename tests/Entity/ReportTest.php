<?php

namespace AIContentAuditBundle\Tests\Entity;

use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Entity\Report;
use AIContentAuditBundle\Enum\ProcessStatus;
use PHPUnit\Framework\TestCase;

class ReportTest extends TestCase
{
    private Report $report;

    protected function setUp(): void
    {
        $this->report = new Report();
    }

    /**
     * @dataProvider provideReporterUserData
     */
    public function testReporterUserAccessors(int|string|null $user): void
    {
        $this->report->setReporterUser($user);
        $this->assertSame($user, $this->report->getReporterUser());
    }
    
    public function provideReporterUserData(): array
    {
        return [
            'string user id' => ['test_user'],
            'numeric string user id' => ['test_user_123'],
            'integer user id' => [789],
            'null user' => [null],
        ];
    }
    
    /**
     * @dataProvider provideReportedContentData
     */
    public function testReportedContentAccessors(GeneratedContent $content): void
    {
        $this->report->setReportedContent($content);
        $this->assertSame($content, $this->report->getReportedContent());
    }
    
    public function provideReportedContentData(): array
    {
        $content = $this->createMock(GeneratedContent::class);
        
        return [
            'normal content' => [$content],
        ];
    }
    
    public function testReportTimeAccessors(): void
    {
        $reportTime = new \DateTimeImmutable('-1 hour');
        $this->report->setReportTime($reportTime);
        $this->assertEquals($reportTime, $this->report->getReportTime());
    }
    
    /**
     * @dataProvider provideReportReasonData
     */
    public function testReportReasonAccessors(string $reason): void
    {
        $this->report->setReportReason($reason);
        $this->assertEquals($reason, $this->report->getReportReason());
    }
    
    public function provideReportReasonData(): array
    {
        return [
            'empty reason' => [''],
            'spam reason' => ['该内容是垃圾广告'],
            'inappropriate reason' => ['该内容含有不适当的内容'],
            'harmful reason' => ['该内容可能对他人造成伤害'],
            'illegal reason' => ['该内容涉嫌违法'],
        ];
    }
    
    /**
     * @dataProvider provideProcessStatusData
     */
    public function testProcessStatusAccessors(ProcessStatus $status): void
    {
        $this->report->setProcessStatus($status);
        $this->assertEquals($status, $this->report->getProcessStatus());
    }
    
    public function provideProcessStatusData(): array
    {
        return [
            'pending status' => [ProcessStatus::PENDING],
            'processing status' => [ProcessStatus::PROCESSING],
            'completed status' => [ProcessStatus::COMPLETED],
        ];
    }
    
    /**
     * @dataProvider provideProcessTimeData
     */
    public function testProcessTimeAccessors(?\DateTimeImmutable $time): void
    {
        $this->report->setProcessTime($time);
        $this->assertEquals($time, $this->report->getProcessTime());
    }
    
    public function provideProcessTimeData(): array
    {
        return [
            'null time' => [null],
            'current time' => [new \DateTimeImmutable()],
            'past time' => [new \DateTimeImmutable('-30 minutes')],
        ];
    }
    
    /**
     * @dataProvider provideProcessResultData
     */
    public function testProcessResultAccessors(?string $result): void
    {
        $this->report->setProcessResult($result);
        $this->assertEquals($result, $this->report->getProcessResult());
    }
    
    public function provideProcessResultData(): array
    {
        return [
            'null result' => [null],
            'empty result' => [''],
            'approved result' => ['举报已审核，内容已删除'],
            'rejected result' => ['举报已审核，内容符合规范'],
            'pending result' => ['举报已收到，正在处理中'],
        ];
    }
    
    public function testConstructor(): void
    {
        $report = new Report();
        
        $this->assertInstanceOf(\DateTimeImmutable::class, $report->getReportTime());
        $this->assertEquals(ProcessStatus::PENDING, $report->getProcessStatus());
    }
    
    public function testToString(): void
    {
        $report = new Report();
        $report->setReporterUser('test_user_123');
        
        // 反射设置ID
        $reflectionClass = new \ReflectionClass($report);
        $idProperty = $reflectionClass->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($report, 123);
        
        // 只测试字符串转换方法被调用不会报错
        $result = (string)$report;
        $this->assertStringContainsString('123', $result);
        $this->assertStringContainsString('test_user_123', $result);
    }
    
    public function testIsProcessed(): void
    {
        // 初始状态应该是待处理
        $this->assertFalse($this->report->isProcessed());
        
        // 设置为处理中
        $this->report->setProcessStatus(ProcessStatus::PROCESSING);
        $this->assertFalse($this->report->isProcessed());
        
        // 设置为已处理
        $this->report->setProcessStatus(ProcessStatus::COMPLETED);
        $this->assertTrue($this->report->isProcessed());
    }
    
    public function testIsPending(): void
    {
        // 初始状态应该是待处理
        $this->assertTrue($this->report->isPending());
        
        // 设置为处理中
        $this->report->setProcessStatus(ProcessStatus::PROCESSING);
        $this->assertFalse($this->report->isPending());
        
        // 设置为已处理
        $this->report->setProcessStatus(ProcessStatus::COMPLETED);
        $this->assertFalse($this->report->isPending());
    }
} 