<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\RAGFlowApiBundle\Exception\RateLimitException;

/**
 * @internal
 */
#[CoversClass(RateLimitException::class)]
class RateLimitExceptionTest extends AbstractExceptionTestCase
{
    protected function getExceptionClass(): string
    {
        return RateLimitException::class;
    }

    public function testExceptionCreation(): void
    {
        $exception = new RateLimitException();

        $this->assertInstanceOf(RateLimitException::class, $exception);
        $this->assertEquals('Rate limit exceeded', $exception->getMessage());
        $this->assertEquals(429, $exception->getCode());
    }

    public function testExceptionWithCustomMessage(): void
    {
        $exception = new RateLimitException('Too many requests');

        $this->assertEquals('Too many requests', $exception->getMessage());
        $this->assertEquals(429, $exception->getCode());
    }

    public function testExceptionWithCustomCode(): void
    {
        $exception = new RateLimitException('Rate limited', 420);

        $this->assertEquals('Rate limited', $exception->getMessage());
        $this->assertEquals(420, $exception->getCode());
    }

    public function testExceptionWithPrevious(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new RateLimitException('Rate limit exceeded', 429, $previous);

        $this->assertSame($previous, $exception->getPrevious());
        $this->assertEquals('Rate limit exceeded', $exception->getMessage());
        $this->assertEquals(429, $exception->getCode());
    }
}
