<?php

namespace AIContentAuditBundle\Tests\Enum;

use AIContentAuditBundle\Enum\ProcessStatus;
use PHPUnit\Framework\TestCase;

class ProcessStatusTest extends TestCase
{
    /**
     * @dataProvider provideProcessStatusLabelData
     */
    public function testGetLabel(ProcessStatus $status, string $expectedLabel): void
    {
        $this->assertEquals($expectedLabel, $status->getLabel());
    }
    
    public function provideProcessStatusLabelData(): array
    {
        return [
            'pending' => [ProcessStatus::PENDING, '待审核'],
            'processing' => [ProcessStatus::PROCESSING, '审核中'],
            'completed' => [ProcessStatus::COMPLETED, '已处理'],
        ];
    }
    
    /**
     * @dataProvider provideProcessStatusStyleData
     */
    public function testGetStyle(ProcessStatus $status, string $expectedStyle): void
    {
        $this->assertEquals($expectedStyle, $status->getStyle());
    }
    
    public function provideProcessStatusStyleData(): array
    {
        return [
            'pending' => [ProcessStatus::PENDING, 'warning'],
            'processing' => [ProcessStatus::PROCESSING, 'info'],
            'completed' => [ProcessStatus::COMPLETED, 'success'],
        ];
    }
    
    /**
     * 测试所有处理状态的值是否符合预期
     */
    public function testEnumValues(): void
    {
        $this->assertEquals('待审核', ProcessStatus::PENDING->value);
        $this->assertEquals('审核中', ProcessStatus::PROCESSING->value);
        $this->assertEquals('已处理', ProcessStatus::COMPLETED->value);
    }
    
    /**
     * 测试枚举实例的不可变性
     */
    public function testEnumImmutability(): void
    {
        $pending1 = ProcessStatus::PENDING;
        $pending2 = ProcessStatus::PENDING;
        
        // 两个同名枚举实例应该是同一个对象
        $this->assertSame($pending1, $pending2);
    }
    
    /**
     * 测试枚举值的唯一性
     */
    public function testEnumUniqueness(): void
    {
        $values = array_map(fn($case) => $case->value, ProcessStatus::cases());
        $uniqueValues = array_unique($values);
        
        $this->assertCount(count(ProcessStatus::cases()), $uniqueValues, '枚举值应该是唯一的');
    }
} 