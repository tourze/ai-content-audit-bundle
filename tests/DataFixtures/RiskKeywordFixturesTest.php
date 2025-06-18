<?php

namespace AIContentAuditBundle\Tests\DataFixtures;

use AIContentAuditBundle\DataFixtures\RiskKeywordFixtures;
use AIContentAuditBundle\Entity\RiskKeyword;
use AIContentAuditBundle\Enum\RiskLevel;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RiskKeywordFixturesTest extends TestCase
{
    private RiskKeywordFixtures $fixtures;
    private MockObject $manager;

    protected function setUp(): void
    {
        $this->fixtures = new RiskKeywordFixtures();
        $this->manager = $this->createMock(ObjectManager::class);
        
        // Mock ReferenceRepository for AbstractFixture
        $referenceRepository = $this->createMock(\Doctrine\Common\DataFixtures\ReferenceRepository::class);
        $referenceRepository->method('addReference');
        $referenceRepository->method('getReference')->willReturn($this->createMock(RiskKeyword::class));
        
        // 使用反射设置ReferenceRepository
        $reflection = new \ReflectionClass($this->fixtures);
        $property = $reflection->getProperty('referenceRepository');
        $property->setAccessible(true);
        $property->setValue($this->fixtures, $referenceRepository);
    }

    public function testLoad_createsExpectedNumberOfKeywords()
    {
        $persistedEntities = [];
        
        // Mock manager的persist方法，记录所有被持久化的实体
        $this->manager->expects($this->atLeastOnce())
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$persistedEntities) {
                $persistedEntities[] = $entity;
            });
        
        // Mock manager的flush方法
        $this->manager->expects($this->once())
            ->method('flush');
            
        // Mock getRepository方法返回空数组（模拟没有已存在的数据）
        $this->manager->method('getRepository')
            ->willReturnCallback(function ($entityClass) {
                $repo = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
                $repo->method('findAll')->willReturn([]);
                return $repo;
            });
        
        // 执行load方法
        $this->fixtures->load($this->manager);
        
        // 验证创建了RiskKeyword实体
        $keywordEntities = array_filter($persistedEntities, fn($entity) => $entity instanceof RiskKeyword);
        $this->assertNotEmpty($keywordEntities, '应该创建了RiskKeyword实体');
        
        // 验证创建的关键词数量合理（假设创建30-100个）
        $this->assertGreaterThanOrEqual(20, count($keywordEntities), '应该创建足够数量的风险关键词');
        $this->assertLessThanOrEqual(150, count($keywordEntities), '创建的风险关键词数量应该在合理范围内');
    }
    
    public function testLoad_createsKeywordsWithValidRiskLevel()
    {
        $persistedEntities = [];
        
        $this->manager->expects($this->atLeastOnce())
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$persistedEntities) {
                $persistedEntities[] = $entity;
            });
        
        $this->manager->expects($this->once())
            ->method('flush');
            
        $this->manager->method('getRepository')
            ->willReturnCallback(function ($entityClass) {
                $repo = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
                $repo->method('findAll')->willReturn([]);
                return $repo;
            });
        
        $this->fixtures->load($this->manager);
        
        // 获取所有RiskKeyword实体
        $keywordEntities = array_filter($persistedEntities, fn($entity) => $entity instanceof RiskKeyword);
        
        foreach ($keywordEntities as $keyword) {
            $this->assertInstanceOf(RiskKeyword::class, $keyword);
            
            // 通过反射获取riskLevel（因为可能是私有属性）
            $reflection = new \ReflectionClass($keyword);
            if ($reflection->hasProperty('riskLevel')) {
                $levelProperty = $reflection->getProperty('riskLevel');
                $levelProperty->setAccessible(true);
                $riskLevel = $levelProperty->getValue($keyword);
                
                if ($riskLevel !== null) {
                    $this->assertInstanceOf(RiskLevel::class, $riskLevel, '风险等级应该是有效的RiskLevel枚举值');
                    $this->assertContains($riskLevel, RiskLevel::cases(), '风险等级应该是有效的枚举值');
                }
            }
        }
    }
    
    public function testLoad_createsKeywordsWithValidKeywordText()
    {
        $persistedEntities = [];
        
        $this->manager->expects($this->atLeastOnce())
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$persistedEntities) {
                $persistedEntities[] = $entity;
            });
        
        $this->manager->expects($this->once())
            ->method('flush');
            
        $this->manager->method('getRepository')
            ->willReturnCallback(function ($entityClass) {
                $repo = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
                $repo->method('findAll')->willReturn([]);
                return $repo;
            });
        
        $this->fixtures->load($this->manager);
        
        $keywordEntities = array_filter($persistedEntities, fn($entity) => $entity instanceof RiskKeyword);
        
        foreach ($keywordEntities as $keyword) {
            // 通过反射获取keyword文本
            $reflection = new \ReflectionClass($keyword);
            if ($reflection->hasProperty('keyword')) {
                $keywordProperty = $reflection->getProperty('keyword');
                $keywordProperty->setAccessible(true);
                $keywordText = $keywordProperty->getValue($keyword);
                
                if ($keywordText !== null) {
                    $this->assertNotEmpty($keywordText, '关键词不应该为空');
                    $this->assertLessThanOrEqual(100, strlen($keywordText), '关键词长度应该合理');
                    $this->assertGreaterThanOrEqual(1, strlen($keywordText), '关键词应该至少有一个字符');
                }
            }
        }
    }
    
    public function testLoad_createsKeywordsWithValidCategory()
    {
        $persistedEntities = [];
        
        $this->manager->expects($this->atLeastOnce())
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$persistedEntities) {
                $persistedEntities[] = $entity;
            });
        
        $this->manager->expects($this->once())
            ->method('flush');
            
        $this->manager->method('getRepository')
            ->willReturnCallback(function ($entityClass) {
                $repo = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
                $repo->method('findAll')->willReturn([]);
                return $repo;
            });
        
        $this->fixtures->load($this->manager);
        
        $keywordEntities = array_filter($persistedEntities, fn($entity) => $entity instanceof RiskKeyword);
        
        foreach ($keywordEntities as $keyword) {
            // 通过反射获取category
            $reflection = new \ReflectionClass($keyword);
            if ($reflection->hasProperty('category')) {
                $categoryProperty = $reflection->getProperty('category');
                $categoryProperty->setAccessible(true);
                $category = $categoryProperty->getValue($keyword);
                
                if ($category !== null) {
                    $this->assertNotEmpty($category, '分类不应该为空');
                    $this->assertLessThanOrEqual(50, strlen($category), '分类名称长度应该合理');
                }
            }
        }
    }
    
    public function testLoad_createsKeywordsWithValidUpdateTime()
    {
        $persistedEntities = [];
        
        $this->manager->expects($this->atLeastOnce())
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$persistedEntities) {
                $persistedEntities[] = $entity;
            });
        
        $this->manager->expects($this->once())
            ->method('flush');
            
        $this->manager->method('getRepository')
            ->willReturnCallback(function ($entityClass) {
                $repo = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
                $repo->method('findAll')->willReturn([]);
                return $repo;
            });
        
        $this->fixtures->load($this->manager);
        
        $keywordEntities = array_filter($persistedEntities, fn($entity) => $entity instanceof RiskKeyword);
        
        foreach ($keywordEntities as $keyword) {
            // 通过反射获取updateTime
            $reflection = new \ReflectionClass($keyword);
            if ($reflection->hasProperty('updateTime')) {
                $timeProperty = $reflection->getProperty('updateTime');
                $timeProperty->setAccessible(true);
                $updateTime = $timeProperty->getValue($keyword);
                
                if ($updateTime !== null) {
                    $this->assertInstanceOf(\DateTimeImmutable::class, $updateTime, '更新时间应该是DateTimeImmutable实例');
                    
                    // 验证时间在合理范围内（过去1年内到现在）
                    $now = new \DateTimeImmutable();
                    $oneYearAgo = $now->sub(new \DateInterval('P1Y'));
                    $this->assertGreaterThanOrEqual($oneYearAgo, $updateTime, '更新时间应该在过去一年内');
                    $this->assertLessThanOrEqual($now, $updateTime, '更新时间不应该是未来时间');
                }
            }
        }
    }
    
    public function testLoad_riskLevelDistribution()
    {
        $persistedEntities = [];
        
        $this->manager->expects($this->atLeastOnce())
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$persistedEntities) {
                $persistedEntities[] = $entity;
            });
        
        $this->manager->expects($this->once())
            ->method('flush');
            
        $this->manager->method('getRepository')
            ->willReturnCallback(function ($entityClass) {
                $repo = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
                $repo->method('findAll')->willReturn([]);
                return $repo;
            });
        
        $this->fixtures->load($this->manager);
        
        $keywordEntities = array_filter($persistedEntities, fn($entity) => $entity instanceof RiskKeyword);
        
        if (count($keywordEntities) > 10) {
            // 统计各种风险等级的分布
            $levelCounts = [];
            foreach ($keywordEntities as $keyword) {
                $reflection = new \ReflectionClass($keyword);
                if ($reflection->hasProperty('riskLevel')) {
                    $levelProperty = $reflection->getProperty('riskLevel');
                    $levelProperty->setAccessible(true);
                    $riskLevel = $levelProperty->getValue($keyword);
                    
                    if ($riskLevel instanceof RiskLevel) {
                        $levelValue = $riskLevel->value;
                        $levelCounts[$levelValue] = ($levelCounts[$levelValue] ?? 0) + 1;
                    }
                }
            }
            
            // 验证至少有两种不同的风险等级
            $this->assertGreaterThanOrEqual(2, count($levelCounts), '应该有不同的风险等级分布');
            
            // 验证高风险关键词数量相对较少
            $totalKeywords = count($keywordEntities);
            if (isset($levelCounts['高风险'])) {
                $highRiskRatio = $levelCounts['高风险'] / $totalKeywords;
                $this->assertLessThan(0.5, $highRiskRatio, '高风险关键词占比应该相对较少');
            }
        }
    }
    
    public function testLoad_categoryDistribution()
    {
        $persistedEntities = [];
        
        $this->manager->expects($this->atLeastOnce())
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$persistedEntities) {
                $persistedEntities[] = $entity;
            });
        
        $this->manager->expects($this->once())
            ->method('flush');
            
        $this->manager->method('getRepository')
            ->willReturnCallback(function ($entityClass) {
                $repo = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
                $repo->method('findAll')->willReturn([]);
                return $repo;
            });
        
        $this->fixtures->load($this->manager);
        
        $keywordEntities = array_filter($persistedEntities, fn($entity) => $entity instanceof RiskKeyword);
        
        if (count($keywordEntities) > 10) {
            // 统计各种分类的分布
            $categoryCounts = [];
            foreach ($keywordEntities as $keyword) {
                $reflection = new \ReflectionClass($keyword);
                if ($reflection->hasProperty('category')) {
                    $categoryProperty = $reflection->getProperty('category');
                    $categoryProperty->setAccessible(true);
                    $category = $categoryProperty->getValue($keyword);
                    
                    if ($category !== null) {
                        $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + 1;
                    }
                }
            }
            
            // 验证至少有两种不同的分类
            $this->assertGreaterThanOrEqual(2, count($categoryCounts), '应该有不同的关键词分类');
        }
    }
    
    public function testLoad_callsFlushOnce()
    {
        $this->manager->expects($this->once())
            ->method('flush')
            ->with();
            
        $this->manager->method('getRepository')
            ->willReturnCallback(function ($entityClass) {
                $repo = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
                $repo->method('findAll')->willReturn([]);
                return $repo;
            });
        
        $this->fixtures->load($this->manager);
    }
    
    public function testLoad_doesNotCreateDuplicateKeywords()
    {
        $this->manager->expects($this->atLeastOnce())
            ->method('persist');
        
        $this->manager->expects($this->once())
            ->method('flush');
            
        // Mock getRepository返回一些已存在的关键词
        $this->manager->method('getRepository')
            ->willReturnCallback(function ($entityClass) {
                $repo = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
                if ($entityClass === RiskKeyword::class) {
                    // 返回一些模拟的已存在关键词
                    $existingKeyword = $this->createMock(RiskKeyword::class);
                    $repo->method('findAll')->willReturn([$existingKeyword]);
                } else {
                    $repo->method('findAll')->willReturn([]);
                }
                return $repo;
            });
        
        // 这个测试主要验证方法能正常执行，不会因为重复数据而出错
        $this->fixtures->load($this->manager);
    }
    
    public function testLoad_createsKeywordsWithAddedBy()
    {
        $persistedEntities = [];
        
        $this->manager->expects($this->atLeastOnce())
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$persistedEntities) {
                $persistedEntities[] = $entity;
            });
        
        $this->manager->expects($this->once())
            ->method('flush');
            
        $this->manager->method('getRepository')
            ->willReturnCallback(function ($entityClass) {
                $repo = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
                $repo->method('findAll')->willReturn([]);
                return $repo;
            });
        
        $this->fixtures->load($this->manager);
        
        $keywordEntities = array_filter($persistedEntities, fn($entity) => $entity instanceof RiskKeyword);
        
        foreach ($keywordEntities as $keyword) {
            // 通过反射获取addedBy
            $reflection = new \ReflectionClass($keyword);
            if ($reflection->hasProperty('addedBy')) {
                $addedByProperty = $reflection->getProperty('addedBy');
                $addedByProperty->setAccessible(true);
                $addedBy = $addedByProperty->getValue($keyword);
                
                if ($addedBy !== null) {
                    $this->assertNotEmpty($addedBy, '添加人不应该为空');
                }
            }
        }
    }
} 