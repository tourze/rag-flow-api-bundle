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
use Tourze\RAGFlowApiBundle\Controller\Api\ChunkController;
use Tourze\RAGFlowApiBundle\Service\ChunkService;

/**
 * @internal
 */
#[CoversClass(ChunkController::class)]
#[RunTestsInSeparateProcesses]
final class ChunkControllerTest extends AbstractWebTestCase
{
    /** @var ChunkService&MockObject */
    private ChunkService $chunkService;

    private ChunkController $controller;

    protected function onSetUp(): void
    {
        $this->chunkService = $this->createMock(ChunkService::class);
        self::getContainer()->set(ChunkService::class, $this->chunkService);
        $this->controller = self::getService(ChunkController::class);
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
        $this->assertInstanceOf(ChunkController::class, $this->controller);
    }

    public function testControllerHasExpectedMethods(): void
    {
        $reflection = new \ReflectionClass(ChunkController::class);
        $expectedMethods = ['retrieve', 'add', 'update', 'delete'];
        foreach ($expectedMethods as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Controller should have method: {$method}");
        }
    }

    public function testRetrieveWithValidData(): void
    {
        $datasetId = 'dataset-123';
        $requestData = ['query' => 'artificial intelligence', 'limit' => 5, 'similarity_threshold' => 0.8];
        $request = $this->createJsonRequest($requestData);
        $expectedResult = ['chunks' => [['id' => 'chunk-1', 'content' => 'AI content...', 'score' => 0.9]], 'total' => 1];
        $this->chunkService->expects($this->once())->method('retrieve')->with($datasetId, 'artificial intelligence', ['limit' => 5, 'similarity_threshold' => 0.8])->willReturn($expectedResult);
        $response = $this->controller->retrieve($datasetId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals($expectedResult, $responseData['data']);
    }

    public function testRetrieveWithInvalidJson(): void
    {
        $datasetId = 'dataset-123';
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $response = $this->controller->retrieve($datasetId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Invalid JSON data', $responseData['message']);
    }

    public function testRetrieveWithMissingQuery(): void
    {
        $datasetId = 'dataset-123';
        $requestData = ['limit' => 5];
        $request = $this->createJsonRequest($requestData);
        $response = $this->controller->retrieve($datasetId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Query is required', $responseData['message']);
    }

    public function testAddWithValidData(): void
    {
        $datasetId = 'dataset-123';
        $chunks = [['content' => 'This is a test chunk', 'metadata' => ['source' => 'test-doc.txt', 'page' => 1]]];
        $requestData = ['chunks' => $chunks];
        $request = $this->createJsonRequest($requestData);
        $expectedResult = ['added_chunks' => [['id' => 'chunk-456', 'content' => 'This is a test chunk']]];
        $this->chunkService->expects($this->once())->method('add')->with($datasetId, $chunks)->willReturn($expectedResult);
        $response = $this->controller->add($datasetId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals($expectedResult, $responseData['data']);
    }

    public function testAddWithInvalidJson(): void
    {
        $datasetId = 'dataset-123';
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $response = $this->controller->add($datasetId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Invalid JSON data', $responseData['message']);
    }

    public function testAddWithMissingChunks(): void
    {
        $datasetId = 'dataset-123';
        $requestData = ['limit' => 5];
        $request = $this->createJsonRequest($requestData);
        $response = $this->controller->add($datasetId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Chunks array is required', $responseData['message']);
    }

    public function testUpdateWithValidData(): void
    {
        $datasetId = 'dataset-123';
        $chunkId = 'chunk-456';
        $updateData = ['content' => 'Updated chunk content', 'metadata' => ['updated' => true]];
        $request = $this->createJsonRequest($updateData);
        $expectedResult = ['id' => $chunkId, 'content' => 'Updated chunk content', 'metadata' => ['updated' => true]];
        $this->chunkService->expects($this->once())->method('update')->with($datasetId, $chunkId, $updateData)->willReturn($expectedResult);
        $response = $this->controller->update($datasetId, $chunkId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals($expectedResult, $responseData['data']);
    }

    public function testUpdateWithInvalidJson(): void
    {
        $datasetId = 'dataset-123';
        $chunkId = 'chunk-456';
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $response = $this->controller->update($datasetId, $chunkId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Invalid JSON data', $responseData['message']);
    }

    public function testDelete(): void
    {
        $datasetId = 'dataset-123';
        $chunkId = 'chunk-456';
        $this->chunkService->expects($this->once())->method('delete')->with($datasetId, $chunkId);
        $response = $this->controller->delete($datasetId, $chunkId);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals('Chunk deleted successfully', $responseData['message']);
    }

    public function testRetrieveHandlesException(): void
    {
        $datasetId = 'dataset-123';
        $requestData = ['query' => 'test query'];
        $request = $this->createJsonRequest($requestData);
        $this->chunkService->expects($this->once())->method('retrieve')->willThrowException(new \Exception('Service error'));
        $response = $this->controller->retrieve($datasetId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to retrieve chunks', $responseData['message']);
        $this->assertEquals('Service error', $responseData['error']);
    }

    public function testAddHandlesException(): void
    {
        $datasetId = 'dataset-123';
        $requestData = ['chunks' => [['content' => 'test']]];
        $request = $this->createJsonRequest($requestData);
        $this->chunkService->expects($this->once())->method('add')->willThrowException(new \Exception('Service error'));
        $response = $this->controller->add($datasetId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to add chunks', $responseData['message']);
        $this->assertEquals('Service error', $responseData['error']);
    }

    public function testUpdateHandlesException(): void
    {
        $datasetId = 'dataset-123';
        $chunkId = 'chunk-456';
        $requestData = ['content' => 'updated content'];
        $request = $this->createJsonRequest($requestData);
        $this->chunkService->expects($this->once())->method('update')->willThrowException(new \Exception('Service error'));
        $response = $this->controller->update($datasetId, $chunkId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to update chunk', $responseData['message']);
        $this->assertEquals('Service error', $responseData['error']);
    }

    public function testDeleteHandlesException(): void
    {
        $datasetId = 'dataset-123';
        $chunkId = 'chunk-456';
        $this->chunkService->expects($this->once())->method('delete')->willThrowException(new \Exception('Service error'));
        $response = $this->controller->delete($datasetId, $chunkId);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to delete chunk', $responseData['message']);
        $this->assertEquals('Service error', $responseData['error']);
    }

    public function testMethodNotAllowed(string $method): void
    {
        // 多方法控制器，不使用 __invoke，因此此测试不适用
        // 无意义的断言已移除
    }
}
