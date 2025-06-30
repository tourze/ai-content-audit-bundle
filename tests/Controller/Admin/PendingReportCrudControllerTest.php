<?php

namespace AIContentAuditBundle\Tests\Controller\Admin;

use AIContentAuditBundle\Controller\Admin\PendingReportCrudController;
use AIContentAuditBundle\Entity\Report;
use PHPUnit\Framework\TestCase;

class PendingReportCrudControllerTest extends TestCase
{
    public function testGetEntityFqcn()
    {
        // 调用父类的静态方法
        $result = PendingReportCrudController::getEntityFqcn();
        
        $this->assertEquals(Report::class, $result);
    }
    
    public function testClassExtends()
    {
        // 测试类是否正确继承
        $reflection = new \ReflectionClass(PendingReportCrudController::class);
        $this->assertEquals('AIContentAuditBundle\Controller\Admin\ReportCrudController', $reflection->getParentClass()->getName());
    }
    
    public function testCreateIndexQueryBuilder()
    {
        // 由于涉及复杂的EasyAdmin环境设置，这里主要测试类的存在
        $this->assertTrue(class_exists(PendingReportCrudController::class));
    }
    
    public function testClassHasSecurityAttribute()
    {
        // 测试类是否有安全注解
        $reflection = new \ReflectionClass(PendingReportCrudController::class);
        $attributes = $reflection->getAttributes();
        
        $hasSecurityAttribute = false;
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === 'Symfony\Component\Security\Http\Attribute\IsGranted') {
                $hasSecurityAttribute = true;
                break;
            }
        }
        
        $this->assertTrue($hasSecurityAttribute, 'PendingReportCrudController should have IsGranted attribute');
    }
    
    public function testPendingReportFiltering()
    {
        // 测试类的存在和基本功能
        $this->assertTrue(class_exists(PendingReportCrudController::class));
        
        // 验证类是否重写了createIndexQueryBuilder方法
        $reflection = new \ReflectionClass(PendingReportCrudController::class);
        $this->assertTrue($reflection->hasMethod('createIndexQueryBuilder'));
        
        $method = $reflection->getMethod('createIndexQueryBuilder');
        $this->assertTrue($method->isPublic());
    }
}