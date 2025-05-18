<?php

namespace AIContentAuditBundle\Tests\Enum;

use AIContentAuditBundle\Enum\AuditResult;
use PHPUnit\Framework\TestCase;

class AuditResultTest extends TestCase
{
    /**
     * @dataProvider provideAuditResultLabelData
     */
    public function testGetLabel(AuditResult $auditResult, string $expectedLabel): void
    {
        $this->assertEquals($expectedLabel, $auditResult->getLabel());
    }
    
    public function provideAuditResultLabelData(): array
    {
        return [
            'pass' => [AuditResult::PASS, '通过'],
            'modify' => [AuditResult::MODIFY, '修改'],
            'delete' => [AuditResult::DELETE, '删除'],
        ];
    }
    
    /**
     * @dataProvider provideAuditResultStyleData
     */
    public function testGetStyle(AuditResult $auditResult, string $expectedStyle): void
    {
        $this->assertEquals($expectedStyle, $auditResult->getStyle());
    }
    
    public function provideAuditResultStyleData(): array
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
} 