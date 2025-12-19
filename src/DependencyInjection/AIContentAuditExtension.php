<?php

declare(strict_types=1);

namespace AIContentAuditBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

final class AIContentAuditExtension extends AutoExtension implements PrependExtensionInterface
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('twig', [
            'paths' => [
                dirname(__DIR__, 2) . '/templates' => 'AIContentAudit',
            ],
        ]);
    }
}
