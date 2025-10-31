<?php

namespace AIContentAuditBundle\Tests\Entity;

use AIContentAuditBundle\Entity\ViolationRecord;
use AIContentAuditBundle\Enum\ViolationType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(ViolationRecord::class)]
final class ViolationRecordTest extends AbstractEntityTestCase
{
    protected function createEntity(): ViolationRecord
    {
        return new ViolationRecord();
    }

    /**
     * @return array<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): array
    {
        return [
            'user' => ['user', 'test_user'],
            'violationTime' => ['violationTime', new \DateTimeImmutable()],
            'violationContent' => ['violationContent', 'test content'],
            'violationType' => ['violationType', ViolationType::MACHINE_HIGH_RISK],
            'processResult' => ['processResult', 'test result'],
            'processTime' => ['processTime', new \DateTimeImmutable()],
            'processedBy' => ['processedBy', 'test_processor'],
        ];
    }

    #[DataProvider('provideUserData')]
    public function testUserAccessors(int|string|null $user): void
    {
        $entity = $this->createEntity();
        $entity->setUser($user);
        $this->assertSame($user, $entity->getUser());
    }

    /**
     * @return array<string, array{0: int|string|null}>
     */
    public static function provideUserData(): array
    {
        return [
            'string user id' => ['test_user'],
            'numeric string user id' => ['456'],
            'integer user id' => [123],
            'null user' => [null],
        ];
    }

    #[DataProvider('provideViolationTimeData')]
    public function testViolationTimeAccessors(\DateTimeImmutable $time): void
    {
        $entity = $this->createEntity();
        $entity->setViolationTime($time);
        $this->assertEquals($time, $entity->getViolationTime());
    }

    /**
     * @return array<string, array{0: \DateTimeImmutable}>
     */
    public static function provideViolationTimeData(): array
    {
        return [
            'current time' => [new \DateTimeImmutable()],
            'past time' => [new \DateTimeImmutable('-1 day')],
        ];
    }

    #[DataProvider('provideViolationContentData')]
    public function testViolationContentAccessors(string $content): void
    {
        $entity = $this->createEntity();
        $entity->setViolationContent($content);
        $this->assertEquals($content, $entity->getViolationContent());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function provideViolationContentData(): array
    {
        return [
            'empty content' => [''],
            'simple content' => ['This is a violation content'],
            'content with special chars' => ['Special chars: !@#$%^&*()'],
            'multilanguage content' => ['English 中文 Español'],
        ];
    }

    #[DataProvider('provideViolationTypeData')]
    public function testViolationTypeAccessors(ViolationType $type): void
    {
        $entity = $this->createEntity();
        $entity->setViolationType($type);
        $this->assertEquals($type, $entity->getViolationType());
    }

    /**
     * @return array<string, array{0: ViolationType}>
     */
    public static function provideViolationTypeData(): array
    {
        return [
            'machine high risk' => [ViolationType::MACHINE_HIGH_RISK],
            'manual delete' => [ViolationType::MANUAL_DELETE],
            'user report' => [ViolationType::USER_REPORT],
            'repeated violation' => [ViolationType::REPEATED_VIOLATION],
        ];
    }

    #[DataProvider('provideProcessResultData')]
    public function testProcessResultAccessors(string $result): void
    {
        $entity = $this->createEntity();
        $entity->setProcessResult($result);
        $this->assertEquals($result, $entity->getProcessResult());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function provideProcessResultData(): array
    {
        return [
            'empty result' => [''],
            'content deleted' => ['内容已删除'],
            'user warning' => ['用户已警告'],
            'account disabled' => ['账号已禁用'],
        ];
    }

    #[DataProvider('provideProcessTimeData')]
    public function testProcessTimeAccessors(\DateTimeImmutable $time): void
    {
        $entity = $this->createEntity();
        $entity->setProcessTime($time);
        $this->assertEquals($time, $entity->getProcessTime());
    }

    /**
     * @return array<string, array{0: \DateTimeImmutable}>
     */
    public static function provideProcessTimeData(): array
    {
        return [
            'current time' => [new \DateTimeImmutable()],
            'past time' => [new \DateTimeImmutable('-1 hour')],
        ];
    }

    #[DataProvider('provideProcessedByData')]
    public function testProcessedByAccessors(string $processor): void
    {
        $entity = $this->createEntity();
        $entity->setProcessedBy($processor);
        $this->assertEquals($processor, $entity->getProcessedBy());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function provideProcessedByData(): array
    {
        return [
            'admin user' => ['admin'],
            'system' => ['系统'],
            'moderator' => ['moderator1'],
        ];
    }

    public function testConstructor(): void
    {
        $violationRecord = new ViolationRecord();

        $this->assertInstanceOf(\DateTimeImmutable::class, $violationRecord->getViolationTime());
        $this->assertInstanceOf(\DateTimeImmutable::class, $violationRecord->getProcessTime());
    }

    public function testToString(): void
    {
        $violationRecord = new ViolationRecord();
        $violationRecord->setUser('test_user_789');
        $violationRecord->setViolationType(ViolationType::MANUAL_DELETE);

        // 反射设置ID
        $reflectionClass = new \ReflectionClass($violationRecord);
        $idProperty = $reflectionClass->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($violationRecord, 123);

        // 只测试字符串转换方法被调用不会报错
        $stringResult = (string) $violationRecord;
        $this->assertStringContainsString('123', $stringResult);
        $this->assertStringContainsString('人工审核删除', $stringResult);
        $this->assertStringContainsString('test_user_789', $stringResult);
    }
}
