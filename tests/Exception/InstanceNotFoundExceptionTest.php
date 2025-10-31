<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\RAGFlowApiBundle\Exception\InstanceNotFoundException;

/**
 * @internal
 */
#[CoversClass(InstanceNotFoundException::class)]
class InstanceNotFoundExceptionTest extends AbstractExceptionTestCase
{
    protected function getExceptionClass(): string
    {
        return InstanceNotFoundException::class;
    }

    public function testExceptionCreation(): void
    {
        $exception = new InstanceNotFoundException('test-instance');

        $this->assertInstanceOf(InstanceNotFoundException::class, $exception);
        $this->assertEquals('Instance "test-instance" not found', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
    }

    public function testExceptionWithDifferentInstanceName(): void
    {
        $exception = new InstanceNotFoundException('production');

        $this->assertEquals('Instance "production" not found', $exception->getMessage());
    }

    public function testExceptionWithEmptyInstanceName(): void
    {
        $exception = new InstanceNotFoundException('');

        $this->assertEquals('Instance "" not found', $exception->getMessage());
    }
}
