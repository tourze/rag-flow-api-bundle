<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\RAGFlowApiBundle\Exception\ApiKeyDecryptionException;

/**
 * @internal
 */
#[CoversClass(ApiKeyDecryptionException::class)]
class ApiKeyDecryptionExceptionTest extends AbstractExceptionTestCase
{
    protected function getExceptionClass(): string
    {
        return ApiKeyDecryptionException::class;
    }

    public function testExceptionCreation(): void
    {
        $exception = new ApiKeyDecryptionException('Test decryption failure');

        $this->assertInstanceOf(ApiKeyDecryptionException::class, $exception);
        $this->assertEquals('Test decryption failure', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
    }

    public function testExceptionWithPrevious(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new ApiKeyDecryptionException('Decryption failed', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
        $this->assertEquals('Decryption failed', $exception->getMessage());
    }
}
