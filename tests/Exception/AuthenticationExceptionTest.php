<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\RAGFlowApiBundle\Exception\AuthenticationException;

/**
 * @internal
 */
#[CoversClass(AuthenticationException::class)]
class AuthenticationExceptionTest extends AbstractExceptionTestCase
{
    protected function getExceptionClass(): string
    {
        return AuthenticationException::class;
    }

    public function testExceptionCreation(): void
    {
        $exception = new AuthenticationException();

        $this->assertInstanceOf(AuthenticationException::class, $exception);
        $this->assertEquals('Authentication failed', $exception->getMessage());
        $this->assertEquals(401, $exception->getCode());
    }

    public function testExceptionWithCustomMessage(): void
    {
        $exception = new AuthenticationException('Custom auth message');

        $this->assertEquals('Custom auth message', $exception->getMessage());
        $this->assertEquals(401, $exception->getCode());
    }

    public function testExceptionWithCustomCode(): void
    {
        $exception = new AuthenticationException('Auth failed', 403);

        $this->assertEquals('Auth failed', $exception->getMessage());
        $this->assertEquals(403, $exception->getCode());
    }

    public function testExceptionWithPrevious(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new AuthenticationException('Auth failed', 401, $previous);

        $this->assertSame($previous, $exception->getPrevious());
        $this->assertEquals('Auth failed', $exception->getMessage());
        $this->assertEquals(401, $exception->getCode());
    }
}
