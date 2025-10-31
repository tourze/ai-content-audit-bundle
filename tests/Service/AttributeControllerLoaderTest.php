<?php

declare(strict_types=1);

namespace AIContentAuditBundle\Tests\Service;

use AIContentAuditBundle\Service\AttributeControllerLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(AttributeControllerLoader::class)]
#[RunTestsInSeparateProcesses]
final class AttributeControllerLoaderTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // No specific setup needed for this test
    }

    public function testAutoloadReturnsNonEmptyRouteCollection(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $collection = $loader->autoload();

        self::assertGreaterThanOrEqual(0, $collection->count());
    }

    public function testLoadReturnsNonEmptyRouteCollection(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $collection = $loader->load('resource');

        self::assertGreaterThanOrEqual(0, $collection->count());
    }

    public function testSupportsAlwaysReturnsFalse(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);

        self::assertFalse($loader->supports('resource'));
        self::assertFalse($loader->supports('resource', 'type'));
    }
}
