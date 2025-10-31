<?php

namespace AIContentAuditBundle\Tests\Enum;

use AIContentAuditBundle\Enum\AuditResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(AuditResult::class)]
final class AuditResultTest extends AbstractEnumTestCase
{
    #[DataProvider('provideAuditResultLabelData')]
    public function testGetLabel(AuditResult $auditResult, string $expectedLabel): void
    {
        $this->assertEquals($expectedLabel, $auditResult->getLabel());
    }

    /**
     * @return array<string, array{0: AuditResult, 1: string}>
     */
    public static function provideAuditResultLabelData(): array
    {
        return [
            'pass' => [AuditResult::PASS, '通过'],
            'modify' => [AuditResult::MODIFY, '修改'],
            'delete' => [AuditResult::DELETE, '删除'],
        ];
    }

    #[DataProvider('provideAuditResultStyleData')]
    public function testGetStyle(AuditResult $auditResult, string $expectedStyle): void
    {
        $this->assertEquals($expectedStyle, $auditResult->getStyle());
    }

    /**
     * @return array<string, array{0: AuditResult, 1: string}>
     */
    public static function provideAuditResultStyleData(): array
    {
        return [
            'pass' => [AuditResult::PASS, 'success'],
            'modify' => [AuditResult::MODIFY, 'warning'],
            'delete' => [AuditResult::DELETE, 'danger'],
        ];
    }

    /**
     * 测试所有审核结果的值是否符合预期
     */
    public function testEnumValues(): void
    {
        $this->assertEquals('通过', AuditResult::PASS->value);
        $this->assertEquals('修改', AuditResult::MODIFY->value);
        $this->assertEquals('删除', AuditResult::DELETE->value);
    }

    /**
     * 测试toArray方法返回所有枚举值的数组
     */
    public function testToArray(): void
    {
        $expected = [
            'value' => '通过',
            'label' => '通过',
        ];

        $this->assertEquals($expected, AuditResult::PASS->toArray());
    }
}
