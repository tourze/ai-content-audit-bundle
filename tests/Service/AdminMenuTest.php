<?php

namespace AIContentAuditBundle\Tests\Service;

use AIContentAuditBundle\Service\AdminMenu;
use Knp\Menu\ItemInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;

class AdminMenuTest extends TestCase
{
    private AdminMenu $adminMenu;
    private LinkGeneratorInterface|MockObject $linkGenerator;
    private ItemInterface|MockObject $rootItem;
    private ItemInterface|MockObject $contentMenuItem;

    protected function setUp(): void
    {
        $this->linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        $this->adminMenu = new AdminMenu($this->linkGenerator);
        
        // 创建根菜单项
        $this->rootItem = $this->createMock(ItemInterface::class);
        
        // 创建内容审核子菜单
        $this->contentMenuItem = $this->createMock(ItemInterface::class);
    }
    
    public function testInvokeCreatesAllMenuItems(): void
    {
        // 设置LinkGenerator行为
        $this->linkGenerator->expects($this->exactly(4))
            ->method('getCurdListPage')
            ->willReturn('/test-url');
        
        // 首先返回null，表示需要创建菜单，然后返回contentMenuItem
        $this->rootItem->expects($this->any())
            ->method('getChild')
            ->with('内容审核')
            ->will($this->onConsecutiveCalls(null, $this->contentMenuItem));
            
        $this->rootItem->expects($this->once())
            ->method('addChild')
            ->with('内容审核')
            ->willReturn($this->contentMenuItem);
            
        // 设置contentMenuItem的行为
        $this->contentMenuItem->expects($this->exactly(4))
            ->method('addChild')
            ->willReturnCallback(function($name) {
                $menuItem = $this->createMock(ItemInterface::class);
                $menuItem->expects($this->once())
                    ->method('setUri')
                    ->willReturnSelf();
                $menuItem->expects($this->once())
                    ->method('setAttribute')
                    ->with('icon', $this->anything())
                    ->willReturnSelf();
                return $menuItem;
            });
            
        // 执行菜单构建
        ($this->adminMenu)($this->rootItem);
    }
    
    public function testInvokeWithExistingContentMenu(): void
    {
        // 设置LinkGenerator行为
        $this->linkGenerator->expects($this->exactly(4))
            ->method('getCurdListPage')
            ->willReturn('/some-url');
        
        // 总是返回现有的菜单项
        $this->rootItem->expects($this->any())
            ->method('getChild')
            ->with('内容审核')
            ->willReturn($this->contentMenuItem);
            
        // 不应该再创建内容审核菜单
        $this->rootItem->expects($this->never())
            ->method('addChild');
            
        // 设置contentMenuItem的行为
        $menuItem = $this->createMock(ItemInterface::class);
        $menuItem->expects($this->exactly(4))
            ->method('setUri')
            ->willReturnSelf();
        $menuItem->expects($this->exactly(4))
            ->method('setAttribute')
            ->willReturnSelf();
            
        // 验证子菜单添加
        $this->contentMenuItem->expects($this->exactly(4))
            ->method('addChild')
            ->willReturn($menuItem);
            
        // 执行菜单构建
        ($this->adminMenu)($this->rootItem);
    }
} 