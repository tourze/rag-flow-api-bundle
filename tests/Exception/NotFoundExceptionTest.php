<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\RAGFlowApiBundle\Exception\NotFoundException;

/**
 * @internal
 */
#[CoversClass(NotFoundException::class)]
class NotFoundExceptionTest extends AbstractExceptionTestCase
{
    protected function getExceptionClass(): string
    {
        return NotFoundException::class;
    }

    public function testExceptionCreation(): void
    {
        $exception = new NotFoundException();

        $this->assertInstanceOf(NotFoundException::class, $exception);
        $this->assertEquals('Resource not found', $exception->getMessage());
        $this->assertEquals(404, $exception->getCode());
    }

    public function testExceptionWithCustomMessage(): void
    {
        $exception = new NotFoundException('Dataset not found');

        $this->assertEquals('Dataset not found', $exception->getMessage());
        $this->assertEquals(404, $exception->getCode());
    }

    public function testExceptionWithCustomCode(): void
    {
        $exception = new NotFoundException('Resource gone', 410);

        $this->assertEquals('Resource gone', $exception->getMessage());
        $this->assertEquals(410, $exception->getCode());
    }

    public function testExceptionWithPrevious(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new NotFoundException('Not found', 404, $previous);

        $this->assertSame($previous, $exception->getPrevious());
        $this->assertEquals('Not found', $exception->getMessage());
        $this->assertEquals(404, $exception->getCode());
    }
}
