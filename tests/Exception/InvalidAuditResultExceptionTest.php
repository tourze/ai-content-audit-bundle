<?php

namespace AIContentAuditBundle\Tests\Exception;

use AIContentAuditBundle\Exception\InvalidAuditResultException;
use PHPUnit\Framework\TestCase;

class InvalidAuditResultExceptionTest extends TestCase
{
    public function testExceptionInstance(): void
    {
        $exception = new InvalidAuditResultException('测试消息');
        
        $this->assertInstanceOf(InvalidAuditResultException::class, $exception);
        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
        $this->assertEquals('测试消息', $exception->getMessage());
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