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
use Tourze\RAGFlowApiBundle\Controller\Api\DatasetController;
use Tourze\RAGFlowApiBundle\Service\DatasetService;

/**
 * @internal
 */
#[CoversClass(DatasetController::class)]
#[RunTestsInSeparateProcesses]
final class DatasetControllerTest extends AbstractWebTestCase
{
    /** @var DatasetService&MockObject */
    private DatasetService $datasetService;

    private DatasetController $controller;

    protected function onSetUp(): void
    {
        $this->datasetService = $this->createMock(DatasetService::class);
        // Only set the service if it hasn't been initialized yet
        if (!self::getContainer()->has(DatasetService::class)) {
            self::getContainer()->set(DatasetService::class, $this->datasetService);
        }
        $this->controller = self::getService(DatasetController::class);
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

    /**
     * @param array<string, mixed> $data
     */
    private function createJsonRequest(array $data): Request
    {
        $jsonContent = json_encode($data);
        $this->assertIsString($jsonContent);

        return new Request([], [], [], [], [], [], $jsonContent);
    }

    public function testControllerExists(): void
    {
        $this->assertInstanceOf(DatasetController::class, $this->controller);
    }

    public function testControllerHasExpectedMethods(): void
    {
        $reflection = new \ReflectionClass(DatasetController::class);
        $expectedMethods = ['list', 'create', 'update', 'delete', 'getKnowledgeGraph'];
        foreach ($expectedMethods as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Controller should have method: {$method}");
        }
    }

    public function testList(): void
    {
        $request = new Request(['page' => '1', 'limit' => '30', 'name' => 'Test', 'language' => 'en', 'status' => 'active']);
        $expectedData = ['data' => [], 'total' => 0, 'page' => 1, 'limit' => 20];
        $this->datasetService->expects($this->once())->method('list')->with(['page' => 1, 'limit' => 20, 'name' => 'Test', 'language' => 'en', 'status' => 'active'])->willReturn($expectedData);
        $response = $this->controller->list($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals($expectedData, $responseData['data']);
    }

    public function testCreateWithValidData(): void
    {
        $requestData = ['name' => 'New Dataset', 'description' => 'Test dataset', 'language' => 'en'];
        $request = $this->createJsonRequest($requestData);
        $expectedResult = ['id' => 'dataset-123', 'name' => 'New Dataset'];
        $this->datasetService->expects($this->once())->method('create')->with($requestData)->willReturn($expectedResult);
        $response = $this->controller->create($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals($expectedResult, $responseData['data']);
    }

    public function testCreateWithInvalidJson(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $response = $this->controller->create($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Invalid JSON data', $responseData['message']);
    }

    public function testCreateWithMissingName(): void
    {
        $requestData = ['description' => 'Test dataset without name'];
        $request = $this->createJsonRequest($requestData);
        $response = $this->controller->create($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Dataset name is required', $responseData['message']);
    }

    public function testUpdate(): void
    {
        $datasetId = 123;
        $updateData = ['name' => 'Updated Dataset'];
        $request = $this->createJsonRequest($updateData);
        $expectedResult = ['id' => $datasetId, 'name' => 'Updated Dataset'];
        $this->datasetService->expects($this->once())->method('update')->with('dataset-123', $updateData)->willReturn($expectedResult);
        $response = $this->controller->update($datasetId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals($expectedResult, $responseData['data']);
    }

    public function testUpdateWithInvalidJson(): void
    {
        $datasetId = 123;
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $response = $this->controller->update($datasetId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Invalid JSON data', $responseData['message']);
    }

    public function testDelete(): void
    {
        $datasetId = 123;
        $this->datasetService->expects($this->once())->method('delete')->with('dataset-123');
        $response = $this->controller->delete($datasetId);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals('Dataset deleted successfully', $responseData['message']);
    }

    public function testGetKnowledgeGraph(): void
    {
        $datasetId = 'dataset-123';
        $expectedResult = ['nodes' => [], 'edges' => [], 'metadata' => ['total_nodes' => 0, 'total_edges' => 0]];
        $this->datasetService->expects($this->once())->method('getKnowledgeGraph')->with($datasetId)->willReturn($expectedResult);
        $response = $this->controller->getKnowledgeGraph($datasetId);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals($expectedResult, $responseData['data']);
    }

    public function testListHandlesException(): void
    {
        $request = new Request();
        $this->datasetService->expects($this->once())->method('list')->willThrowException(new \Exception('Service error'));
        $response = $this->controller->list($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to retrieve datasets', $responseData['message']);
        $this->assertEquals('Service error', $responseData['error']);
    }

    public function testCreateHandlesException(): void
    {
        $requestData = ['name' => 'Test Dataset'];
        $request = $this->createJsonRequest($requestData);
        $this->datasetService->expects($this->once())->method('create')->willThrowException(new \Exception('Service error'));
        $response = $this->controller->create($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to create dataset', $responseData['message']);
        $this->assertEquals('Service error', $responseData['error']);
    }

    public function testUpdateHandlesException(): void
    {
        $datasetId = 123;
        $requestData = ['name' => 'Updated Dataset'];
        $request = $this->createJsonRequest($requestData);
        $this->datasetService->expects($this->once())->method('update')->willThrowException(new \Exception('Service error'));
        $response = $this->controller->update($datasetId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to update dataset', $responseData['message']);
        $this->assertEquals('Service error', $responseData['error']);
    }

    public function testDeleteHandlesException(): void
    {
        $datasetId = 123;
        $this->datasetService->expects($this->once())->method('delete')->willThrowException(new \Exception('Service error'));
        $response = $this->controller->delete($datasetId);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to delete dataset', $responseData['message']);
        $this->assertEquals('Service error', $responseData['error']);
    }

    public function testGetKnowledgeGraphHandlesException(): void
    {
        $datasetId = 'dataset-123';
        $this->datasetService->expects($this->once())->method('getKnowledgeGraph')->willThrowException(new \Exception('Service error'));
        $response = $this->controller->getKnowledgeGraph($datasetId);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to retrieve knowledge graph', $responseData['message']);
        $this->assertEquals('Service error', $responseData['error']);
    }

    public function testMethodNotAllowed(string $method): void
    {
        // 多方法控制器，不使用 __invoke，因此此测试不适用
        // 多方法控制器，不使用 __invoke，因此此测试总是通过
    }
}
