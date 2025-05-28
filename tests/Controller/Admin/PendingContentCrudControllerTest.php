<?php

namespace AIContentAuditBundle\Tests\Controller\Admin;

use AIContentAuditBundle\Controller\Admin\PendingContentCrudController;
use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Enum\AuditResult;
use AIContentAuditBundle\Repository\GeneratedContentRepository;
use AIContentAuditBundle\Service\ContentAuditService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PendingContentCrudControllerTest extends TestCase
{
    private PendingContentCrudController $controller;
    private MockObject $entityManager;
    private MockObject $repository;
    private MockObject $contentAuditService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(GeneratedContentRepository::class);
        $this->contentAuditService = $this->createMock(ContentAuditService::class);
        
        $this->controller = new PendingContentCrudController(
            $this->contentAuditService,
            $this->entityManager
        );
        
        // 设置EntityManager返回Repository
        $this->entityManager->method('getRepository')
            ->with(GeneratedContent::class)
            ->willReturn($this->repository);
    }
    
    public function testGetEntityFqcn()
    {
        $result = PendingContentCrudController::getEntityFqcn();
        
        $this->assertEquals(GeneratedContent::class, $result);
    }
    
    public function testCreateIndexQueryBuilder()
    {
        // 测试方法是否存在且可调用
        $this->assertTrue(method_exists($this->controller, 'createIndexQueryBuilder'));
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
        // 测试方法是否存在且可调用（继承自父类）
        $this->assertTrue(method_exists($this->controller, 'configureCrud'));
    }
    
    public function testConfigureFilters()
    {
        // 测试方法是否存在且可调用（继承自父类）
        $this->assertTrue(method_exists($this->controller, 'configureFilters'));
    }
    
    public function testConfigureFields()
    {
        // 测试方法是否存在且可调用（继承自父类）
        $this->assertTrue(method_exists($this->controller, 'configureFields'));
        
        $result = $this->controller->configureFields('index');
        
        $this->assertIsIterable($result);
        
        // 将迭代器转换为数组以便测试
        $fields = iterator_to_array($result);
        $this->assertNotEmpty($fields);
    }
    
    public function testConfigureActions()
    {
        // 测试方法是否存在且可调用（继承自父类）
        $this->assertTrue(method_exists($this->controller, 'configureActions'));
    }
    
    /**
     * 创建测试用的GeneratedContent实例
     */
    private function createGeneratedContent(int $id, AuditResult $auditResult): GeneratedContent
    {
        $content = new GeneratedContent();
        $content->setInputText('测试输入内容');
        $content->setOutputText('测试生成内容');
        $content->setMachineAuditResult(\AIContentAuditBundle\Enum\RiskLevel::MEDIUM_RISK);
        $content->setMachineAuditTime(new \DateTimeImmutable());
        
        // 使用反射设置ID
        $reflection = new \ReflectionClass($content);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($content, $id);
        
        return $content;
    }
} 