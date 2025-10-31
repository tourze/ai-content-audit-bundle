<?php

namespace AIContentAuditBundle\Tests\Exception;

use AIContentAuditBundle\Exception\InvalidRepositoryArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(InvalidRepositoryArgumentException::class)]
final class InvalidRepositoryArgumentExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionCanBeCreated(): void
    {
        $message = 'Test exception message';
        $exception = new InvalidRepositoryArgumentException($message);

        // 验证异常消息
        $this->assertSame($message, $exception->getMessage());

        // 验证异常继承关系
        $reflection = new \ReflectionClass($exception);
        $parentClass = $reflection->getParentClass();
        $this->assertNotFalse($parentClass);
        $this->assertEquals('InvalidArgumentException', $parentClass->getName());
    }

    public function testExceptionWithCode(): void
    {
        $message = 'Test exception message';
        $code = 123;
        $exception = new InvalidRepositoryArgumentException($message, $code);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
    }

    public function testExceptionWithPrevious(): void
    {
        $message = 'Test exception message';
        $previous = new \RuntimeException('Previous exception');
        $exception = new InvalidRepositoryArgumentException($message, 0, $previous);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
