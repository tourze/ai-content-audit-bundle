<?php

namespace AIContentAuditBundle\Tests\Service;

use AIContentAuditBundle\Service\AdminMenu;
use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    private LinkGeneratorInterface&MockObject $linkGenerator;

    protected function onSetUp(): void
    {
        $this->linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        self::getContainer()->set(LinkGeneratorInterface::class, $this->linkGenerator);
    }

    public function testServiceIsCallable(): void
    {
        $service = self::getService(AdminMenu::class);
        // Verify the service implements __invoke method
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasMethod('__invoke'));
        $this->assertTrue($reflection->getMethod('__invoke')->isPublic());
    }

    public function testInvokeCreatesContentMenu(): void
    {
        $service = self::getService(AdminMenu::class);
        $rootMenu = $this->createMock(ItemInterface::class);
        $contentMenu = $this->createMock(ItemInterface::class);

        // 第一次调用返回null（不存在），第二次调用返回子菜单对象
        $rootMenu->expects($this->exactly(2))
            ->method('getChild')
            ->with('内容审核')
            ->willReturnOnConsecutiveCalls(null, $contentMenu)
        ;

        $rootMenu->expects($this->once())
            ->method('addChild')
            ->with('内容审核')
            ->willReturn($contentMenu)
        ;

        // 设置子菜单的添加期望
        $contentMenu->expects($this->exactly(4))
            ->method('addChild')
            ->willReturnCallback(function () {
                return $this->createMock(ItemInterface::class);
            })
        ;

        $service->__invoke($rootMenu);
    }

    public function testInvokeHandlesExistingContentMenu(): void
    {
        $service = self::getService(AdminMenu::class);
        $rootMenu = $this->createMock(ItemInterface::class);
        $contentMenu = $this->createMock(ItemInterface::class);

        // 第一次和第二次调用都返回已存在的子菜单
        $rootMenu->expects($this->exactly(2))
            ->method('getChild')
            ->with('内容审核')
            ->willReturn($contentMenu)
        ;

        $rootMenu->expects($this->never())
            ->method('addChild')
        ;

        // 设置子菜单的添加期望
        $contentMenu->expects($this->exactly(4))
            ->method('addChild')
            ->willReturnCallback(function () {
                return $this->createMock(ItemInterface::class);
            })
        ;

        $service->__invoke($rootMenu);
    }
}
