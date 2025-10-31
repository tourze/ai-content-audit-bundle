<?php

namespace AIContentAuditBundle\Tests\Entity;

use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Entity\Report;
use AIContentAuditBundle\Enum\ProcessStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(Report::class)]
final class ReportTest extends AbstractEntityTestCase
{
    protected function createEntity(): Report
    {
        return new Report();
    }

    /**
     * @return array<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): array
    {
        return [
            'reporterUser' => ['reporterUser', 'test_user'],
            'reportedContent' => ['reportedContent', new GeneratedContent()],
            'reportReason' => ['reportReason', 'Test reason'],
            'processStatus' => ['processStatus', ProcessStatus::PENDING],
            'processTime' => ['processTime', new \DateTimeImmutable()],
            'processResult' => ['processResult', 'Test result'],
        ];
    }

    #[DataProvider('provideReporterUserData')]
    public function testReporterUserAccessors(int|string|null $user): void
    {
        $entity = $this->createEntity();
        $entity->setReporterUser($user);
        $this->assertSame($user, $entity->getReporterUser());
    }

    /**
     * @return array<string, array{0: int|string|null}>
     */
    public static function provideReporterUserData(): array
    {
        return [
            'string user id' => ['test_user'],
            'numeric string user id' => ['test_user_123'],
            'integer user id' => [789],
            'null user' => [null],
        ];
    }

    #[DataProvider('provideReportedContentData')]
    public function testReportedContentAccessors(GeneratedContent $content): void
    {
        $entity = $this->createEntity();
        $entity->setReportedContent($content);
        $this->assertSame($content, $entity->getReportedContent());
    }

    /**
     * @return array<string, array{0: GeneratedContent}>
     */
    public static function provideReportedContentData(): array
    {
        $content = new GeneratedContent();

        return [
            'normal content' => [$content],
        ];
    }

    public function testReportTimeAccessors(): void
    {
        $entity = $this->createEntity();
        $reportTime = new \DateTimeImmutable('-1 hour');
        $entity->setReportTime($reportTime);
        $this->assertEquals($reportTime, $entity->getReportTime());
    }

    #[DataProvider('provideReportReasonData')]
    public function testReportReasonAccessors(string $reason): void
    {
        $entity = $this->createEntity();
        $entity->setReportReason($reason);
        $this->assertEquals($reason, $entity->getReportReason());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function provideReportReasonData(): array
    {
        return [
            'empty reason' => [''],
            'spam reason' => ['该内容是垃圾广告'],
            'inappropriate reason' => ['该内容含有不适当的内容'],
            'harmful reason' => ['该内容可能对他人造成伤害'],
            'illegal reason' => ['该内容涉嫌违法'],
        ];
    }

    #[DataProvider('provideProcessStatusData')]
    public function testProcessStatusAccessors(ProcessStatus $status): void
    {
        $entity = $this->createEntity();
        $entity->setProcessStatus($status);
        $this->assertEquals($status, $entity->getProcessStatus());
    }

    /**
     * @return array<string, array{0: ProcessStatus}>
     */
    public static function provideProcessStatusData(): array
    {
        return [
            'pending status' => [ProcessStatus::PENDING],
            'processing status' => [ProcessStatus::PROCESSING],
            'completed status' => [ProcessStatus::COMPLETED],
        ];
    }

    #[DataProvider('provideProcessTimeData')]
    public function testProcessTimeAccessors(?\DateTimeImmutable $time): void
    {
        $entity = $this->createEntity();
        $entity->setProcessTime($time);
        $this->assertEquals($time, $entity->getProcessTime());
    }

    /**
     * @return array<string, array{0: \DateTimeImmutable|null}>
     */
    public static function provideProcessTimeData(): array
    {
        return [
            'null time' => [null],
            'current time' => [new \DateTimeImmutable()],
            'past time' => [new \DateTimeImmutable('-30 minutes')],
        ];
    }

    #[DataProvider('provideProcessResultData')]
    public function testProcessResultAccessors(?string $result): void
    {
        $entity = $this->createEntity();
        $entity->setProcessResult($result);
        $this->assertEquals($result, $entity->getProcessResult());
    }

    /**
     * @return array<string, array{0: string|null}>
     */
    public static function provideProcessResultData(): array
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
        $result = (string) $report;
        $this->assertStringContainsString('123', $result);
        $this->assertStringContainsString('test_user_123', $result);
    }

    public function testIsProcessed(): void
    {
        $entity = $this->createEntity();

        // 初始状态应该是待处理
        $this->assertFalse($entity->isProcessed());

        // 设置为处理中
        $entity->setProcessStatus(ProcessStatus::PROCESSING);
        $this->assertFalse($entity->isProcessed());

        // 设置为已处理
        $entity->setProcessStatus(ProcessStatus::COMPLETED);
        $this->assertTrue($entity->isProcessed());
    }

    public function testIsPending(): void
    {
        $entity = $this->createEntity();

        // 初始状态应该是待处理
        $this->assertTrue($entity->isPending());

        // 设置为处理中
        $entity->setProcessStatus(ProcessStatus::PROCESSING);
        $this->assertFalse($entity->isPending());

        // 设置为已处理
        $entity->setProcessStatus(ProcessStatus::COMPLETED);
        $this->assertFalse($entity->isPending());
    }
}
