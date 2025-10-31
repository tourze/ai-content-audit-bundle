<?php

declare(strict_types=1);

namespace AIContentAuditBundle;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Tourze\BundleDependency\BundleDependencyInterface;

class AIContentAuditBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            DoctrineBundle::class => ['all' => true],
            TwigBundle::class => ['all' => true],
            SecurityBundle::class => ['all' => true],
            EasyAdminBundle::class => ['all' => true],
        ];
    }

    public function configureRoutes(RoutingConfigurator $routes): void
    {
        // 非 EasyAdmin 的注解路由由本包的 AttributeControllerLoader 自动加载
        // 这里无需再导入 YAML 路由
    }
}
