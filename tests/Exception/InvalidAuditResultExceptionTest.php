<?php

namespace AIContentAuditBundle\Tests\Exception;

use AIContentAuditBundle\Exception\InvalidAuditResultException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(InvalidAuditResultException::class)]
final class InvalidAuditResultExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionInstance(): void
    {
        $exception = new InvalidAuditResultException('测试消息');

        // 验证异常消息
        $this->assertEquals('测试消息', $exception->getMessage());

        // 验证异常继承关系
        $reflection = new \ReflectionClass($exception);
        $parentClass = $reflection->getParentClass();
        $this->assertNotFalse($parentClass);
        $this->assertEquals('InvalidArgumentException', $parentClass->getName());
    }

    public function testExceptionWithCode(): void
    {
        $exception = new InvalidAuditResultException('测试消息', 400);

        $this->assertEquals(400, $exception->getCode());
    }

    public function testExceptionWithPrevious(): void
    {
        $previous = new \Exception('前一个异常');
        $exception = new InvalidAuditResultException('测试消息', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
