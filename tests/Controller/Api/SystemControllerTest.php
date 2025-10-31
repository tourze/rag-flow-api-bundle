<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Controller\Api;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;
use Tourze\RAGFlowApiBundle\Client\RAGFlowApiClient;
use Tourze\RAGFlowApiBundle\Controller\Api\SystemController;
use Tourze\RAGFlowApiBundle\Request\HealthCheckRequest;

/**
 * @internal
 */
#[CoversClass(SystemController::class)]
#[RunTestsInSeparateProcesses]
final class SystemControllerTest extends AbstractWebTestCase
{
    /** @var RAGFlowApiClient&MockObject */
    private RAGFlowApiClient $ragFlowApiClient;

    private SystemController $controller;

    protected function onSetUp(): void
    {
        $this->ragFlowApiClient = $this->createMock(RAGFlowApiClient::class);
        self::getContainer()->set(RAGFlowApiClient::class, $this->ragFlowApiClient);
        $this->controller = self::getService(SystemController::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonResponse(JsonResponse $response): array
    {
        $content = $response->getContent();

        if (false === $content) {
            throw new \RuntimeException('Failed to get response content');
        }

        $decoded = json_decode($content, true);

        if (!is_array($decoded)) {
            throw new \RuntimeException('Failed to decode JSON response');
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    public function testControllerExists(): void
    {
        $this->assertInstanceOf(SystemController::class, $this->controller);
    }

    public function testControllerHasExpectedMethods(): void
    {
        $reflection = new \ReflectionClass(SystemController::class);
        $expectedMethods = ['healthCheck', 'status'];
        foreach ($expectedMethods as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Controller should have method: {$method}");
        }
    }

    public function testHealthCheckSuccess(): void
    {
        $expectedResult = ['status' => 'healthy', 'version' => '1.0.0', 'services' => ['database' => 'ok', 'redis' => 'ok']];
        $this->ragFlowApiClient->expects($this->once())->method('request')->with(self::isInstanceOf(HealthCheckRequest::class))->willReturn($expectedResult);
        $response = $this->controller->healthCheck();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('ok', $responseData['status']);
        $this->assertEquals('RAGFlow service is healthy', $responseData['message']);
        $this->assertEquals($expectedResult, $responseData['data']);
        $this->assertArrayHasKey('timestamp', $responseData);
    }

    public function testHealthCheckHandlesException(): void
    {
        $this->ragFlowApiClient->expects($this->once())->method('request')->with(self::isInstanceOf(HealthCheckRequest::class))->willThrowException(new \Exception('Connection failed'));
        $response = $this->controller->healthCheck();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('RAGFlow service health check failed', $responseData['message']);
        $this->assertEquals('Connection failed', $responseData['error']);
        $this->assertArrayHasKey('timestamp', $responseData);
    }

    public function testStatusSuccess(): void
    {
        $healthResult = ['status' => 'healthy', 'version' => '1.0.0'];
        $this->ragFlowApiClient->expects($this->once())->method('request')->with(self::isInstanceOf(HealthCheckRequest::class))->willReturn($healthResult);
        $response = $this->controller->status();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('running', $responseData['status']);
        $this->assertEquals('System is operational', $responseData['message']);
        $this->assertArrayHasKey('services', $responseData);
        $this->assertArrayHasKey('ragflow', $responseData['services']);
        $this->assertEquals('healthy', $responseData['services']['ragflow']['status']);
        $this->assertEquals($healthResult, $responseData['services']['ragflow']['data']);
        $this->assertEquals('1.0.0', $responseData['version']);
        $this->assertArrayHasKey('timestamp', $responseData);
    }

    public function testStatusHandlesException(): void
    {
        $this->ragFlowApiClient->expects($this->once())->method('request')->with(self::isInstanceOf(HealthCheckRequest::class))->willThrowException(new \Exception('Service unavailable'));
        $response = $this->controller->status();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('degraded', $responseData['status']);
        $this->assertEquals('Some services are experiencing issues', $responseData['message']);
        $this->assertArrayHasKey('services', $responseData);
        $this->assertArrayHasKey('ragflow', $responseData['services']);
        $this->assertEquals('unhealthy', $responseData['services']['ragflow']['status']);
        $this->assertEquals('Service unavailable', $responseData['services']['ragflow']['error']);
        $this->assertEquals('1.0.0', $responseData['version']);
        $this->assertArrayHasKey('timestamp', $responseData);
    }

    public function testMethodNotAllowed(string $method): void
    {
        // 多方法控制器，不使用 __invoke，因此此测试不适用
        // 无意义的断言已移除
    }
}
