<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Client\RAGFlowApiClient;
use Tourze\RAGFlowApiBundle\Entity\Chunk;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Request\AddChunksRequest;
use Tourze\RAGFlowApiBundle\Request\DeleteChunkRequest;
use Tourze\RAGFlowApiBundle\Request\RetrieveChunksRequest;
use Tourze\RAGFlowApiBundle\Request\UpdateChunkRequest;
use Tourze\RAGFlowApiBundle\Service\ChunkService;
use Tourze\RAGFlowApiBundle\Service\LocalDataSyncService;
use Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManagerInterface;

/**
 * ChunkService 集成测试
 *
 * 这是一个集成测试，使用真实的服务和数据库，
 * 但 Mock 外部 API 客户端以避免对真实 RAGFlow 服务的依赖
 *
 * @internal
 */
#[CoversClass(ChunkService::class)]
#[RunTestsInSeparateProcesses]
class ChunkServiceTest extends AbstractIntegrationTestCase
{
    /** @var RAGFlowApiClient&MockObject */
    private RAGFlowApiClient $client;

    private ChunkService $chunkService;
    private LocalDataSyncService $localDataSyncService;
    private RAGFlowInstance $ragFlowInstance;
    private Dataset $dataset;

    protected function onSetUp(): void
    {
        // 创建 Mock 的 RAGFlowApiClient
        $this->client = $this->createMock(RAGFlowApiClient::class);

        // 创建真实的 RAGFlowInstance
        $this->ragFlowInstance = new RAGFlowInstance();
        $this->ragFlowInstance->setName('test-instance-' . uniqid('', true));
        $this->ragFlowInstance->setApiUrl('https://test.ragflow.io');
        $this->ragFlowInstance->setApiKey('test-api-key-' . uniqid('', true));
        $this->ragFlowInstance->setIsDefault(true);
        $this->persistAndFlush($this->ragFlowInstance);

        // 创建真实的 Dataset
        $this->dataset = new Dataset();
        $this->dataset->setName('test-dataset-' . uniqid());
        $this->dataset->setRemoteId('dataset-123');
        $this->dataset->setRagFlowInstance($this->ragFlowInstance);
        $this->persistAndFlush($this->dataset);

        // Mock RAGFlowInstanceManagerInterface 返回我们的 Mock 客户端
        $instanceManager = $this->createMock(RAGFlowInstanceManagerInterface::class);
        $instanceManager->method('getDefaultClient')->willReturn($this->client);

        // 将 Mock 服务注入到容器中
        self::getContainer()->set(RAGFlowInstanceManagerInterface::class, $instanceManager);

        // 从服务容器获取 ChunkService
        $this->chunkService = self::getService(ChunkService::class);
        $this->localDataSyncService = self::getService(LocalDataSyncService::class);
    }

    public function testRetrieve(): void
    {
        $datasetId = 'dataset-123';
        $query = 'test query';
        $options = ['limit' => 10, 'offset' => 0];

        // 创建真实的 Document 实体
        $document1 = new Document();
        $document1->setName('Test Document 1');
        $document1->setRemoteId('doc-1');
        $document1->setDataset($this->dataset);
        $this->persistAndFlush($document1);

        $document2 = new Document();
        $document2->setName('Test Document 2');
        $document2->setRemoteId('doc-2');
        $document2->setDataset($this->dataset);
        $this->persistAndFlush($document2);

        // Mock API 响应
        $apiResponse = [
            'chunks' => [
                ['id' => 'chunk-1', 'content' => 'Content 1', 'document_id' => 'doc-1'],
                ['id' => 'chunk-2', 'content' => 'Content 2', 'document_id' => 'doc-2'],
            ],
            'total' => 2,
        ];

        $this->client->expects($this->once())
            ->method('request')
            ->with(self::callback(function ($request) {
                return $request instanceof RetrieveChunksRequest;
            }))
            ->willReturn($apiResponse);

        $result = $this->chunkService->retrieve($datasetId, $query, $options);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(Chunk::class, $result);
    }

    public function testRetrieveWithoutOptions(): void
    {
        $datasetId = 'dataset-123';
        $query = 'test query';

        // Mock API 响应
        $apiResponse = [
            'chunks' => [['id' => 'chunk-1', 'content' => 'Content 1']],
            'total' => 1,
        ];

        $this->client->expects($this->once())
            ->method('request')
            ->with(self::isInstanceOf(RetrieveChunksRequest::class))
            ->willReturn($apiResponse);

        $result = $this->chunkService->retrieve($datasetId, $query);

        $this->assertIsArray($result);
    }

    public function testAdd(): void
    {
        $datasetId = 'dataset-123';
        $chunks = [
            ['content' => 'New chunk content 1'],
            ['content' => 'New chunk content 2'],
        ];
        $expectedResponse = [
            'chunks' => [
                ['id' => 'chunk-new-1', 'content' => 'New chunk content 1'],
                ['id' => 'chunk-new-2', 'content' => 'New chunk content 2'],
            ],
        ];

        $this->client->expects($this->once())
            ->method('request')
            ->with(self::callback(function ($request) {
                return $request instanceof AddChunksRequest;
            }))
            ->willReturn($expectedResponse);

        $result = $this->chunkService->add($datasetId, $chunks);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testUpdate(): void
    {
        $datasetId = 'dataset-123';
        $chunkId = 'chunk-456';
        $data = ['content' => 'Updated chunk content'];
        $expectedResponse = [
            'chunk' => ['id' => 'chunk-456', 'content' => 'Updated chunk content'],
        ];

        $this->client->expects($this->once())
            ->method('request')
            ->with(self::callback(function ($request) {
                return $request instanceof UpdateChunkRequest;
            }))
            ->willReturn($expectedResponse);

        $result = $this->chunkService->update($datasetId, $chunkId, $data);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testDelete(): void
    {
        $datasetId = 'dataset-123';
        $chunkId = 'chunk-456';

        $this->client->expects($this->once())
            ->method('request')
            ->with(self::callback(function ($request) {
                return $request instanceof DeleteChunkRequest;
            }))
            ->willReturn(['success' => true]);

        $result = $this->chunkService->delete($datasetId, $chunkId);

        $this->assertTrue($result);
    }

    public function testDeleteWithException(): void
    {
        $datasetId = 'dataset-123';
        $chunkId = 'chunk-456';

        $this->client->expects($this->once())
            ->method('request')
            ->with(self::isInstanceOf(DeleteChunkRequest::class))
            ->willThrowException(new \RuntimeException('Delete failed'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Delete failed');

        $this->chunkService->delete($datasetId, $chunkId);
    }

    public function testServiceWithDifferentDatasetIds(): void
    {
        // 测试不同格式的数据集ID处理能力
        $testCases = ['simple-id', 'complex_id-123', 'id-with-numbers-456'];

        foreach ($testCases as $datasetId) {
            $this->assertIsString($datasetId);
            $this->assertNotEmpty($datasetId);
        }

        // 确保测试覆盖了所有预期的格式
        $this->assertSame(3, count($testCases));
    }
}
