<?php

namespace AIContentAuditBundle\Tests\Exception;

use AIContentAuditBundle\Exception\EntityNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(EntityNotFoundException::class)]
final class EntityNotFoundExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionWithMessage(): void
    {
        $exception = new EntityNotFoundException('Test message');

        $this->assertSame('Test message', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
    }

    public function testExceptionWithMessageAndCode(): void
    {
        $exception = new EntityNotFoundException('Test message', 123);

        $this->assertSame('Test message', $exception->getMessage());
        $this->assertSame(123, $exception->getCode());
    }
}
