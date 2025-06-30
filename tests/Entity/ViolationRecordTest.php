<?php

namespace AIContentAuditBundle\Tests\Entity;

use AIContentAuditBundle\Entity\ViolationRecord;
use AIContentAuditBundle\Enum\ViolationType;
use PHPUnit\Framework\TestCase;

class ViolationRecordTest extends TestCase
{
    private ViolationRecord $violationRecord;

    protected function setUp(): void
    {
        $this->violationRecord = new ViolationRecord();
    }

    /**
     * @dataProvider provideUserData
     */
    public function testUserAccessors($user): void
    {
        $this->violationRecord->setUser($user);
        $this->assertSame($user, $this->violationRecord->getUser());
    }
    
    public function provideUserData(): array
    {
        return [
            'string user id' => ['test_user'],
            'numeric string user id' => ['456'],
            'integer user id' => [123],
            'null user' => [null],
        ];
    }
    
    /**
     * @dataProvider provideViolationTimeData
     */
    public function testViolationTimeAccessors(\DateTimeImmutable $time): void
    {
        $this->violationRecord->setViolationTime($time);
        $this->assertEquals($time, $this->violationRecord->getViolationTime());
    }
    
    public function provideViolationTimeData(): array
    {
        return [
            'current time' => [new \DateTimeImmutable()],
            'past time' => [new \DateTimeImmutable('-1 day')],
        ];
    }
    
    /**
     * @dataProvider provideViolationContentData
     */
    public function testViolationContentAccessors(string $content): void
    {
        $this->violationRecord->setViolationContent($content);
        $this->assertEquals($content, $this->violationRecord->getViolationContent());
    }
    
    public function provideViolationContentData(): array
    {
        return [
            'empty content' => [''],
            'simple content' => ['This is a violation content'],
            'content with special chars' => ['Special chars: !@#$%^&*()'],
            'multilanguage content' => ['English 中文 Español'],
        ];
    }
    
    /**
     * @dataProvider provideViolationTypeData
     */
    public function testViolationTypeAccessors(ViolationType $type): void
    {
        $this->violationRecord->setViolationType($type);
        $this->assertEquals($type, $this->violationRecord->getViolationType());
    }
    
    public function provideViolationTypeData(): array
    {
        return [
            'machine high risk' => [ViolationType::MACHINE_HIGH_RISK],
            'manual delete' => [ViolationType::MANUAL_DELETE],
            'user report' => [ViolationType::USER_REPORT],
            'repeated violation' => [ViolationType::REPEATED_VIOLATION],
        ];
    }
    
    /**
     * @dataProvider provideProcessResultData
     */
    public function testProcessResultAccessors(string $result): void
    {
        $this->violationRecord->setProcessResult($result);
        $this->assertEquals($result, $this->violationRecord->getProcessResult());
    }
    
    public function provideProcessResultData(): array
    {
        return [
            'empty result' => [''],
            'content deleted' => ['内容已删除'],
            'user warning' => ['用户已警告'],
            'account disabled' => ['账号已禁用'],
        ];
    }
    
    /**
     * @dataProvider provideProcessTimeData
     */
    public function testProcessTimeAccessors(\DateTimeImmutable $time): void
    {
        $this->violationRecord->setProcessTime($time);
        $this->assertEquals($time, $this->violationRecord->getProcessTime());
    }
    
    public function provideProcessTimeData(): array
    {
        return [
            'current time' => [new \DateTimeImmutable()],
            'past time' => [new \DateTimeImmutable('-1 hour')],
        ];
    }
    
    /**
     * @dataProvider provideProcessedByData
     */
    public function testProcessedByAccessors(string $processor): void
    {
        $this->violationRecord->setProcessedBy($processor);
        $this->assertEquals($processor, $this->violationRecord->getProcessedBy());
    }
    
    public function provideProcessedByData(): array
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
        $stringResult = (string)$violationRecord;
        $this->assertStringContainsString('123', $stringResult);
        $this->assertStringContainsString('人工审核删除', $stringResult);
        $this->assertStringContainsString('test_user_789', $stringResult);
    }
} 