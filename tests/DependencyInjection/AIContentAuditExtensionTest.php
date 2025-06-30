<?php

namespace AIContentAuditBundle\Tests\DependencyInjection;

use AIContentAuditBundle\DependencyInjection\AIContentAuditExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AIContentAuditExtensionTest extends TestCase
{
    private AIContentAuditExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new AIContentAuditExtension();
    }

    public function testLoad_withEmptyConfig_loadsServices(): void
    {
        $container = new ContainerBuilder();
        
        $this->extension->load([], $container);
        
        // 验证一些基础服务是否被加载
        $this->assertTrue($container->hasDefinition('AIContentAuditBundle\Service\ContentAuditService'));
        $this->assertTrue($container->hasDefinition('AIContentAuditBundle\Service\ReportService'));
        $this->assertTrue($container->hasDefinition('AIContentAuditBundle\Service\StatisticsService'));
        $this->assertTrue($container->hasDefinition('AIContentAuditBundle\Service\UserManagementService'));
    }

    public function testLoad_withMultipleConfigs_mergesCorrectly(): void
    {
        $container = new ContainerBuilder();
        
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

    public function testLoad_withConfigs_loadsRepositories(): void
    {
        $container = new ContainerBuilder();
        
        $this->extension->load([], $container);
        
        // 验证仓储是否被正确加载
        $this->assertTrue($container->hasDefinition('AIContentAuditBundle\Repository\GeneratedContentRepository'));
        $this->assertTrue($container->hasDefinition('AIContentAuditBundle\Repository\ReportRepository'));
        $this->assertTrue($container->hasDefinition('AIContentAuditBundle\Repository\RiskKeywordRepository'));
        $this->assertTrue($container->hasDefinition('AIContentAuditBundle\Repository\ViolationRecordRepository'));
    }
}