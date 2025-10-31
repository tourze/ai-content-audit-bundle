<?php

namespace AIContentAuditBundle\Tests\Entity;

use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Entity\Report;
use AIContentAuditBundle\Enum\AuditResult;
use AIContentAuditBundle\Enum\RiskLevel;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(GeneratedContent::class)]
final class GeneratedContentTest extends AbstractEntityTestCase
{
    public function testReportsCollection(): void
    {
        $entity = $this->createEntity();
        $report1 = new Report();
        $report2 = new Report();

        // 初始状态下，reports集合应该为空
        $this->assertCount(0, $entity->getReports());

        // 添加第一个报告
        $entity->addReport($report1);
        $this->assertCount(1, $entity->getReports());
        $this->assertTrue($entity->getReports()->contains($report1));

        // 添加第二个报告
        $entity->addReport($report2);
        $this->assertCount(2, $entity->getReports());
        $this->assertTrue($entity->getReports()->contains($report2));

        // 添加重复报告不应增加集合大小
        $entity->addReport($report1);
        $this->assertCount(2, $entity->getReports());

        // 移除报告
        $entity->removeReport($report1);
        $this->assertCount(1, $entity->getReports());
        $this->assertFalse($entity->getReports()->contains($report1));
        $this->assertTrue($entity->getReports()->contains($report2));
    }

    public function testNeedsManualAuditReturnsTrueForMediumRiskWithoutManualAudit(): void
    {
        $entity = $this->createEntity();
        $entity->setMachineAuditResult(RiskLevel::MEDIUM_RISK);
        $entity->setManualAuditResult(null);

        $this->assertTrue($entity->needsManualAudit());
    }

    public function testNeedsManualAuditReturnsFalseForMediumRiskWithManualAudit(): void
    {
        $entity = $this->createEntity();
        $entity->setMachineAuditResult(RiskLevel::MEDIUM_RISK);
        $entity->setManualAuditResult(AuditResult::PASS);

        $this->assertFalse($entity->needsManualAudit());
    }

    public function testNeedsManualAuditReturnsFalseForNonMediumRisk(): void
    {
        $entity = $this->createEntity();
        $entity->setMachineAuditResult(RiskLevel::LOW_RISK);
        $entity->setManualAuditResult(null);

        $this->assertFalse($entity->needsManualAudit());

        $entity->setMachineAuditResult(RiskLevel::HIGH_RISK);
        $this->assertFalse($entity->needsManualAudit());
    }

    public function testToString(): void
    {
        $entity = $this->createEntity();
        $entity->setInputText('This is a test input that is longer than thirty characters.');
        $this->assertEquals('This is a test input that is l...', (string) $entity);

        $entity->setInputText('Short text');
        $this->assertEquals('Short text...', (string) $entity);
    }

    protected function createEntity(): GeneratedContent
    {
        return new GeneratedContent();
    }

    /**
     * @return array<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): array
    {
        return [
            'user' => ['user', 'test_user'],
            'inputText' => ['inputText', 'Test input text'],
            'outputText' => ['outputText', 'Test output text'],
            'machineAuditResult' => ['machineAuditResult', RiskLevel::NO_RISK],
            'machineAuditTime' => ['machineAuditTime', new \DateTimeImmutable()],
            'manualAuditResult' => ['manualAuditResult', AuditResult::PASS],
            'manualAuditTime' => ['manualAuditTime', new \DateTimeImmutable()],
        ];
    }
}
