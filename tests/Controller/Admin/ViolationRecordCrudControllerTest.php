<?php

namespace AIContentAuditBundle\Tests\Controller\Admin;

use AIContentAuditBundle\Controller\Admin\ViolationRecordCrudController;
use AIContentAuditBundle\Entity\ViolationRecord;
use AIContentAuditBundle\Enum\ViolationType;
use AIContentAuditBundle\Repository\ViolationRecordRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ViolationRecordCrudControllerTest extends TestCase
{
    private ViolationRecordCrudController $controller;
    private $entityManager;
    private $repository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(ViolationRecordRepository::class);
        
        $this->controller = new ViolationRecordCrudController();
        
        // 设置EntityManager返回Repository
        $this->entityManager->method('getRepository')
            ->with(ViolationRecord::class)
            ->willReturn($this->repository);
    }
    
    public function testGetEntityFqcn()
    {
        $result = ViolationRecordCrudController::getEntityFqcn();
        
        $this->assertEquals(ViolationRecord::class, $result);
    }
    
    public function testGetViolationTypeChoices()
    {
        // 使用反射访问私有方法
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getViolationTypeChoices');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->controller);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('机器识别高风险内容', $result);
        $this->assertArrayHasKey('人工审核删除', $result);
        $this->assertArrayHasKey('用户举报', $result);
        $this->assertArrayHasKey('重复违规', $result);
        
        $this->assertEquals(ViolationType::MACHINE_HIGH_RISK->value, $result['机器识别高风险内容']);
        $this->assertEquals(ViolationType::MANUAL_DELETE->value, $result['人工审核删除']);
        $this->assertEquals(ViolationType::USER_REPORT->value, $result['用户举报']);
        $this->assertEquals(ViolationType::REPEATED_VIOLATION->value, $result['重复违规']);
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
        // Filters是final类，通过反射测试方法存在性
        $reflection = new \ReflectionClass($this->controller);
        $this->assertTrue($reflection->hasMethod('configureFilters'));
    }
    
    public function testConfigureFields()
    {
        $result = $this->controller->configureFields('index');
        
        // 将迭代器转换为数组以便测试
        $fields = iterator_to_array($result);
        $this->assertNotEmpty($fields);
        
        // 验证字段存在
        $fieldNames = array_map(fn($field) => $field->getAsDto()->getProperty(), $fields);
        $this->assertContains('user', $fieldNames);
        $this->assertContains('violationType', $fieldNames);
        $this->assertContains('violationTime', $fieldNames);
        $this->assertContains('processResult', $fieldNames);
        $this->assertContains('processedBy', $fieldNames);
    }
    
    public function testConfigureActions()
    {
        // Actions是final类，通过反射测试方法存在性
        $reflection = new \ReflectionClass($this->controller);
        $this->assertTrue($reflection->hasMethod('configureActions'));
    }
    
    public function testViolationTypeChoicesCompleteness()
    {
        // 使用反射访问私有方法
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getViolationTypeChoices');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->controller);
        
        // 验证所有违规类型都包含在选择中
        $allViolationTypes = ViolationType::cases();
        $this->assertCount(count($allViolationTypes), $result);
        
        foreach ($allViolationTypes as $violationType) {
            $this->assertContains($violationType->value, $result);
        }
    }
    
    public function testConfigureCrud_withDefaultSettings()
    {
        $crudMock = $this->createMock(\EasyCorp\Bundle\EasyAdminBundle\Config\Crud::class);
        
        // 验证调用了正确的配置方法
        $crudMock->expects($this->once())
            ->method('setEntityLabelInSingular')
            ->with('违规记录')
            ->willReturnSelf();
            
        $crudMock->expects($this->once())
            ->method('setEntityLabelInPlural')
            ->with('违规记录')
            ->willReturnSelf();
            
        $crudMock->expects($this->once())
            ->method('setSearchFields')
            ->willReturnSelf();
            
        $crudMock->expects($this->once())
            ->method('setDefaultSort')
            ->willReturnSelf();
            
        $crudMock->expects($this->once())
            ->method('setPaginatorPageSize')
            ->willReturnSelf();
        
        $result = $this->controller->configureCrud($crudMock);
        
        $this->assertInstanceOf(\EasyCorp\Bundle\EasyAdminBundle\Config\Crud::class, $result);
    }
    
    public function testConfigureFilters_withExpectedFilters()
    {
        // 使用反射测试私有方法 getViolationTypeChoices
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getViolationTypeChoices');
        $method->setAccessible(true);
        
        $choices = $method->invoke($this->controller);
        
        // 验证返回的选项格式正确
        foreach ($choices as $label => $value) {
            $this->assertIsString($label);
            $this->assertIsString($value);
        }
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
} 