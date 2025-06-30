<?php

declare(strict_types=1);

namespace AIContentAuditBundle\Tests\Unit;

use AIContentAuditBundle\AIContentAuditBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class AIContentAuditBundleTest extends TestCase
{
    public function testIsBundle(): void
    {
        $bundle = new AIContentAuditBundle();
        self::assertInstanceOf(Bundle::class, $bundle);
    }

    public function testGetName(): void
    {
        $bundle = new AIContentAuditBundle();
        self::assertSame('AIContentAuditBundle', $bundle->getName());
    }
}