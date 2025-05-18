<?php

namespace AIContentAuditBundle\Tests\Entity;

use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Entity\Report;
use AIContentAuditBundle\Enum\AuditResult;
use AIContentAuditBundle\Enum\RiskLevel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

class GeneratedContentTest extends TestCase
{
    private GeneratedContent $generatedContent;
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->generatedContent = new GeneratedContent();
        $this->now = new \DateTimeImmutable();
    }

    /**
     * @dataProvider provideUserData
     */
    public function testUserAccessors($user): void
    {
        $this->generatedContent->setUser($user);
        $this->assertSame($user, $this->generatedContent->getUser());
    }
    
    public function provideUserData(): array
    {
        $user = $this->createMock(UserInterface::class);
        
        return [
            'normal user' => [$user],
        ];
    }
    
    /**
     * @dataProvider provideTextData
     */
    public function testInputTextAccessors(string $text): void
    {
        $this->generatedContent->setInputText($text);
        $this->assertEquals($text, $this->generatedContent->getInputText());
    }
    
    /**
     * @dataProvider provideTextData
     */
    public function testOutputTextAccessors(string $text): void
    {
        $this->generatedContent->setOutputText($text);
        $this->assertEquals($text, $this->generatedContent->getOutputText());
    }
    
    public function provideTextData(): array
    {
        return [
            'empty string' => [''],
            'simple text' => ['Hello world'],
            'text with special chars' => ['Hello, 你好! Special chars: !@#$%^&*()'],
            'multiline text' => ["Line 1\nLine 2\nLine 3"],
        ];
    }
    
    /**
     * @dataProvider provideMachineAuditResultData
     */
    public function testMachineAuditResultAccessors(RiskLevel $riskLevel): void
    {
        $this->generatedContent->setMachineAuditResult($riskLevel);
        $this->assertEquals($riskLevel, $this->generatedContent->getMachineAuditResult());
    }
    
    public function provideMachineAuditResultData(): array
    {
        return [
            'no risk' => [RiskLevel::NO_RISK],
            'low risk' => [RiskLevel::LOW_RISK],
            'medium risk' => [RiskLevel::MEDIUM_RISK],
            'high risk' => [RiskLevel::HIGH_RISK],
        ];
    }
    
    public function testMachineAuditTimeAccessors(): void
    {
        $this->generatedContent->setMachineAuditTime($this->now);
        $this->assertEquals($this->now, $this->generatedContent->getMachineAuditTime());
    }
    
    /**
     * @dataProvider provideManualAuditResultData
     */
    public function testManualAuditResultAccessors(?AuditResult $auditResult): void
    {
        $this->generatedContent->setManualAuditResult($auditResult);
        $this->assertEquals($auditResult, $this->generatedContent->getManualAuditResult());
    }
    
    public function provideManualAuditResultData(): array
    {
        return [
            'null result' => [null],
            'pass result' => [AuditResult::PASS],
            'modify result' => [AuditResult::MODIFY],
            'delete result' => [AuditResult::DELETE],
        ];
    }
    
    /**
     * @dataProvider provideManualAuditTimeData
     */
    public function testManualAuditTimeAccessors(?\DateTimeImmutable $time): void
    {
        $this->generatedContent->setManualAuditTime($time);
        $this->assertEquals($time, $this->generatedContent->getManualAuditTime());
    }
    
    public function provideManualAuditTimeData(): array
    {
        return [
            'null time' => [null],
            'current time' => [new \DateTimeImmutable()],
        ];
    }
    
    public function testReportsCollection(): void
    {
        $report1 = new Report();
        $report2 = new Report();
        
        // 初始状态下，reports集合应该为空
        $this->assertCount(0, $this->generatedContent->getReports());
        
        // 添加第一个报告
        $this->generatedContent->addReport($report1);
        $this->assertCount(1, $this->generatedContent->getReports());
        $this->assertTrue($this->generatedContent->getReports()->contains($report1));
        
        // 添加第二个报告
        $this->generatedContent->addReport($report2);
        $this->assertCount(2, $this->generatedContent->getReports());
        $this->assertTrue($this->generatedContent->getReports()->contains($report2));
        
        // 添加重复报告不应增加集合大小
        $this->generatedContent->addReport($report1);
        $this->assertCount(2, $this->generatedContent->getReports());
        
        // 移除报告
        $this->generatedContent->removeReport($report1);
        $this->assertCount(1, $this->generatedContent->getReports());
        $this->assertFalse($this->generatedContent->getReports()->contains($report1));
        $this->assertTrue($this->generatedContent->getReports()->contains($report2));
    }
    
    public function testNeedsManualAudit_returnsTrueForMediumRiskWithoutManualAudit(): void
    {
        $this->generatedContent->setMachineAuditResult(RiskLevel::MEDIUM_RISK);
        $this->generatedContent->setManualAuditResult(null);
        
        $this->assertTrue($this->generatedContent->needsManualAudit());
    }
    
    public function testNeedsManualAudit_returnsFalseForMediumRiskWithManualAudit(): void
    {
        $this->generatedContent->setMachineAuditResult(RiskLevel::MEDIUM_RISK);
        $this->generatedContent->setManualAuditResult(AuditResult::PASS);
        
        $this->assertFalse($this->generatedContent->needsManualAudit());
    }
    
    public function testNeedsManualAudit_returnsFalseForNonMediumRisk(): void
    {
        $this->generatedContent->setMachineAuditResult(RiskLevel::LOW_RISK);
        $this->generatedContent->setManualAuditResult(null);
        
        $this->assertFalse($this->generatedContent->needsManualAudit());
        
        $this->generatedContent->setMachineAuditResult(RiskLevel::HIGH_RISK);
        $this->assertFalse($this->generatedContent->needsManualAudit());
    }
    
    public function testToString(): void
    {
        $this->generatedContent->setInputText('This is a test input that is longer than thirty characters.');
        $this->assertEquals('This is a test input that is l...', (string)$this->generatedContent);
        
        $this->generatedContent->setInputText('Short text');
        $this->assertEquals('Short text...', (string)$this->generatedContent);
    }
} 