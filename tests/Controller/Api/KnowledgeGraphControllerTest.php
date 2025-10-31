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
use Tourze\RAGFlowApiBundle\Controller\Api\KnowledgeGraphController;
use Tourze\RAGFlowApiBundle\Service\DatasetService;

/**
 * @internal
 */
#[CoversClass(KnowledgeGraphController::class)]
#[RunTestsInSeparateProcesses]
final class KnowledgeGraphControllerTest extends AbstractWebTestCase
{
    private DatasetService&MockObject $datasetService;

    private KnowledgeGraphController $controller;

    protected function onSetUp(): void
    {
        $this->datasetService = $this->createMock(DatasetService::class);
        // Only set the service if it hasn't been initialized yet
        if (!self::getContainer()->has(DatasetService::class)) {
            self::getContainer()->set(DatasetService::class, $this->datasetService);
        }
        $this->controller = self::getService(KnowledgeGraphController::class);
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
        $this->assertInstanceOf(KnowledgeGraphController::class, $this->controller);
    }

    public function testControllerHasExpectedMethods(): void
    {
        $reflection = new \ReflectionClass(KnowledgeGraphController::class);
        $expectedMethods = ['getByDataset', 'searchEntities', 'getEntityRelations', 'getStats'];
        foreach ($expectedMethods as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Controller should have method: {$method}");
        }
    }

    public function testGetByDataset(): void
    {
        $datasetId = 'dataset-123';
        $request = new Request(['depth' => '2', 'limit' => '100', 'entity_types' => 'person', 'relation_types' => 'related_to']);
        $expectedResult = ['nodes' => [['id' => 'entity-1', 'name' => 'AI', 'type' => 'concept']], 'edges' => [['source' => 'entity-1', 'target' => 'entity-2', 'type' => 'related_to']]];
        $this->datasetService->expects($this->once())->method('getKnowledgeGraph')->with($datasetId)->willReturn($expectedResult);
        $response = $this->controller->getByDataset($datasetId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals($expectedResult, $responseData['data']);
        $this->assertEquals($datasetId, $responseData['dataset_id']);
        $this->assertArrayHasKey('filters', $responseData);
    }

    public function testSearchEntitiesWithValidQuery(): void
    {
        $datasetId = 'dataset-123';
        $requestData = ['query' => 'artificial intelligence', 'entity_type' => 'concept', 'limit' => 10];
        $request = $this->createJsonRequest($requestData);
        $mockGraphResult = ['entities' => [['id' => 'entity-1', 'name' => 'Artificial Intelligence', 'type' => 'concept'], ['id' => 'entity-2', 'name' => 'Machine Learning', 'type' => 'concept'], ['id' => 'entity-3', 'name' => 'John Doe', 'type' => 'person']]];
        $this->datasetService->expects($this->once())->method('getKnowledgeGraph')->with($datasetId)->willReturn($mockGraphResult);
        $response = $this->controller->searchEntities($datasetId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals($datasetId, $responseData['dataset_id']);
        $this->assertArrayHasKey('entities', $responseData['data']);
        $this->assertEquals('artificial intelligence', $responseData['data']['query']);
    }

    public function testSearchEntitiesWithInvalidJson(): void
    {
        $datasetId = 'dataset-123';
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $response = $this->controller->searchEntities($datasetId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Invalid JSON data', $responseData['message']);
    }

    public function testSearchEntitiesWithMissingQuery(): void
    {
        $datasetId = 'dataset-123';
        $requestData = ['entity_type' => 'concept'];
        $request = $this->createJsonRequest($requestData);
        $response = $this->controller->searchEntities($datasetId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Search query is required', $responseData['message']);
    }

    public function testGetEntityRelations(): void
    {
        $datasetId = 'dataset-123';
        $entityId = 'entity-1';
        $request = new Request(['depth' => '2', 'max_relations' => '100']);
        $mockGraphResult = ['entities' => [['id' => 'entity-1', 'name' => 'AI', 'type' => 'concept'], ['id' => 'entity-2', 'name' => 'ML', 'type' => 'concept']], 'relations' => [['source' => 'entity-1', 'target' => 'entity-2', 'type' => 'related_to']]];
        $this->datasetService->expects($this->once())->method('getKnowledgeGraph')->with($datasetId)->willReturn($mockGraphResult);
        $response = $this->controller->getEntityRelations($datasetId, $entityId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals($datasetId, $responseData['dataset_id']);
        $this->assertEquals($entityId, $responseData['data']['entity_id']);
        $this->assertArrayHasKey('relations', $responseData['data']);
        $this->assertArrayHasKey('related_entities', $responseData['data']);
    }

    public function testGetStats(): void
    {
        $datasetId = 'dataset-123';
        $mockGraphResult = ['entities' => [['id' => 'entity-1', 'name' => 'AI', 'type' => 'concept'], ['id' => 'entity-2', 'name' => 'John', 'type' => 'person']], 'relations' => [['source' => 'entity-1', 'target' => 'entity-2', 'type' => 'related_to']]];
        $this->datasetService->expects($this->once())->method('getKnowledgeGraph')->with($datasetId)->willReturn($mockGraphResult);
        $response = $this->controller->getStats($datasetId);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals($datasetId, $responseData['dataset_id']);
        $this->assertArrayHasKey('total_entities', $responseData['data']);
        $this->assertArrayHasKey('total_relations', $responseData['data']);
        $this->assertArrayHasKey('entity_types', $responseData['data']);
        $this->assertArrayHasKey('relation_types', $responseData['data']);
    }

    public function testGetByDatasetHandlesException(): void
    {
        $datasetId = 'dataset-123';
        $request = new Request();
        $this->datasetService->expects($this->once())->method('getKnowledgeGraph')->willThrowException(new \Exception('Service error'));
        $response = $this->controller->getByDataset($datasetId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to retrieve knowledge graph', $responseData['message']);
        $this->assertEquals('Service error', $responseData['error']);
        $this->assertEquals($datasetId, $responseData['dataset_id']);
    }

    public function testSearchEntitiesHandlesException(): void
    {
        $datasetId = 'dataset-123';
        $requestData = ['query' => 'test'];
        $request = $this->createJsonRequest($requestData);
        $this->datasetService->expects($this->once())->method('getKnowledgeGraph')->willThrowException(new \Exception('Service error'));
        $response = $this->controller->searchEntities($datasetId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to search entities', $responseData['message']);
        $this->assertEquals('Service error', $responseData['error']);
        $this->assertEquals($datasetId, $responseData['dataset_id']);
    }

    public function testGetEntityRelationsHandlesException(): void
    {
        $datasetId = 'dataset-123';
        $entityId = 'entity-1';
        $request = new Request();
        $this->datasetService->expects($this->once())->method('getKnowledgeGraph')->willThrowException(new \Exception('Service error'));
        $response = $this->controller->getEntityRelations($datasetId, $entityId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to retrieve entity relations', $responseData['message']);
        $this->assertEquals('Service error', $responseData['error']);
        $this->assertEquals($datasetId, $responseData['dataset_id']);
    }

    public function testGetStatsHandlesException(): void
    {
        $datasetId = 'dataset-123';
        $this->datasetService->expects($this->once())->method('getKnowledgeGraph')->willThrowException(new \Exception('Service error'));
        $response = $this->controller->getStats($datasetId);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to retrieve knowledge graph statistics', $responseData['message']);
        $this->assertEquals('Service error', $responseData['error']);
        $this->assertEquals($datasetId, $responseData['dataset_id']);
    }

    public function testMethodNotAllowed(string $method): void
    {
        // 多方法控制器，不使用 __invoke，因此此测试不适用
        // 无意义的断言已移除
    }
}
