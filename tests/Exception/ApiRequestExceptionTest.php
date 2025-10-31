<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Exception;

use HttpClientBundle\Response\CacheResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\RAGFlowApiBundle\Exception\ApiRequestException;
use Tourze\RAGFlowApiBundle\Request\HealthCheckRequest;

/**
 * @internal
 */
#[CoversClass(ApiRequestException::class)]
class ApiRequestExceptionTest extends AbstractExceptionTestCase
{
    public function testCanCreateWithAllParameters(): void
    {
        $request = new HealthCheckRequest();
        $response = new CacheResponse(500, ['Content-Type' => ['application/json']], '{"error": "test"}', []);
        $message = 'Test error message';
        $errorCode = 500;
        $errorDetails = 'Validation error details';
        $previous = new \Exception('Previous exception');

        $exception = new ApiRequestException(
            $request,
            $response,
            $message,
            $errorCode,
            $errorDetails,
            $previous
        );

        $this->assertSame($request, $exception->getRequest());
        $this->assertSame($response, $exception->getResponse());
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($errorCode, $exception->getErrorCode());
        $this->assertSame($errorDetails, $exception->getErrorDetails());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testCanCreateWithMinimalParameters(): void
    {
        $request = new HealthCheckRequest();
        $response = new CacheResponse(400, [], '', []);

        $exception = new ApiRequestException($request, $response);

        $this->assertSame($request, $exception->getRequest());
        $this->assertSame($response, $exception->getResponse());
        $this->assertNull($exception->getErrorCode());
        $this->assertNull($exception->getErrorDetails());
    }

    public function testGettersReturnCorrectValues(): void
    {
        $request = new HealthCheckRequest();
        $response = new CacheResponse(422, [], '', []);
        $errorCode = 422;
        $errorDetails = 'Validation error: field1, field2';

        $exception = new ApiRequestException(
            $request,
            $response,
            'Validation failed',
            $errorCode,
            $errorDetails
        );

        $this->assertSame($errorCode, $exception->getErrorCode());
        $this->assertSame($errorDetails, $exception->getErrorDetails());
        $this->assertSame('Validation failed', $exception->getMessage());
    }
}
