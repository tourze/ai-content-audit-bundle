<?php

namespace AIContentAuditBundle\Tests;

use AIContentAuditBundle\AIContentAuditBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class TestKernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new SecurityBundle(),
            new TwigBundle(),
            new DoctrineBundle(),
            new EasyAdminBundle(),
            new AIContentAuditBundle(),
        ];
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'TEST_SECRET',
            'test' => true,
            'http_method_override' => false,
            'handle_all_throwables' => true,
            'validation' => [
                'email_validation_mode' => 'html5'
            ],
            'php_errors' => [
                'log' => true,
            ],
        ]);

        $container->extension('doctrine', [
            'dbal' => [
                'driver' => 'pdo_sqlite',
                'path' => ':memory:',
            ],
            'orm' => [
                'auto_generate_proxy_classes' => true,
                'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
                'auto_mapping' => true,
                'mappings' => [
                    'AIContentAuditBundle' => [
                        'is_bundle' => false,
                        'type' => 'attribute',
                        'dir' => '%kernel.project_dir%/src/Entity',
                        'prefix' => 'AIContentAuditBundle\\Entity',
                        'alias' => 'AIContentAuditBundle',
                    ],
                ],
            ],
        ]);

        $container->extension('twig', [
            'default_path' => '%kernel.project_dir%/templates',
            'debug' => '%kernel.debug%',
            'strict_variables' => '%kernel.debug%',
        ]);

        $container->extension('security', [
            'providers' => [
                'in_memory' => [
                    'memory' => [
                        'users' => [
                            'test' => [
                                'password' => 'test',
                                'roles' => ['ROLE_USER'],
                            ],
                        ],
                    ],
                ],
            ],
            'firewalls' => [
                'main' => [
                    'provider' => 'in_memory',
                    'security' => false,
                ],
            ],
        ]);
    }
}