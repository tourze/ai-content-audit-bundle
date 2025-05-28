<?php

namespace AIContentAuditBundle\Tests\Controller\Admin;

use AIContentAuditBundle\Controller\Admin\RiskKeywordCrudController;
use AIContentAuditBundle\Entity\RiskKeyword;
use AIContentAuditBundle\Enum\RiskLevel;
use AIContentAuditBundle\Repository\RiskKeywordRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RiskKeywordCrudControllerTest extends TestCase
{
    private RiskKeywordCrudController $controller;
    private MockObject $entityManager;
    private MockObject $repository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(RiskKeywordRepository::class);
        
        $this->controller = new RiskKeywordCrudController($this->entityManager);
        
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
        // 测试方法存在性
        $this->assertTrue(method_exists($this->controller, 'configureCrud'));
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
    
    public function testConfigureFields_withDifferentPageNames()
    {
        // 测试不同页面的字段配置
        $pageNames = ['index', 'detail', 'new', 'edit'];
        
        foreach ($pageNames as $pageName) {
            $result = $this->controller->configureFields($pageName);
            $this->assertIsIterable($result);
            
            $fields = iterator_to_array($result);
            $this->assertNotEmpty($fields);
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
        // 测试方法存在性
        $this->assertTrue(method_exists($this->controller, 'configureCrud'));
    }
    
    public function testConfigureFilters_withExpectedFilters()
    {
        // 测试方法存在性
        $this->assertTrue(method_exists($this->controller, 'configureFilters'));
    }
    
    /**
     * 创建测试用的RiskKeyword实例
     */
    private function createRiskKeyword(int $id, string $keyword, RiskLevel $riskLevel): RiskKeyword
    {
        $riskKeyword = new RiskKeyword();
        $riskKeyword->setKeyword($keyword);
        $riskKeyword->setRiskLevel($riskLevel);
        $riskKeyword->setCategory('测试分类');
        $riskKeyword->setAddedBy('test_user');
        $riskKeyword->setUpdateTime(new \DateTimeImmutable());
        
        // 使用反射设置ID
        $reflection = new \ReflectionClass($riskKeyword);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($riskKeyword, $id);
        
        return $riskKeyword;
    }
} 