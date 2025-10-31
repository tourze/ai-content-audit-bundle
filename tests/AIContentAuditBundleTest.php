<?php

declare(strict_types=1);

namespace AIContentAuditBundle\Tests;

use AIContentAuditBundle\AIContentAuditBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(AIContentAuditBundle::class)]
#[RunTestsInSeparateProcesses]
final class AIContentAuditBundleTest extends AbstractBundleTestCase
{
}
