<?php

namespace AIContentAuditBundle\Tests\Exception;

use AIContentAuditBundle\Exception\ServiceNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(ServiceNotFoundException::class)]
final class ServiceNotFoundExceptionTest extends AbstractExceptionTestCase
{
    public function testConstructorFormatsMessageCorrectly(): void
    {
        $exception = new ServiceNotFoundException('ExpectedClass', 'ActualClass');

        $this->assertEquals(
            'Service "ActualClass" is not an instance of "ExpectedClass"',
            $exception->getMessage()
        );
    }

    public function testExceptionIsInstanceOfRuntimeException(): void
    {
        $exception = new ServiceNotFoundException('ExpectedClass', 'ActualClass');

        // 验证异常继承关系
        $reflection = new \ReflectionClass($exception);
        $parentClass = $reflection->getParentClass();
        $this->assertNotFalse($parentClass);
        $this->assertEquals('RuntimeException', $parentClass->getName());
    }
}
