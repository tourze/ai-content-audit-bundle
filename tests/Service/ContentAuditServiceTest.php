<?php

namespace AIContentAuditBundle\Tests\Service;

use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Enum\AuditResult;
use AIContentAuditBundle\Enum\RiskLevel;
use AIContentAuditBundle\Repository\GeneratedContentRepository;
use AIContentAuditBundle\Service\ContentAuditService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(ContentAuditService::class)]
#[RunTestsInSeparateProcesses]
final class ContentAuditServiceTest extends AbstractIntegrationTestCase
{
    private ContentAuditService $service;

    protected function onSetUp(): void
    {
        $this->service = self::getService(ContentAuditService::class);
    }

    public function testMachineAuditWithNoRiskContent(): void
    {
        // 执行方法
        $result = $this->service->machineAudit(
            'This is a safe input',
            'This is a safe output',
            'test_user'
        );

        // 断言结果
        $this->assertEquals(RiskLevel::NO_RISK, $result->getMachineAuditResult());
        $this->assertEquals('This is a safe input', $result->getInputText());
        $this->assertEquals('This is a safe output', $result->getOutputText());
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->getMachineAuditTime());

        // 验证数据库中保存了结果
        $savedContent = self::getService(GeneratedContentRepository::class)
            ->findOneBy(['inputText' => 'This is a safe input'])
        ;
        $this->assertNotNull($savedContent);
        $this->assertEquals(RiskLevel::NO_RISK, $savedContent->getMachineAuditResult());
    }

    public function testMachineAuditWithEmptyInput(): void
    {
        // 执行方法
        $result = $this->service->machineAudit(
            '',
            '',
            'test_user'
        );

        // 断言结果
        $this->assertEquals(RiskLevel::NO_RISK, $result->getMachineAuditResult());
        $this->assertEquals('', $result->getInputText());
        $this->assertEquals('', $result->getOutputText());
    }

    public function testFindGeneratedContentExists(): void
    {
        // 创建测试内容
        $content = new GeneratedContent();
        $content->setUser('test_user');
        $content->setInputText('Test input');
        $content->setOutputText('Test output');
        $content->setMachineAuditResult(RiskLevel::NO_RISK);
        $content->setMachineAuditTime(new \DateTimeImmutable());

        self::getEntityManager()->persist($content);
        self::getEntityManager()->flush();

        $contentId = $content->getId();
        $this->assertNotNull($contentId);

        // 执行方法
        $foundContent = $this->service->findGeneratedContent($contentId);

        // 断言结果
        $this->assertNotNull($foundContent);
        $this->assertEquals($contentId, $foundContent->getId());
        $this->assertEquals('test_user', $foundContent->getUser());
        $this->assertEquals('Test input', $foundContent->getInputText());
        $this->assertEquals('Test output', $foundContent->getOutputText());
        $this->assertEquals(RiskLevel::NO_RISK, $foundContent->getMachineAuditResult());
    }

    public function testFindGeneratedContentNotFound(): void
    {
        // 使用不存在的ID
        $nonExistentId = 999999;

        // 执行方法
        $foundContent = $this->service->findGeneratedContent($nonExistentId);

        // 断言结果
        $this->assertNull($foundContent);
    }

    public function testManualAuditWithPassResult(): void
    {
        // 创建测试内容
        $content = new GeneratedContent();
        $content->setUser('test_user');
        $content->setInputText('Test input');
        $content->setOutputText('Test output');
        $content->setMachineAuditResult(RiskLevel::MEDIUM_RISK);
        $content->setMachineAuditTime(new \DateTimeImmutable());

        self::getEntityManager()->persist($content);
        self::getEntityManager()->flush();

        // 执行人工审核
        $result = $this->service->manualAudit($content, AuditResult::PASS, 'admin_user');

        // 断言结果
        $this->assertEquals(AuditResult::PASS, $result->getManualAuditResult());
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->getManualAuditTime());
        $this->assertEquals($content->getId(), $result->getId());

        // 验证数据库中的更新
        self::getEntityManager()->refresh($content);
        $this->assertEquals(AuditResult::PASS, $content->getManualAuditResult());
        $this->assertNotNull($content->getManualAuditTime());
    }

    public function testManualAuditWithModifyResult(): void
    {
        // 创建测试内容
        $content = new GeneratedContent();
        $content->setUser('test_user');
        $content->setInputText('Test input');
        $content->setOutputText('Test output');
        $content->setMachineAuditResult(RiskLevel::HIGH_RISK);
        $content->setMachineAuditTime(new \DateTimeImmutable());

        self::getEntityManager()->persist($content);
        self::getEntityManager()->flush();

        // 执行人工审核
        $result = $this->service->manualAudit($content, AuditResult::MODIFY, 'admin_user');

        // 断言结果
        $this->assertEquals(AuditResult::MODIFY, $result->getManualAuditResult());
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->getManualAuditTime());
    }

    public function testManualAuditWithDeleteResult(): void
    {
        // 创建测试内容
        $content = new GeneratedContent();
        $content->setUser('test_user');
        $content->setInputText('Inappropriate input');
        $content->setOutputText('Inappropriate output');
        $content->setMachineAuditResult(RiskLevel::HIGH_RISK);
        $content->setMachineAuditTime(new \DateTimeImmutable());

        self::getEntityManager()->persist($content);
        self::getEntityManager()->flush();

        // 执行人工审核
        $result = $this->service->manualAudit($content, AuditResult::DELETE, 'admin_user');

        // 断言结果
        $this->assertEquals(AuditResult::DELETE, $result->getManualAuditResult());
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->getManualAuditTime());

        // 验证创建了违规记录
        $violationRepository = self::getService('AIContentAuditBundle\Repository\ViolationRecordRepository');
        $violations = $violationRepository->findBy(['user' => 'test_user']);
        $this->assertCount(1, $violations);
        $this->assertEquals('admin_user', $violations[0]->getProcessedBy());
    }
}
