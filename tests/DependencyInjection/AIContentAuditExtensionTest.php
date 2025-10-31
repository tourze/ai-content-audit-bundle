<?php

namespace AIContentAuditBundle\Tests\DependencyInjection;

use AIContentAuditBundle\DependencyInjection\AIContentAuditExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(AIContentAuditExtension::class)]
final class AIContentAuditExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private AIContentAuditExtension $extension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new AIContentAuditExtension();
    }

    public function testLoadWithMultipleConfigsMergesCorrectly(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $configs = [
            [],
            [],
        ];

        $this->extension->load($configs, $container);

        // 验证控制器是否被正确加载
        $this->assertTrue($container->hasDefinition('AIContentAuditBundle\Controller\Admin\GeneratedContentCrudController'));
        $this->assertTrue($container->hasDefinition('AIContentAuditBundle\Controller\Admin\PendingContentCrudController'));
        $this->assertTrue($container->hasDefinition('AIContentAuditBundle\Controller\Admin\PendingReportCrudController'));
        $this->assertTrue($container->hasDefinition('AIContentAuditBundle\Controller\Admin\ReportCrudController'));
        $this->assertTrue($container->hasDefinition('AIContentAuditBundle\Controller\Admin\RiskKeywordCrudController'));
        $this->assertTrue($container->hasDefinition('AIContentAuditBundle\Controller\Admin\ViolationRecordCrudController'));
    }

    public function testLoadWithConfigsLoadsRepositories(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $this->extension->load([], $container);

        // 验证仓储是否被正确加载
        $this->assertTrue($container->hasDefinition('AIContentAuditBundle\Repository\GeneratedContentRepository'));
        $this->assertTrue($container->hasDefinition('AIContentAuditBundle\Repository\ReportRepository'));
        $this->assertTrue($container->hasDefinition('AIContentAuditBundle\Repository\RiskKeywordRepository'));
        $this->assertTrue($container->hasDefinition('AIContentAuditBundle\Repository\ViolationRecordRepository'));
    }

    public function testPrependConfiguresTwigPaths(): void
    {
        $container = new ContainerBuilder();

        // 执行prepend方法
        $this->extension->prepend($container);

        // 获取prepend的扩展配置
        $twigConfig = $container->getExtensionConfig('twig');

        // 断言twig配置被正确prepend
        $this->assertNotEmpty($twigConfig);
        $this->assertArrayHasKey('paths', $twigConfig[0]);

        // 验证模板路径配置
        $paths = $twigConfig[0]['paths'];

        // Debug output to see the actual structure
        // var_dump($paths);

        // 验证路径指向正确的模板目录
        $expectedPath = dirname(__DIR__, 2) . '/templates';

        // 验证关联数组结构：路径 => 命名空间
        self::assertIsArray($paths);
        $this->assertArrayHasKey($expectedPath, $paths);
        $this->assertEquals('AIContentAudit', $paths[$expectedPath]);
        $this->assertStringEndsWith('ai-content-audit-bundle/templates', $expectedPath);
    }

    public function testPrependWithExistingTwigConfig(): void
    {
        $container = new ContainerBuilder();

        // 预先设置一些twig配置
        $container->prependExtensionConfig('twig', [
            'debug' => true,
            'paths' => [
                'custom/path' => 'CustomNamespace',
            ],
        ]);

        // 执行prepend方法
        $this->extension->prepend($container);

        // 获取所有twig配置
        $twigConfigs = $container->getExtensionConfig('twig');

        // 应该有两个配置项：我们的配置和预先存在的配置
        $this->assertCount(2, $twigConfigs);

        // 第一个配置应该是我们prepend的路径配置
        $this->assertArrayHasKey('paths', $twigConfigs[0]);
        $expectedPath = dirname(__DIR__, 2) . '/templates';
        $firstConfigPaths = $twigConfigs[0]['paths'];
        self::assertIsArray($firstConfigPaths);
        $this->assertArrayHasKey($expectedPath, $firstConfigPaths);
        $this->assertEquals('AIContentAudit', $firstConfigPaths[$expectedPath]);

        // 第二个配置应该是预先存在的配置
        $this->assertArrayHasKey('debug', $twigConfigs[1]);
        $this->assertTrue($twigConfigs[1]['debug']);
        $secondConfigPaths = $twigConfigs[1]['paths'];
        self::assertIsArray($secondConfigPaths);
        $this->assertArrayHasKey('custom/path', $secondConfigPaths);
        $this->assertEquals('CustomNamespace', $secondConfigPaths['custom/path']);
    }
}
