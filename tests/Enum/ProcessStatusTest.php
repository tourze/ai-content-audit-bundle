<?php

namespace AIContentAuditBundle\Tests\Enum;

use AIContentAuditBundle\Enum\ProcessStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(ProcessStatus::class)]
final class ProcessStatusTest extends AbstractEnumTestCase
{
    #[DataProvider('provideProcessStatusLabelData')]
    public function testGetLabel(ProcessStatus $status, string $expectedLabel): void
    {
        $this->assertEquals($expectedLabel, $status->getLabel());
    }

    /**
     * @return array<string, array{0: ProcessStatus, 1: string}>
     */
    public static function provideProcessStatusLabelData(): array
    {
        return [
            'pending' => [ProcessStatus::PENDING, '待审核'],
            'processing' => [ProcessStatus::PROCESSING, '审核中'],
            'completed' => [ProcessStatus::COMPLETED, '已处理'],
        ];
    }

    #[DataProvider('provideProcessStatusStyleData')]
    public function testGetStyle(ProcessStatus $status, string $expectedStyle): void
    {
        $this->assertEquals($expectedStyle, $status->getStyle());
    }

    /**
     * @return array<string, array{0: ProcessStatus, 1: string}>
     */
    public static function provideProcessStatusStyleData(): array
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

        // PHP 8.1 枚举是单例模式，同一个枚举值总是返回相同的实例
        // 这个测试验证了枚举的基本特性
        $this->assertEquals($pending1->value, $pending2->value);
    }

    /**
     * 测试枚举值的唯一性
     */
    public function testEnumUniqueness(): void
    {
        $values = array_map(fn ($case) => $case->value, ProcessStatus::cases());
        $uniqueValues = array_unique($values);

        $this->assertCount(count(ProcessStatus::cases()), $uniqueValues, '枚举值应该是唯一的');
    }

    /**
     * 测试toArray方法返回所有枚举值的数组
     */
    public function testToArray(): void
    {
        $expected = [
            'value' => '待审核',
            'label' => '待审核',
        ];

        $this->assertEquals($expected, ProcessStatus::PENDING->toArray());
    }
}
