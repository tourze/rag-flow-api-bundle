<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Exception;

use HttpClientBundle\Exception\HttpClientException;
use HttpClientBundle\Request\RequestInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\RAGFlowApiBundle\Exception\ApiRequestException;
use Tourze\RAGFlowApiBundle\Exception\RAGFlowApiException;

/**
 * @internal
 */
#[CoversClass(RAGFlowApiException::class)]
class RAGFlowApiExceptionTest extends AbstractExceptionTestCase
{
    protected function getExceptionClass(): string
    {
        return RAGFlowApiException::class;
    }

    public function testExceptionCreation(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $exception = new ApiRequestException($request, $response, 'Test error', 500);

        $this->assertEquals('Test error', $exception->getMessage());
        $this->assertEquals(500, $exception->getErrorCode());
        $this->assertNull($exception->getErrorDetails());
    }

    public function testExceptionWithDetails(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $details = 'Detailed error information';
        $exception = new ApiRequestException($request, $response, 'Test error', 404, $details);

        $this->assertEquals('Test error', $exception->getMessage());
        $this->assertEquals(404, $exception->getErrorCode());
        $this->assertEquals($details, $exception->getErrorDetails());
    }

    public function testExceptionWithPrevious(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $previous = new \RuntimeException('Previous error');
        $exception = new ApiRequestException($request, $response, 'Test error', 500, null, $previous);

        $this->assertEquals('Test error', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testExceptionInheritance(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $exception = new ApiRequestException($request, $response, 'Test error', 500);

        $this->assertInstanceOf(\Throwable::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(RAGFlowApiException::class, $exception);
    }
}
