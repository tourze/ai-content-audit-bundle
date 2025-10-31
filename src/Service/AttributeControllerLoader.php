<?php

namespace AIContentAuditBundle\Service;

use AIContentAuditBundle\Controller\Admin\GeneratedContentCrudController;
use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Routing\RouteCollection;
use Tourze\RoutingAutoLoaderBundle\Service\RoutingAutoLoaderInterface;

/**
 * 仅加载本 Bundle 中的“非 EasyAdmin”HTTP 路由（基于 Attribute）。
 * 注意：EasyAdmin 的 CRUD 行为仍由 EasyAdmin 内部路由机制处理。
 */
#[AutoconfigureTag(name: 'routing.loader')]
class AttributeControllerLoader extends Loader implements RoutingAutoLoaderInterface
{
    private AttributeRouteControllerLoader $controllerLoader;

    public function __construct()
    {
        parent::__construct();
        $this->controllerLoader = new AttributeRouteControllerLoader();
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        return $this->autoload();
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return false;
    }

    public function autoload(): RouteCollection
    {
        $collection = new RouteCollection();

        // 审核页（GET/POST）作为“非 EasyAdmin 路由”，由 Attribute 路由管理
        $collection->addCollection($this->controllerLoader->load(GeneratedContentCrudController::class));

        return $collection;
    }
}
