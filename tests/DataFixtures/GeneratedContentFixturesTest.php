<?php

namespace AIContentAuditBundle\Tests\DataFixtures;

use AIContentAuditBundle\DataFixtures\GeneratedContentFixtures;
use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Entity\RiskKeyword;
use AIContentAuditBundle\Enum\AuditResult;
use AIContentAuditBundle\Enum\RiskLevel;
use BizUserBundle\DataFixtures\BizUserFixtures;
use BizUserBundle\Entity\BizUser;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GeneratedContentFixturesTest extends TestCase
{
    private GeneratedContentFixtures $fixtures;
    private MockObject $objectManager;
    private MockObject $user;
    private MockObject $riskKeyword;

    protected function setUp(): void
    {
        $this->fixtures = new GeneratedContentFixtures();
        $this->objectManager = $this->createMock(ObjectManager::class);
        $this->user = $this->createMock(BizUser::class);
        $this->riskKeyword = $this->createMock(RiskKeyword::class);
        
        // 设置RiskKeyword的getKeyword方法
        $this->riskKeyword->method('getKeyword')
            ->willReturn('测试关键词');
        
        // Mock ReferenceRepository for AbstractFixture
        $referenceRepository = $this->createMock(\Doctrine\Common\DataFixtures\ReferenceRepository::class);
        $referenceRepository->method('addReference');
        $referenceRepository->method('getReference')->willReturn($this->createMock(GeneratedContent::class));
        
        // 使用反射设置ReferenceRepository
        $reflection = new \ReflectionClass($this->fixtures);
        $property = $reflection->getProperty('referenceRepository');
        $property->setAccessible(true);
        $property->setValue($this->fixtures, $referenceRepository);
    }
    
    public function testLoad()
    {
        // 设置getReference方法的期望
        $this->fixtures = $this->getMockBuilder(GeneratedContentFixtures::class)
            ->onlyMethods(['getReference'])
            ->getMock();
            
        // 配置getReference方法返回值
        $this->fixtures->method('getReference')
            ->willReturnCallback(function ($reference, $class) {
                if ($class === BizUser::class) {
                    return $this->user;
                } elseif ($class === RiskKeyword::class) {
                    return $this->riskKeyword;
                }
                return null;
            });
        
        // Mock ReferenceRepository for AbstractFixture
        $referenceRepository = $this->createMock(\Doctrine\Common\DataFixtures\ReferenceRepository::class);
        $referenceRepository->method('addReference');
        
        // 使用反射设置ReferenceRepository
        $reflection = new \ReflectionClass($this->fixtures);
        $property = $reflection->getProperty('referenceRepository');
        $property->setAccessible(true);
        $property->setValue($this->fixtures, $referenceRepository);
        
        // 期望persist被调用100次（创建100条记录）
        $this->objectManager->expects($this->exactly(100))
            ->method('persist')
            ->with($this->isInstanceOf(GeneratedContent::class));
            
        // 期望flush被调用一次
        $this->objectManager->expects($this->once())
            ->method('flush');
        
        // 执行load方法
        $this->fixtures->load($this->objectManager);
        
        // 验证没有抛出异常
        $this->assertTrue(true);
    }
    
    public function testGetDependencies()
    {
        $dependencies = $this->fixtures->getDependencies();
        $this->assertContains(BizUserFixtures::class, $dependencies);
        $this->assertContains(\AIContentAuditBundle\DataFixtures\RiskKeywordFixtures::class, $dependencies);
    }
    
    public function testGetGroups()
    {
        $groups = GeneratedContentFixtures::getGroups();
        $this->assertContains('ai-content-audit', $groups);
    }
    
    public function testGetRandomRiskLevel()
    {
        // 使用反射访问私有方法
        $reflection = new \ReflectionClass($this->fixtures);
        $method = $reflection->getMethod('getRandomRiskLevel');
        $method->setAccessible(true);
        
        // 测试不同索引的风险等级分布
        
        // 测试无风险范围 (1-70)
        $result = $method->invoke($this->fixtures, 35);
        $this->assertEquals(RiskLevel::NO_RISK, $result);
        
        $result = $method->invoke($this->fixtures, 70);
        $this->assertEquals(RiskLevel::NO_RISK, $result);
        
        // 测试低风险范围 (71-85)
        $result = $method->invoke($this->fixtures, 75);
        $this->assertEquals(RiskLevel::LOW_RISK, $result);
        
        $result = $method->invoke($this->fixtures, 85);
        $this->assertEquals(RiskLevel::LOW_RISK, $result);
        
        // 测试中风险范围 (86-95)
        $result = $method->invoke($this->fixtures, 90);
        $this->assertEquals(RiskLevel::MEDIUM_RISK, $result);
        
        $result = $method->invoke($this->fixtures, 95);
        $this->assertEquals(RiskLevel::MEDIUM_RISK, $result);
        
        // 测试高风险范围 (96-100)
        $result = $method->invoke($this->fixtures, 98);
        $this->assertEquals(RiskLevel::HIGH_RISK, $result);
        
        $result = $method->invoke($this->fixtures, 100);
        $this->assertEquals(RiskLevel::HIGH_RISK, $result);
    }
    
    public function testGetRandomManualAuditResult()
    {
        // 使用反射访问私有方法
        $reflection = new \ReflectionClass($this->fixtures);
        $method = $reflection->getMethod('getRandomManualAuditResult');
        $method->setAccessible(true);
        
        // 由于方法使用随机数，我们测试多次调用确保返回的都是有效的AuditResult
        $validResults = [AuditResult::PASS, AuditResult::MODIFY, AuditResult::DELETE];
        
        for ($i = 0; $i < 10; $i++) {
            $result = $method->invoke($this->fixtures);
            $this->assertInstanceOf(AuditResult::class, $result);
            $this->assertContains($result, $validResults);
        }
    }
    
    public function testGenerateContent()
    {
        // 使用反射访问私有方法
        $reflection = new \ReflectionClass($this->fixtures);
        $method = $reflection->getMethod('generateContent');
        $method->setAccessible(true);
        
        // 创建Faker实例
        $faker = \Faker\Factory::create('zh_CN');
        
        // 测试无风险内容生成
        $result = $method->invoke($this->fixtures, RiskLevel::NO_RISK, $faker);
        $this->assertCount(2, $result);
        
        [$inputText, $outputText] = $result;

        $this->assertNotEmpty($inputText);
        $this->assertNotEmpty($outputText);
        
        // 修正正则表达式，允许更多的Faker生成内容
        $this->assertMatchesRegularExpression('/请|帮|如何|详细|分析|介绍|了解|想|能|可以|关于/', $inputText);
    }
    
    public function testGenerateContentWithHighRisk()
    {
        // 这个测试需要实际的RiskKeyword，而不是GeneratedContent
        // 由于Fixtures中使用了getReference获取RiskKeyword，我们需要正确设置
        $this->fixtures = $this->getMockBuilder(GeneratedContentFixtures::class)
            ->onlyMethods(['getReference'])
            ->getMock();
            
        // 配置getReference方法返回正确的对象类型
        $this->fixtures->method('getReference')
            ->willReturnCallback(function ($reference, $class) {
                if ($class === \BizUserBundle\Entity\BizUser::class) {
                    return $this->user;
                } elseif ($class === \AIContentAuditBundle\Entity\RiskKeyword::class) {
                    return $this->riskKeyword; // 这个应该是RiskKeyword，不是GeneratedContent
                }
                return null;
            });
        
        // 使用反射访问私有方法
        $reflection = new \ReflectionClass($this->fixtures);
        $method = $reflection->getMethod('generateContent');
        $method->setAccessible(true);
        
        // 创建Faker实例
        $faker = \Faker\Factory::create('zh_CN');
        
        // 测试高风险内容生成
        $result = $method->invoke($this->fixtures, RiskLevel::HIGH_RISK, $faker);
        $this->assertCount(2, $result);
        
        [$inputText, $outputText] = $result;

        $this->assertNotEmpty($inputText);
        $this->assertNotEmpty($outputText);
    }
    
    public function testGenerateContentWithMediumRisk()
    {
        // 同样修复Medium Risk测试
        $this->fixtures = $this->getMockBuilder(GeneratedContentFixtures::class)
            ->onlyMethods(['getReference'])
            ->getMock();
            
        // 配置getReference方法返回正确的对象类型
        $this->fixtures->method('getReference')
            ->willReturnCallback(function ($reference, $class) {
                if ($class === \BizUserBundle\Entity\BizUser::class) {
                    return $this->user;
                } elseif ($class === \AIContentAuditBundle\Entity\RiskKeyword::class) {
                    return $this->riskKeyword;
                }
                return null;
            });
        
        // 使用反射访问私有方法
        $reflection = new \ReflectionClass($this->fixtures);
        $method = $reflection->getMethod('generateContent');
        $method->setAccessible(true);
        
        // 创建Faker实例
        $faker = \Faker\Factory::create('zh_CN');
        
        // 测试中风险内容生成
        $result = $method->invoke($this->fixtures, RiskLevel::MEDIUM_RISK, $faker);
        $this->assertCount(2, $result);
        
        [$inputText, $outputText] = $result;

        $this->assertNotEmpty($inputText);
        $this->assertNotEmpty($outputText);
    }
    
    public function testConstants()
    {
        // 测试常量是否正确定义
        $this->assertEquals('generated-content-', GeneratedContentFixtures::CONTENT_REFERENCE_PREFIX);
        
        // 使用反射访问私有常量
        $reflection = new \ReflectionClass($this->fixtures);
        
        $inputTemplates = $reflection->getConstant('INPUT_TEMPLATES');
        $this->assertNotEmpty($inputTemplates);
        
        $topics = $reflection->getConstant('TOPICS');
        $this->assertNotEmpty($topics);
        
        $sensitiveTopics = $reflection->getConstant('SENSITIVE_TOPICS');
        $this->assertNotEmpty($sensitiveTopics);
    }
    
    public function testLoadWithMockedReferences()
    {
        // 创建一个部分Mock，只模拟getReference方法
        $fixturesMock = $this->getMockBuilder(GeneratedContentFixtures::class)
            ->onlyMethods(['getReference', 'addReference'])
            ->getMock();
            
        // 配置getReference方法
        $fixturesMock->method('getReference')
            ->willReturnCallback(function ($reference, $class) {
                if ($class === BizUser::class) {
                    return $this->user;
                } elseif ($class === RiskKeyword::class) {
                    return $this->riskKeyword;
                }
                return null;
            });
            
        // 配置addReference方法
        $fixturesMock->method('addReference');
        
        // 期望persist被调用100次
        $this->objectManager->expects($this->exactly(100))
            ->method('persist')
            ->with($this->isInstanceOf(GeneratedContent::class));
            
        // 期望flush被调用一次
        $this->objectManager->expects($this->once())
            ->method('flush');
        
        // 执行load方法
        $fixturesMock->load($this->objectManager);
        
        // 验证执行成功
        $this->assertTrue(true);
    }
    
    public function testRiskLevelDistribution()
    {
        // 使用反射访问私有方法
        $reflection = new \ReflectionClass($this->fixtures);
        $method = $reflection->getMethod('getRandomRiskLevel');
        $method->setAccessible(true);
        
        // 统计风险等级分布
        $distribution = [
            RiskLevel::NO_RISK->value => 0,
            RiskLevel::LOW_RISK->value => 0,
            RiskLevel::MEDIUM_RISK->value => 0,
            RiskLevel::HIGH_RISK->value => 0,
        ];
        
        for ($i = 1; $i <= 100; $i++) {
            $riskLevel = $method->invoke($this->fixtures, $i);
            $distribution[$riskLevel->value]++;
        }
        
        // 验证分布符合预期：70%无风险，15%低风险，10%中风险，5%高风险
        $this->assertEquals(70, $distribution[RiskLevel::NO_RISK->value]);
        $this->assertEquals(15, $distribution[RiskLevel::LOW_RISK->value]);
        $this->assertEquals(10, $distribution[RiskLevel::MEDIUM_RISK->value]);
        $this->assertEquals(5, $distribution[RiskLevel::HIGH_RISK->value]);
    }
    
    public function testManualAuditResultDistribution()
    {
        // 使用反射访问私有方法
        $reflection = new \ReflectionClass($this->fixtures);
        $method = $reflection->getMethod('getRandomManualAuditResult');
        $method->setAccessible(true);
        
        // 由于使用随机数，我们测试多次调用的结果分布
        $distribution = [
            AuditResult::PASS->value => 0,
            AuditResult::MODIFY->value => 0,
            AuditResult::DELETE->value => 0,
        ];
        
        // 模拟1000次调用以获得统计意义
        for ($i = 0; $i < 1000; $i++) {
            $result = $method->invoke($this->fixtures);
            $distribution[$result->value]++;
        }
        
        // 验证分布大致符合预期（允许一定误差）
        $this->assertGreaterThan(500, $distribution[AuditResult::PASS->value]); // 应该约60%
        $this->assertGreaterThan(200, $distribution[AuditResult::MODIFY->value]); // 应该约30%
        $this->assertGreaterThan(50, $distribution[AuditResult::DELETE->value]); // 应该约10%
    }
} 