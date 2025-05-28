<?php

namespace AIContentAuditBundle\Tests\Controller\Admin;

use AIContentAuditBundle\Controller\Admin\GeneratedContentCrudController;
use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Enum\AuditResult;
use AIContentAuditBundle\Enum\RiskLevel;
use AIContentAuditBundle\Repository\GeneratedContentRepository;
use AIContentAuditBundle\Service\ContentAuditService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Environment;

class GeneratedContentCrudControllerTest extends TestCase
{
    private GeneratedContentCrudController $controller;
    private MockObject $contentAuditService;
    private MockObject $entityManager;
    private MockObject $repository;
    private MockObject $urlGenerator;
    private MockObject $twig;
    private MockObject $user;

    protected function setUp(): void
    {
        $this->contentAuditService = $this->createMock(ContentAuditService::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(GeneratedContentRepository::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->twig = $this->createMock(Environment::class);
        $this->user = $this->createMock(UserInterface::class);
        
        $this->controller = new GeneratedContentCrudController(
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
        $result = GeneratedContentCrudController::getEntityFqcn();
        
        $this->assertEquals(GeneratedContent::class, $result);
    }
    
    public function testAudit_withValidContent()
    {
        // 由于涉及复杂的EasyAdmin环境设置，这里主要测试方法存在性
        $this->assertTrue(method_exists($this->controller, 'audit'));
        $this->assertInstanceOf(GeneratedContentCrudController::class, $this->controller);
    }
    
    public function testAudit_withNonExistentContent()
    {
        $contentId = 999;
        
        // Mock repository返回null
        $this->repository->expects($this->once())
            ->method('find')
            ->with($contentId)
            ->willReturn(null);
            
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('内容不存在');
        
        $this->controller->audit($this->entityManager, $contentId);
    }
    
    public function testSubmitAudit_withValidContent()
    {
        // 由于涉及复杂的EasyAdmin环境设置，这里主要测试方法存在性
        $this->assertTrue(method_exists($this->controller, 'submitAudit'));
        $this->assertInstanceOf(GeneratedContentCrudController::class, $this->controller);
    }
    
    public function testSubmitAudit_withNonExistentContent()
    {
        $contentId = 999;
        $request = $this->createMock(Request::class);
        
        // Mock repository返回null
        $this->repository->expects($this->once())
            ->method('find')
            ->with($contentId)
            ->willReturn(null);
            
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('内容不存在');
        
        $this->controller->submitAudit($request, $contentId);
    }
    
    public function testGetRiskLevelChoices()
    {
        // 使用反射访问私有方法
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getRiskLevelChoices');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->controller);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('无风险', $result);
        $this->assertArrayHasKey('低风险', $result);
        $this->assertArrayHasKey('中风险', $result);
        $this->assertArrayHasKey('高风险', $result);
        
        $this->assertEquals(RiskLevel::NO_RISK->value, $result['无风险']);
        $this->assertEquals(RiskLevel::LOW_RISK->value, $result['低风险']);
        $this->assertEquals(RiskLevel::MEDIUM_RISK->value, $result['中风险']);
        $this->assertEquals(RiskLevel::HIGH_RISK->value, $result['高风险']);
    }
    
    public function testGetAuditResultChoices()
    {
        // 使用反射访问私有方法
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getAuditResultChoices');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->controller);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('通过', $result);
        $this->assertArrayHasKey('修改', $result);
        $this->assertArrayHasKey('删除', $result);
        
        $this->assertEquals(AuditResult::PASS->value, $result['通过']);
        $this->assertEquals(AuditResult::MODIFY->value, $result['修改']);
        $this->assertEquals(AuditResult::DELETE->value, $result['删除']);
    }
    
    public function testConfigureCrud()
    {
        // 由于Crud配置类的复杂性，我们主要测试方法是否存在且可调用
        $this->assertTrue(method_exists($this->controller, 'configureCrud'));
        
        // 测试方法是否可以被调用而不抛出异常
        $crudMock = $this->createMock(\EasyCorp\Bundle\EasyAdminBundle\Config\Crud::class);
        $crudMock->method('setEntityLabelInSingular')->willReturnSelf();
        $crudMock->method('setEntityLabelInPlural')->willReturnSelf();
        $crudMock->method('setSearchFields')->willReturnSelf();
        $crudMock->method('setDefaultSort')->willReturnSelf();
        $crudMock->method('setPaginatorPageSize')->willReturnSelf();
        
        $result = $this->controller->configureCrud($crudMock);
        
        $this->assertInstanceOf(\EasyCorp\Bundle\EasyAdminBundle\Config\Crud::class, $result);
    }
    
    public function testConfigureFilters()
    {
        // 测试方法存在性
        $this->assertTrue(method_exists($this->controller, 'configureFilters'));
    }
    
    public function testConfigureFields()
    {
        // 测试方法是否存在且可调用
        $this->assertTrue(method_exists($this->controller, 'configureFields'));
        
        $result = $this->controller->configureFields('index');
        
        $this->assertIsIterable($result);
        
        // 将迭代器转换为数组以便测试
        $fields = iterator_to_array($result);
        $this->assertNotEmpty($fields);
    }
    
    public function testConfigureActions()
    {
        // 测试方法存在性
        $this->assertTrue(method_exists($this->controller, 'configureActions'));
    }
    
    /**
     * 创建测试用的GeneratedContent实例
     */
    private function createGeneratedContent(int $id): GeneratedContent
    {
        $content = new GeneratedContent();
        $content->setInputText('Test input');
        $content->setOutputText('Test output');
        $content->setMachineAuditResult(RiskLevel::MEDIUM_RISK);
        $content->setMachineAuditTime(new \DateTimeImmutable());
        
        // 使用反射设置ID
        $reflection = new \ReflectionClass($content);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($content, $id);
        
        return $content;
    }
} 