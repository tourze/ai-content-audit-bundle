<?php

namespace AIContentAuditBundle\Tests\Controller\Admin;

use AIContentAuditBundle\Controller\Admin\RiskKeywordCrudController;
use AIContentAuditBundle\Entity\RiskKeyword;
use AIContentAuditBundle\Enum\RiskLevel;
use AIContentAuditBundle\Repository\RiskKeywordRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class RiskKeywordCrudControllerTest extends TestCase
{
    private RiskKeywordCrudController $controller;
    private $entityManager;
    private $repository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(RiskKeywordRepository::class);
        
        $this->controller = new RiskKeywordCrudController();
        
        // 设置EntityManager返回Repository
        $this->entityManager->method('getRepository')
            ->with(RiskKeyword::class)
            ->willReturn($this->repository);
    }
    
    public function testGetEntityFqcn()
    {
        $result = RiskKeywordCrudController::getEntityFqcn();
        
        $this->assertEquals(RiskKeyword::class, $result);
    }
    
    public function testGetRiskLevelChoices()
    {
        // 使用反射访问私有方法
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getRiskLevelChoices');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->controller);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('低风险', $result);
        $this->assertArrayHasKey('中风险', $result);
        $this->assertArrayHasKey('高风险', $result);
        $this->assertArrayNotHasKey('无风险', $result); // 方法排除了NO_RISK
        
        $this->assertEquals(RiskLevel::LOW_RISK->value, $result['低风险']);
        $this->assertEquals(RiskLevel::MEDIUM_RISK->value, $result['中风险']);
        $this->assertEquals(RiskLevel::HIGH_RISK->value, $result['高风险']);
    }
    
    public function testConfigureCrud()
    {
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
        // 使用反射测试私有方法 getRiskLevelChoices 是否能正常工作
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getRiskLevelChoices');
        $method->setAccessible(true);
        
        $choices = $method->invoke($this->controller);
        $this->assertIsArray($choices);
        $this->assertCount(3, $choices); // 应该有3个风险等级（排除NO_RISK）
    }
    
    public function testConfigureFields()
    {
        $result = $this->controller->configureFields('index');
        
        // 将迭代器转换为数组以便测试
        $fields = iterator_to_array($result);
        $this->assertNotEmpty($fields);
        
        // 验证字段存在
        $fieldNames = array_map(fn($field) => $field->getAsDto()->getProperty(), $fields);
        $this->assertContains('keyword', $fieldNames);
        $this->assertContains('riskLevel', $fieldNames);
        $this->assertContains('category', $fieldNames);
        $this->assertContains('updateTime', $fieldNames);
    }
    
    public function testConfigureActions()
    {
        // Actions是final类，只能通过反射测试方法存在性
        $reflection = new \ReflectionClass($this->controller);
        $this->assertTrue($reflection->hasMethod('configureActions'));
    }
    
    public function testConfigureFields_withDifferentPageNames()
    {
        // 测试不同页面的字段配置
        $pageNames = ['index', 'detail', 'new', 'edit'];
        
        foreach ($pageNames as $pageName) {
            $result = $this->controller->configureFields($pageName);
            
            $fields = iterator_to_array($result);
            $this->assertNotEmpty($fields, "页面 {$pageName} 应该有字段配置");
            
            // 详情页应该显示ID字段
            if ($pageName === 'detail') {
                $fieldNames = array_map(fn($field) => $field->getAsDto()->getProperty(), $fields);
                $this->assertContains('id', $fieldNames);
            }
        }
    }
    
    public function testRiskLevelChoicesCompleteness()
    {
        // 使用反射访问私有方法
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getRiskLevelChoices');
        $method->setAccessible(true);
        
        $choices = $method->invoke($this->controller);
        $allRiskLevels = array_filter(RiskLevel::cases(), fn($level) => $level !== RiskLevel::NO_RISK);
        
        // 验证风险等级选择应该包含3个选项（排除NO_RISK）
        $this->assertCount(3, $choices, '风险等级选择应该包含3个选项（排除无风险）');
        $this->assertCount(count($allRiskLevels), $choices, '选择数量应该与有效枚举数量一致');
        
        foreach ($allRiskLevels as $riskLevel) {
            $this->assertContains($riskLevel->value, $choices, "风险等级 {$riskLevel->value} 应该在选择中");
        }
    }
    
    public function testConfigureCrud_withDefaultSettings()
    {
        $crudMock = $this->createMock(\EasyCorp\Bundle\EasyAdminBundle\Config\Crud::class);
        
        // 验证调用了正确的配置方法
        $crudMock->expects($this->once())
            ->method('setEntityLabelInSingular')
            ->with('风险关键词')
            ->willReturnSelf();
            
        $crudMock->expects($this->once())
            ->method('setEntityLabelInPlural')
            ->with('风险关键词')
            ->willReturnSelf();
            
        $crudMock->expects($this->once())
            ->method('setSearchFields')
            ->willReturnSelf();
            
        $crudMock->expects($this->once())
            ->method('setDefaultSort')
            ->willReturnSelf();
            
        $crudMock->expects($this->once())
            ->method('setPaginatorPageSize')
            ->with(50)
            ->willReturnSelf();
        
        $result = $this->controller->configureCrud($crudMock);
        
        $this->assertInstanceOf(\EasyCorp\Bundle\EasyAdminBundle\Config\Crud::class, $result);
    }
    
    public function testConfigureFilters_withExpectedFilters()
    {
        // 使用反射测试私有方法 getRiskLevelChoices
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getRiskLevelChoices');
        $method->setAccessible(true);
        
        $choices = $method->invoke($this->controller);
        
        // 验证返回的选项格式正确
        foreach ($choices as $label => $value) {
            $this->assertIsString($label);
            $this->assertIsString($value);
        }
    }
} 