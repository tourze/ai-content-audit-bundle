<?php

namespace AIContentAuditBundle\Tests\Enum;

use AIContentAuditBundle\Enum\RiskLevel;
use PHPUnit\Framework\TestCase;

class RiskLevelTest extends TestCase
{
    /**
     * @dataProvider provideRiskLevelLabelData
     */
    public function testGetLabel(RiskLevel $riskLevel, string $expectedLabel): void
    {
        $this->assertEquals($expectedLabel, $riskLevel->getLabel());
    }
    
    public function provideRiskLevelLabelData(): array
    {
        return [
            'no risk' => [RiskLevel::NO_RISK, '无风险'],
            'low risk' => [RiskLevel::LOW_RISK, '低风险'],
            'medium risk' => [RiskLevel::MEDIUM_RISK, '中风险'],
            'high risk' => [RiskLevel::HIGH_RISK, '高风险'],
        ];
    }
    
    /**
     * @dataProvider provideRiskLevelOrderData
     */
    public function testGetOrder(RiskLevel $riskLevel, int $expectedOrder): void
    {
        $this->assertEquals($expectedOrder, $riskLevel->getOrder());
    }
    
    public function provideRiskLevelOrderData(): array
    {
        return [
            'no risk' => [RiskLevel::NO_RISK, 0],
            'low risk' => [RiskLevel::LOW_RISK, 1],
            'medium risk' => [RiskLevel::MEDIUM_RISK, 2],
            'high risk' => [RiskLevel::HIGH_RISK, 3],
        ];
    }
    
    /**
     * @dataProvider provideRiskLevelComparisonData
     */
    public function testGetHigher(RiskLevel $riskLevel1, RiskLevel $riskLevel2, RiskLevel $expectedHigherLevel): void
    {
        $this->assertSame(
            $expectedHigherLevel, 
            RiskLevel::getHigher($riskLevel1, $riskLevel2)
        );
    }
    
    public function provideRiskLevelComparisonData(): array
    {
        return [
            'no risk vs no risk' => [RiskLevel::NO_RISK, RiskLevel::NO_RISK, RiskLevel::NO_RISK],
            'no risk vs low risk' => [RiskLevel::NO_RISK, RiskLevel::LOW_RISK, RiskLevel::LOW_RISK],
            'low risk vs no risk' => [RiskLevel::LOW_RISK, RiskLevel::NO_RISK, RiskLevel::LOW_RISK],
            'low risk vs medium risk' => [RiskLevel::LOW_RISK, RiskLevel::MEDIUM_RISK, RiskLevel::MEDIUM_RISK],
            'medium risk vs low risk' => [RiskLevel::MEDIUM_RISK, RiskLevel::LOW_RISK, RiskLevel::MEDIUM_RISK],
            'medium risk vs high risk' => [RiskLevel::MEDIUM_RISK, RiskLevel::HIGH_RISK, RiskLevel::HIGH_RISK],
            'high risk vs medium risk' => [RiskLevel::HIGH_RISK, RiskLevel::MEDIUM_RISK, RiskLevel::HIGH_RISK],
            'high risk vs high risk' => [RiskLevel::HIGH_RISK, RiskLevel::HIGH_RISK, RiskLevel::HIGH_RISK],
        ];
    }
    
    /**
     * 测试所有风险等级的值是否符合预期
     */
    public function testEnumValues(): void
    {
        $this->assertEquals('无风险', RiskLevel::NO_RISK->value);
        $this->assertEquals('低风险', RiskLevel::LOW_RISK->value);
        $this->assertEquals('中风险', RiskLevel::MEDIUM_RISK->value);
        $this->assertEquals('高风险', RiskLevel::HIGH_RISK->value);
    }
} 