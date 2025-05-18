<?php

namespace AIContentAuditBundle\Tests\Enum;

use AIContentAuditBundle\Enum\ViolationType;
use PHPUnit\Framework\TestCase;

class ViolationTypeTest extends TestCase
{
    /**
     * @dataProvider provideViolationTypeLabelData
     */
    public function testGetLabel(ViolationType $type, string $expectedLabel): void
    {
        $this->assertEquals($expectedLabel, $type->getLabel());
    }
    
    public function provideViolationTypeLabelData(): array
    {
        return [
            'machine high risk' => [ViolationType::MACHINE_HIGH_RISK, '机器识别高风险内容'],
            'manual delete' => [ViolationType::MANUAL_DELETE, '人工审核删除'],
            'user report' => [ViolationType::USER_REPORT, '用户举报'],
            'repeated violation' => [ViolationType::REPEATED_VIOLATION, '重复违规'],
        ];
    }
    
    /**
     * 测试所有违规类型的值是否符合预期
     */
    public function testEnumValues(): void
    {
        $this->assertEquals('机器识别高风险内容', ViolationType::MACHINE_HIGH_RISK->value);
        $this->assertEquals('人工审核删除', ViolationType::MANUAL_DELETE->value);
        $this->assertEquals('用户举报', ViolationType::USER_REPORT->value);
        $this->assertEquals('重复违规', ViolationType::REPEATED_VIOLATION->value);
    }
    
    /**
     * 测试枚举实例的不可变性
     */
    public function testEnumImmutability(): void
    {
        $type1 = ViolationType::MACHINE_HIGH_RISK;
        $type2 = ViolationType::MACHINE_HIGH_RISK;
        
        // 两个同名枚举实例应该是同一个对象
        $this->assertSame($type1, $type2);
    }
    
    /**
     * 测试枚举值的唯一性
     */
    public function testEnumUniqueness(): void
    {
        $values = array_map(fn($case) => $case->value, ViolationType::cases());
        $uniqueValues = array_unique($values);
        
        $this->assertCount(count(ViolationType::cases()), $uniqueValues, '枚举值应该是唯一的');
    }
    
    /**
     * 测试枚举的完整性
     */
    public function testEnumCompleteness(): void
    {
        // 确保所有违规类型枚举都被测试到
        $this->assertCount(4, ViolationType::cases(), '违规类型枚举应该有4个值');
        
        // 验证每个枚举值都有相应的标签方法实现
        foreach (ViolationType::cases() as $case) {
            $this->assertNotEmpty($case->getLabel(), "枚举值 {$case->name} 应该有一个非空的标签");
            $this->assertEquals($case->value, $case->getLabel(), "枚举值 {$case->name} 的值应该与其标签相同");
        }
    }
} 