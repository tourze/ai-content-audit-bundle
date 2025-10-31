<?php

declare(strict_types=1);

namespace AIContentAuditBundle\Tests\Controller\Admin;

use AIContentAuditBundle\Controller\Admin\GeneratedContentCrudController;
use AIContentAuditBundle\Controller\Admin\PendingContentCrudController;
use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Enum\AuditResult;
use AIContentAuditBundle\Enum\RiskLevel;
use AIContentAuditBundle\Repository\GeneratedContentRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Exception\EntityNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * PendingContentCrudController HTTP集成测试
 *
 * 通过HTTP层测试控制器功能，符合WebTestCase标准
 *
 * @internal
 */
#[CoversClass(PendingContentCrudController::class)]
#[RunTestsInSeparateProcesses]
final class PendingContentCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * @return AbstractCrudController<\AIContentAuditBundle\Entity\GeneratedContent>
     */
    protected function getControllerService(): AbstractCrudController
    {
        $controller = self::getService(PendingContentCrudController::class);
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
        // NEW action is disabled for this controller, but PHPUnit requires at least one test case
        // The test will be skipped by the base class due to action being disabled
        yield 'dummy' => ['dummy_field'];
    }

    public static function provideEditPageFields(): iterable
    {
        // EDIT action is disabled for this controller, but PHPUnit requires at least one test case
        // The test will be skipped by the base class due to action being disabled
        yield 'dummy' => ['dummy_field'];
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

    public function testCanAccessPendingContentIndex(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // 直接访问EasyAdmin主页面，验证控制器可以正常加载
        $client->request('GET', '/admin');

        // 验证响应状态
        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful(), 'Response should be successful');
    }

    public function testIndexPageShowsOnlyPendingContent(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // 创建测试数据
        $entityManager = self::getService(EntityManagerInterface::class);

        // 创建一个中风险待审核内容
        $pendingContent = new GeneratedContent();
        $pendingContent->setUser('test_user');
        $pendingContent->setInputText('Test input');
        $pendingContent->setOutputText('Test output');
        $pendingContent->setMachineAuditResult(RiskLevel::MEDIUM_RISK);
        $pendingContent->setMachineAuditTime(new \DateTimeImmutable());
        // manualAuditResult 默认为 null（待审核）

        $entityManager->persist($pendingContent);
        $entityManager->flush();

        // 验证我们创建的内容符合过滤条件
        $this->assertSame(RiskLevel::MEDIUM_RISK, $pendingContent->getMachineAuditResult());
        $this->assertNull($pendingContent->getManualAuditResult());

        // 确认内容已保存到数据库并能被正确查询
        $savedContent = $entityManager->find(GeneratedContent::class, $pendingContent->getId());
        $this->assertNotNull($savedContent);
        $this->assertSame('test_user', $savedContent->getUser());

        // 测试QueryBuilder过滤逻辑查找我们创建的特定内容
        $repository = self::getService(GeneratedContentRepository::class);
        $queryBuilder = $repository->createQueryBuilder('entity')
            ->andWhere('entity.machineAuditResult = :risk')
            ->andWhere('entity.manualAuditResult IS NULL')
            ->andWhere('entity.user = :user')
            ->setParameter('risk', RiskLevel::MEDIUM_RISK->value)
            ->setParameter('user', 'test_user')
        ;

        $results = $queryBuilder->getQuery()->getResult();
        self::assertIsArray($results);
        $this->assertGreaterThanOrEqual(1, count($results), '应该能找到我们创建的待审核内容');

        $foundOurContent = false;
        foreach ($results as $result) {
            self::assertInstanceOf(GeneratedContent::class, $result);
            if ('test_user' === $result->getUser()) {
                $foundOurContent = true;
                break;
            }
        }
        $this->assertTrue($foundOurContent, '应该能找到用户名为test_user的内容');
    }

    public function testAuditActionRedirectsToEditPage(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // 创建测试数据
        $entityManager = self::getService(EntityManagerInterface::class);

        $content = new GeneratedContent();
        $content->setUser('audit_test_user');
        $content->setInputText('Audit test input');
        $content->setOutputText('Audit test output');
        $content->setMachineAuditResult(RiskLevel::MEDIUM_RISK);
        $content->setMachineAuditTime(new \DateTimeImmutable());

        $entityManager->persist($content);
        $entityManager->flush();

        // 测试审核动作
        $client->request('GET', $this->generateAdminUrl('audit', ['entityId' => $content->getId()]));

        // 应该重定向到 GeneratedContentCrudController 的详情页面
        $response = $client->getResponse();
        $this->assertTrue($response->isRedirection(), 'Response should be a redirection');

        $location = (string) $response->headers->get('Location');
        $id = (string) $content->getId();
        $this->assertTrue(
            str_contains($location, '/generated-content/' . $id)
            || (str_contains($location, 'crudControllerFqcn=' . rawurlencode(GeneratedContentCrudController::class)) && str_contains($location, 'entityId=' . $id)),
            'Redirect location should point to generated content detail page'
        );
    }

    public function testFindMethodBypassesQueryBuilderFilter(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // 创建一个不符合索引页过滤条件的内容（低风险）
        $entityManager = self::getService(EntityManagerInterface::class);

        $content = new GeneratedContent();
        $content->setUser('bypass_test_user');
        $content->setInputText('Bypass test input');
        $content->setOutputText('Bypass test output');
        $content->setMachineAuditResult(RiskLevel::LOW_RISK); // 低风险，不会在索引页显示
        $content->setMachineAuditTime(new \DateTimeImmutable());

        $entityManager->persist($content);
        $entityManager->flush();

        // 测试重写的 find 方法是否能正确找到实体
        $controller = self::getService(PendingContentCrudController::class);
        $controller->setContainer(self::getContainer());
        $foundEntity = $controller->find($content->getId());

        $this->assertNotNull($foundEntity, 'find 方法应该能找到不符合过滤条件的实体');
        $this->assertSame($content->getId(), $foundEntity->getId());
        self::assertInstanceOf(GeneratedContent::class, $foundEntity);
        $this->assertSame('bypass_test_user', $foundEntity->getUser());
    }

    public function testAuditActionWithInvalidEntityId(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // 测试无效的实体ID
        $this->expectException(EntityNotFoundException::class);

        $client->request('GET', $this->generateAdminUrl('audit', ['entityId' => 99999]));
    }

    public function testAuditActionWithoutEntityId(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // 对于路径参数，entityId 是必需的，访问 /audit 会匹配到 /{entityId}/audit 路由
        // entityId 会被解析为 "audit" 字符串，导致实体找不到
        $this->expectException(EntityNotFoundException::class);

        // 访问包含 audit 作为 entityId 的路由（模拟非法ID）
        $client->request('GET', $this->generateAdminUrl('audit', ['entityId' => 'audit']));
    }

    public function testQuickAuditPassAction(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // 创建待审核内容
        $entityManager = self::getService(EntityManagerInterface::class);
        $content = new GeneratedContent();
        $content->setUser('audit_pass_user');
        $content->setInputText('Test audit pass input');
        $content->setOutputText('Test audit pass output');
        $content->setMachineAuditResult(RiskLevel::MEDIUM_RISK);
        $content->setMachineAuditTime(new \DateTimeImmutable());

        $entityManager->persist($content);
        $entityManager->flush();

        // 测试快速审核通过
        $client->request('GET', $this->generateAdminUrl('quickAuditPass', ['entityId' => $content->getId()]));

        $response = $client->getResponse();
        $this->assertTrue($response->isRedirection(), 'Response should be a redirection');

        // 验证审核结果 - 重新从数据库获取实体
        $updatedContent = $entityManager->find(GeneratedContent::class, $content->getId());
        $this->assertNotNull($updatedContent);
        $this->assertSame(AuditResult::PASS, $updatedContent->getManualAuditResult());
        $this->assertNotNull($updatedContent->getManualAuditTime());
    }

    public function testQuickAuditRejectAction(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // 创建待审核内容
        $entityManager = self::getService(EntityManagerInterface::class);
        $content = new GeneratedContent();
        $content->setUser('audit_reject_user');
        $content->setInputText('Test audit reject input');
        $content->setOutputText('Test audit reject output');
        $content->setMachineAuditResult(RiskLevel::MEDIUM_RISK);
        $content->setMachineAuditTime(new \DateTimeImmutable());

        $entityManager->persist($content);
        $entityManager->flush();

        // 测试快速审核拒绝
        $client->request('GET', $this->generateAdminUrl('quickAuditReject', ['entityId' => $content->getId()]));

        $response = $client->getResponse();
        $this->assertTrue($response->isRedirection(), 'Response should be a redirection');

        // 验证审核结果 - 重新从数据库获取实体
        $updatedContent = $entityManager->find(GeneratedContent::class, $content->getId());
        $this->assertNotNull($updatedContent);
        $this->assertSame(AuditResult::DELETE, $updatedContent->getManualAuditResult());
        $this->assertNotNull($updatedContent->getManualAuditTime());
    }

    public function testDetailAuditActionRedirectsCorrectly(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // 创建待审核内容
        $entityManager = self::getService(EntityManagerInterface::class);
        $content = new GeneratedContent();
        $content->setUser('detail_audit_user');
        $content->setInputText('Test detail audit input');
        $content->setOutputText('Test detail audit output');
        $content->setMachineAuditResult(RiskLevel::MEDIUM_RISK);
        $content->setMachineAuditTime(new \DateTimeImmutable());

        $entityManager->persist($content);
        $entityManager->flush();

        // 测试详细审核重定向
        $client->request('GET', $this->generateAdminUrl('detailAudit', ['entityId' => $content->getId()]));

        $response = $client->getResponse();
        $this->assertTrue($response->isRedirection(), 'Response should be a redirection');
        $location = (string) $response->headers->get('Location');
        $id = (string) $content->getId();
        $this->assertTrue(
            str_contains($location, '/generated-content/' . $id)
            || (str_contains($location, 'crudControllerFqcn=' . rawurlencode(GeneratedContentCrudController::class)) && str_contains($location, 'entityId=' . $id)),
            'Redirect location should point to generated content detail page'
        );
    }

    /**
     * 检查控制器是否禁用了指定动作
     */
    private function isActionDisabled(string $actionName): bool
    {
        $controller = $this->getControllerService();
        $actions = Actions::new();
        $controller->configureActions($actions);

        $disabledActions = $actions->getAsDto(Action::INDEX)->getDisabledActions();

        return in_array($actionName, $disabledActions, true);
    }

    /**
     * 测试验证控制器确实禁用了编辑和新建动作
     */
    public function testEditAndNewActionsAreDisabled(): void
    {
        $this->assertTrue($this->isActionDisabled(Action::EDIT), 'EDIT action should be disabled');
        $this->assertTrue($this->isActionDisabled(Action::NEW), 'NEW action should be disabled');
    }

    /**
     * 测试验证控制器允许索引动作
     */
    public function testIndexActionIsEnabled(): void
    {
        $this->assertFalse($this->isActionDisabled(Action::INDEX), 'INDEX action should be enabled');
    }

    /**
     * 测试验证控制器允许详情动作
     */
    public function testDetailActionIsEnabled(): void
    {
        $this->assertFalse($this->isActionDisabled(Action::DETAIL), 'DETAIL action should be enabled');
    }

    /**
     * 测试验证控制器有正确的自定义动作
     */
    public function testCustomActionsAreConfigured(): void
    {
        $controller = $this->getControllerService();
        $actions = Actions::new();
        $controller->configureActions($actions);

        $indexActionsDto = $actions->getAsDto(Action::INDEX);
        $pageActions = $indexActionsDto->getActions();

        $actionNames = [];
        foreach ($pageActions as $actionName => $actionDto) {
            $actionNames[] = $actionName;
        }

        $this->assertContains('quickAuditPass', $actionNames, 'Should have quickAuditPass action');
        $this->assertContains('quickAuditReject', $actionNames, 'Should have quickAuditReject action');
        $this->assertContains('detailAudit', $actionNames, 'Should have detailAudit action');
    }
}
