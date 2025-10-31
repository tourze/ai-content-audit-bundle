<?php

declare(strict_types=1);

namespace AIContentAuditBundle\Tests\Controller\Admin;

use AIContentAuditBundle\Controller\Admin\ViolationRecordCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * ViolationRecordCrudController HTTP集成测试
 *
 * 通过HTTP层测试控制器功能，符合WebTestCase标准
 *
 * @internal
 */
#[CoversClass(ViolationRecordCrudController::class)]
#[RunTestsInSeparateProcesses]
final class ViolationRecordCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * @return AbstractCrudController<\AIContentAuditBundle\Entity\ViolationRecord>
     */
    protected function getControllerService(): AbstractCrudController
    {
        $controller = self::getService(ViolationRecordCrudController::class);
        self::assertInstanceOf(AbstractCrudController::class, $controller);

        return $controller;
    }

    public static function provideIndexPageHeaders(): iterable
    {
        yield '用户' => ['用户'];
        yield '违规时间' => ['违规时间'];
        yield '违规类型' => ['违规类型'];
        yield '处理结果' => ['处理结果'];
        yield '处理时间' => ['处理时间'];
        yield '处理人员' => ['处理人员'];
    }

    public static function provideNewPageFields(): iterable
    {
        yield 'user' => ['user'];
        yield 'violationTime' => ['violationTime'];
        yield 'violationContent' => ['violationContent'];
        yield 'violationType' => ['violationType'];
        yield 'processResult' => ['processResult'];
        yield 'processTime' => ['processTime'];
        yield 'processedBy' => ['processedBy'];
    }

    public static function provideEditPageFields(): iterable
    {
        yield 'id' => ['id'];
        yield 'user' => ['user'];
        yield 'violationTime' => ['violationTime'];
        yield 'violationContent' => ['violationContent'];
        yield 'violationType' => ['violationType'];
        yield 'processResult' => ['processResult'];
        yield 'processTime' => ['processTime'];
        yield 'processedBy' => ['processedBy'];
    }

    public function testAuthenticatedAdminCanAccessDashboard(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // 认证用户应该能访问Dashboard
        $crawler = $client->request('GET', '/admin');

        // 验证响应状态
        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful(), 'Response should be successful');
        $content = $response->getContent();
        $this->assertStringContainsString('dashboard', false !== $content ? $content : '');
    }

    /**
     * 测试exportViolationRecords自定义动作
     */
    public function testExportViolationRecordsAction(): void
    {
        self::markTestSkipped('export 功能尚未实现');
    }
}
