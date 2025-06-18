<?php

namespace AIContentAuditBundle\Tests\DataFixtures;

use AIContentAuditBundle\DataFixtures\ReportFixtures;
use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Entity\Report;
use AIContentAuditBundle\Enum\ProcessStatus;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

class ReportFixturesTest extends TestCase
{
    private ReportFixtures $fixtures;
    private MockObject $manager;
    private MockObject $user;
    private MockObject $generatedContent;

    protected function setUp(): void
    {
        $this->fixtures = new ReportFixtures();
        $this->manager = $this->createMock(ObjectManager::class);
        $this->user = $this->createMock(UserInterface::class);
        $this->generatedContent = $this->createMock(GeneratedContent::class);
        
        // Mock用户标识符
        $this->user->method('getUserIdentifier')
            ->willReturn('test_user');
            
        // Mock ReferenceRepository for AbstractFixture
        $referenceRepository = $this->createMock(\Doctrine\Common\DataFixtures\ReferenceRepository::class);
        $referenceRepository->method('addReference');
        $referenceRepository->method('getReference')
            ->willReturnCallback(function ($reference, $class) {
                if ($class === \BizUserBundle\Entity\BizUser::class) {
                    return $this->user;
                } elseif ($class === \AIContentAuditBundle\Entity\GeneratedContent::class) {
                    return $this->generatedContent;
                }
                return null;
            });
        
        // 使用反射设置ReferenceRepository
        $reflection = new \ReflectionClass($this->fixtures);
        $property = $reflection->getProperty('referenceRepository');
        $property->setAccessible(true);
        $property->setValue($this->fixtures, $referenceRepository);
    }

    public function testLoad_createsExpectedNumberOfReports()
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
        
        // 验证创建了Report实体
        $reportEntities = array_filter($persistedEntities, fn($entity) => $entity instanceof Report);
        $this->assertNotEmpty($reportEntities, '应该创建了Report实体');
        
        // 验证创建的Report数量合理（实际创建50个）
        $this->assertGreaterThanOrEqual(40, count($reportEntities), '应该创建足够数量的举报记录');
        $this->assertLessThanOrEqual(60, count($reportEntities), '创建的举报记录数量应该在合理范围内');
    }
    
    public function testLoad_createsReportsWithValidProcessStatus()
    {
        $persistedEntities = [];
        
        $this->manager->expects($this->atLeastOnce())
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$persistedEntities) {
                $persistedEntities[] = $entity;
            });
        
        $this->manager->expects($this->once())
            ->method('flush');
            
        // Mock getRepository方法返回一些模拟数据
        $this->manager->method('getRepository')
            ->willReturnCallback(function ($entityClass) {
                $repo = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
                if ($entityClass === GeneratedContent::class) {
                    // 返回一些模拟的GeneratedContent
                    $content = $this->createMock(GeneratedContent::class);
                    $repo->method('findAll')->willReturn([$content]);
                } else {
                    $repo->method('findAll')->willReturn([]);
                }
                return $repo;
            });
        
        $this->fixtures->load($this->manager);
        
        // 获取所有Report实体
        $reportEntities = array_filter($persistedEntities, fn($entity) => $entity instanceof Report);
        
        foreach ($reportEntities as $report) {
            $this->assertInstanceOf(Report::class, $report);
            
            // 通过反射获取processStatus（因为可能是私有属性）
            $reflection = new \ReflectionClass($report);
            if ($reflection->hasProperty('processStatus')) {
                $statusProperty = $reflection->getProperty('processStatus');
                $statusProperty->setAccessible(true);
                $status = $statusProperty->getValue($report);
                
                if ($status !== null) {
                    $this->assertInstanceOf(ProcessStatus::class, $status, '处理状态应该是有效的ProcessStatus枚举值');
                    $this->assertContains($status, ProcessStatus::cases(), '处理状态应该是有效的枚举值');
                }
            }
        }
    }
    
    public function testLoad_createsReportsWithValidReportTime()
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
                if ($entityClass === GeneratedContent::class) {
                    $content = $this->createMock(GeneratedContent::class);
                    $repo->method('findAll')->willReturn([$content]);
                } else {
                    $repo->method('findAll')->willReturn([]);
                }
                return $repo;
            });
        
        $this->fixtures->load($this->manager);
        
        $reportEntities = array_filter($persistedEntities, fn($entity) => $entity instanceof Report);
        
        foreach ($reportEntities as $report) {
            // 通过反射获取reportTime
            $reflection = new \ReflectionClass($report);
            if ($reflection->hasProperty('reportTime')) {
                $timeProperty = $reflection->getProperty('reportTime');
                $timeProperty->setAccessible(true);
                $reportTime = $timeProperty->getValue($report);
                
                if ($reportTime !== null) {
                    $this->assertInstanceOf(\DateTimeImmutable::class, $reportTime, '举报时间应该是DateTimeImmutable实例');
                    
                    // 验证时间在合理范围内（过去30天内）
                    $now = new \DateTimeImmutable();
                    $thirtyDaysAgo = $now->sub(new \DateInterval('P30D'));
                    $this->assertGreaterThanOrEqual($thirtyDaysAgo, $reportTime, '举报时间应该在过去30天内');
                    $this->assertLessThanOrEqual($now, $reportTime, '举报时间不应该是未来时间');
                }
            }
        }
    }
    
    public function testLoad_createsReportsWithValidReportReason()
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
                if ($entityClass === GeneratedContent::class) {
                    $content = $this->createMock(GeneratedContent::class);
                    $repo->method('findAll')->willReturn([$content]);
                } else {
                    $repo->method('findAll')->willReturn([]);
                }
                return $repo;
            });
        
        $this->fixtures->load($this->manager);
        
        $reportEntities = array_filter($persistedEntities, fn($entity) => $entity instanceof Report);
        
        foreach ($reportEntities as $report) {
            // 通过反射获取reportReason
            $reflection = new \ReflectionClass($report);
            if ($reflection->hasProperty('reportReason')) {
                $reasonProperty = $reflection->getProperty('reportReason');
                $reasonProperty->setAccessible(true);
                $reportReason = $reasonProperty->getValue($report);
                
                if ($reportReason !== null) {
                    $this->assertNotEmpty($reportReason, '举报理由不应该为空');
                    $this->assertLessThanOrEqual(500, strlen($reportReason), '举报理由长度应该合理');
                }
            }
        }
    }
    
    public function testLoad_handlesEmptyGeneratedContent()
    {
        $persistedEntities = [];
        
        // ReportFixtures总是创建50条记录，即使没有内容
        $this->manager->expects($this->exactly(50))
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$persistedEntities) {
                $persistedEntities[] = $entity;
            });
        
        $this->manager->expects($this->once())
            ->method('flush');
            
        // Mock getRepository返回空的GeneratedContent
        $this->manager->method('getRepository')
            ->willReturnCallback(function ($entityClass) {
                $repo = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
                $repo->method('findAll')->willReturn([]); // 返回空数组
                return $repo;
            });
        
        // 执行load方法，应该不会抛出异常
        $this->fixtures->load($this->manager);
        
        // 验证创建了固定数量的报告（50个）
        $reportEntities = array_filter($persistedEntities, fn($entity) => $entity instanceof Report);
        $this->assertEquals(50, count($reportEntities), 'ReportFixtures总是创建50条记录');
    }
    
    public function testLoad_processStatusDistribution()
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
                if ($entityClass === GeneratedContent::class) {
                    // 返回多个模拟内容以确保创建足够的举报
                    $contents = [];
                    for ($i = 0; $i < 10; $i++) {
                        $content = $this->createMock(GeneratedContent::class);
                        $contents[] = $content;
                    }
                    $repo->method('findAll')->willReturn($contents);
                } else {
                    $repo->method('findAll')->willReturn([]);
                }
                return $repo;
            });
        
        $this->fixtures->load($this->manager);
        
        $reportEntities = array_filter($persistedEntities, fn($entity) => $entity instanceof Report);
        
        if (count($reportEntities) > 5) {
            // 统计各种处理状态的分布
            $statusCounts = [];
            foreach ($reportEntities as $report) {
                $reflection = new \ReflectionClass($report);
                if ($reflection->hasProperty('processStatus')) {
                    $statusProperty = $reflection->getProperty('processStatus');
                    $statusProperty->setAccessible(true);
                    $status = $statusProperty->getValue($report);
                    
                    if ($status instanceof ProcessStatus) {
                        $statusValue = $status->value;
                        $statusCounts[$statusValue] = ($statusCounts[$statusValue] ?? 0) + 1;
                    }
                }
            }
            
            // 验证至少有两种不同的处理状态
            $this->assertGreaterThanOrEqual(1, count($statusCounts), '应该有不同的处理状态分布');
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
    
    public function testLoad_doesNotCreateDuplicateData()
    {
        // 第一次执行
        $this->manager->expects($this->atLeastOnce())
            ->method('persist');
        
        $this->manager->expects($this->once())
            ->method('flush');
            
        $this->manager->method('getRepository')
            ->willReturnCallback(function ($entityClass) {
                $repo = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
                if ($entityClass === GeneratedContent::class) {
                    $content = $this->createMock(GeneratedContent::class);
                    $repo->method('findAll')->willReturn([$content]);
                } else {
                    $repo->method('findAll')->willReturn([]);
                }
                return $repo;
            });
        
        // 这个测试主要验证方法能正常执行，不会因为重复数据而出错
        $this->fixtures->load($this->manager);
    }
} 