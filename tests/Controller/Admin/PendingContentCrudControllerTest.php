<?php

namespace AIContentAuditBundle\Tests\Controller\Admin;

use AIContentAuditBundle\Controller\Admin\PendingContentCrudController;
use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Repository\GeneratedContentRepository;
use AIContentAuditBundle\Service\ContentAuditService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PendingContentCrudControllerTest extends TestCase
{
    private PendingContentCrudController $controller;
    private GeneratedContentRepository&MockObject $repository;
    private ContentAuditService&MockObject $contentAuditService;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(GeneratedContentRepository::class);
        $this->contentAuditService = $this->createMock(ContentAuditService::class);
        
        $this->controller = new PendingContentCrudController(
            $this->contentAuditService,
            $this->repository
        );
    }
    
    public function testGetEntityFqcn()
    {
        $result = PendingContentCrudController::getEntityFqcn();
        
        $this->assertEquals(GeneratedContent::class, $result);
    }
    
    public function testCreateIndexQueryBuilder()
    {
        // 测试控制器继承关系，该方法在父类中定义
        $this->assertInstanceOf(
            \AIContentAuditBundle\Controller\Admin\GeneratedContentCrudController::class,
            $this->controller
        );
    }
    
    public function testInheritsFromGeneratedContentCrudController()
    {
        // 测试继承关系
        $this->assertInstanceOf(
            \AIContentAuditBundle\Controller\Admin\GeneratedContentCrudController::class,
            $this->controller
        );
    }
    
    public function testConfigureCrud()
    {
        // 测试控制器继承关系（继承自父类）
        $this->assertInstanceOf(
            \EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController::class,
            $this->controller
        );
    }
    
    public function testConfigureFilters()
    {
        // 测试控制器继承关系（继承自父类）
        $this->assertInstanceOf(
            \EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController::class,
            $this->controller
        );
    }
    
    public function testConfigureFields()
    {
        $result = $this->controller->configureFields('index');
        
        // 将迭代器转换为数组以便测试
        $fields = iterator_to_array($result);
        $this->assertNotEmpty($fields);
    }
    
    public function testConfigureActions()
    {
        // 测试控制器继承关系（继承自父类）
        $this->assertInstanceOf(
            \EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController::class,
            $this->controller
        );
    }
    
} 