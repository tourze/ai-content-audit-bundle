<?php

declare(strict_types=1);

namespace AIContentAuditBundle\Tests\Controller\Admin;

use AIContentAuditBundle\Controller\Admin\GeneratedContentCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * GeneratedContentCrudController HTTP集成测试
 *
 * 通过HTTP层测试控制器功能，符合WebTestCase标准
 *
 * @internal
 */
#[CoversClass(GeneratedContentCrudController::class)]
#[RunTestsInSeparateProcesses]
final class GeneratedContentCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * @return AbstractCrudController<\AIContentAuditBundle\Entity\GeneratedContent>
     */
    protected function getControllerService(): AbstractCrudController
    {
        $controller = self::getService(GeneratedContentCrudController::class);
        self::assertInstanceOf(AbstractCrudController::class, $controller);

        return $controller;
    }

    public static function provideIndexPageHeaders(): iterable
    {
        yield '用户' => ['用户'];
        yield '机器审核结果' => ['机器审核结果'];
        yield '机器审核时间' => ['机器审核时间'];
        yield '人工审核结果' => ['人工审核结果'];
        yield '人工审核时间' => ['人工审核时间'];
    }

    public static function provideNewPageFields(): iterable
    {
        yield 'user' => ['user'];
        yield 'inputText' => ['inputText'];
        yield 'outputText' => ['outputText'];
        yield 'machineAuditResult' => ['machineAuditResult'];
        yield 'machineAuditTime' => ['machineAuditTime'];
        yield 'manualAuditResult' => ['manualAuditResult'];
        yield 'manualAuditTime' => ['manualAuditTime'];
    }

    public static function provideEditPageFields(): iterable
    {
        yield 'user' => ['user'];
        yield 'inputText' => ['inputText'];
        yield 'outputText' => ['outputText'];
        yield 'machineAuditResult' => ['machineAuditResult'];
        yield 'machineAuditTime' => ['machineAuditTime'];
        yield 'manualAuditResult' => ['manualAuditResult'];
        yield 'manualAuditTime' => ['manualAuditTime'];
    }

    public function testAuthenticatedAdminCanAccessDashboard(): void
    {
        $client = self::createAuthenticatedClient();

        // 认证用户应该能访问Dashboard
        $crawler = $client->request('GET', '/admin');

        // 验证响应状态
        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful(), 'Response should be successful');
        $content = $response->getContent();
        $this->assertStringContainsString('dashboard', false !== $content ? $content : '');
    }

    /**
     * 测试audit自定义动作
     */
    public function testAuditAction(): void
    {
        self::markTestSkipped('audit 功能的 Twig 模板尚未实现');
    }
}
