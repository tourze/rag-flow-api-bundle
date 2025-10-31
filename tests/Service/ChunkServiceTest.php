<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Client\RAGFlowApiClient;
use Tourze\RAGFlowApiBundle\Entity\Chunk;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Repository\DocumentRepository;
use Tourze\RAGFlowApiBundle\Request\AddChunksRequest;
use Tourze\RAGFlowApiBundle\Request\DeleteChunkRequest;
use Tourze\RAGFlowApiBundle\Request\RetrieveChunksRequest;
use Tourze\RAGFlowApiBundle\Request\UpdateChunkRequest;
use Tourze\RAGFlowApiBundle\Service\ChunkService;
use Tourze\RAGFlowApiBundle\Service\LocalDataSyncService;
use Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManagerInterface;

/**
 * @internal
 */
#[CoversClass(ChunkService::class)]
#[RunTestsInSeparateProcesses]
class ChunkServiceTest extends AbstractIntegrationTestCase
{
    /** @var RAGFlowApiClient&MockObject */
    private RAGFlowApiClient $client;

    /** @var RAGFlowInstanceManagerInterface&MockObject */
    private RAGFlowInstanceManagerInterface $instanceManager;

    /** @var LocalDataSyncService&MockObject */
    private LocalDataSyncService $localDataSyncService;

    /** @var DocumentRepository&MockObject */
    private DocumentRepository $documentRepository;

    private ChunkService $chunkService;

    protected function onSetUp(): void
    {
        $this->client = $this->createMock(RAGFlowApiClient::class);
        $this->instanceManager = $this->createMock(RAGFlowInstanceManagerInterface::class);
        $this->localDataSyncService = $this->createMock(LocalDataSyncService::class);
        $this->documentRepository = $this->createMock(DocumentRepository::class);
        $this->instanceManager->expects($this->once())->method('getDefaultClient')->willReturn($this->client);
        // Only set services if they haven't been initialized yet
        if (!self::getContainer()->has(RAGFlowInstanceManagerInterface::class)) {
            self::getContainer()->set(RAGFlowInstanceManagerInterface::class, $this->instanceManager);
        }
        if (!self::getContainer()->has(LocalDataSyncService::class)) {
            self::getContainer()->set(LocalDataSyncService::class, $this->localDataSyncService);
        }
        if (!self::getContainer()->has(DocumentRepository::class)) {
            self::getContainer()->set(DocumentRepository::class, $this->documentRepository);
        }
        $this->chunkService = self::getService(ChunkService::class);
    }

    public function testRetrieve(): void
    {
        $datasetId = 'dataset-123';
        $query = 'test query';
        $options = ['limit' => 10, 'offset' => 0];
        $apiResponse = ['chunks' => [['id' => 'chunk-1', 'content' => 'Content 1', 'document_id' => 'doc-1'], ['id' => 'chunk-2', 'content' => 'Content 2', 'document_id' => 'doc-2']], 'total' => 2];
        $mockDocument1 = $this->createMock(Document::class);
        $mockDocument2 = $this->createMock(Document::class);
        $mockChunk1 = $this->createMock(Chunk::class);
        $mockChunk2 = $this->createMock(Chunk::class);
        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof RetrieveChunksRequest;
        }))->willReturn($apiResponse);
        $this->documentRepository->expects($this->exactly(2))->method('findOneBy')->with(self::callback(function ($criteria) {
            return isset($criteria['remoteId']) && in_array($criteria['remoteId'], ['doc-1', 'doc-2'], true);
        }))->willReturnOnConsecutiveCalls($mockDocument1, $mockDocument2);
        $this->localDataSyncService->expects($this->exactly(2))->method('syncChunkFromApi')->willReturnOnConsecutiveCalls($mockChunk1, $mockChunk2);
        $result = $this->chunkService->retrieve($datasetId, $query, $options);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(Chunk::class, $result);
    }

    public function testRetrieveWithoutOptions(): void
    {
        $datasetId = 'dataset-123';
        $query = 'test query';
        $apiResponse = ['chunks' => [['id' => 'chunk-1', 'content' => 'Content 1']], 'total' => 1];
        $this->client->expects($this->once())->method('request')->with(self::isInstanceOf(RetrieveChunksRequest::class))->willReturn($apiResponse);
        $result = $this->chunkService->retrieve($datasetId, $query);
        $this->assertIsArray($result);
    }

    public function testAdd(): void
    {
        $datasetId = 'dataset-123';
        $chunks = [['content' => 'New chunk content 1'], ['content' => 'New chunk content 2']];
        $expectedResponse = ['chunks' => [['id' => 'chunk-new-1', 'content' => 'New chunk content 1'], ['id' => 'chunk-new-2', 'content' => 'New chunk content 2']]];
        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof AddChunksRequest;
        }))->willReturn($expectedResponse);
        $result = $this->chunkService->add($datasetId, $chunks);
        $this->assertEquals($expectedResponse, $result);
    }

    public function testUpdate(): void
    {
        $datasetId = 'dataset-123';
        $chunkId = 'chunk-456';
        $data = ['content' => 'Updated chunk content'];
        $expectedResponse = ['chunk' => ['id' => 'chunk-456', 'content' => 'Updated chunk content']];
        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof UpdateChunkRequest;
        }))->willReturn($expectedResponse);
        $result = $this->chunkService->update($datasetId, $chunkId, $data);
        $this->assertEquals($expectedResponse, $result);
    }

    public function testDelete(): void
    {
        $datasetId = 'dataset-123';
        $chunkId = 'chunk-456';
        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof DeleteChunkRequest;
        }))->willReturn(['success' => true]);
        $result = $this->chunkService->delete($datasetId, $chunkId);
        $this->assertTrue($result);
    }

    public function testDeleteWithException(): void
    {
        $datasetId = 'dataset-123';
        $chunkId = 'chunk-456';
        $this->client->expects($this->once())->method('request')->with(self::isInstanceOf(DeleteChunkRequest::class))->willThrowException(new \RuntimeException('Delete failed'));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Delete failed');
        $this->chunkService->delete($datasetId, $chunkId);
    }

    public function testServiceWithDifferentDatasetIds(): void
    {
        $testCases = ['simple-id', 'complex_id-123', 'id-with-numbers-456'];
        foreach ($testCases as $datasetId) {
            $client = $this->createMock(RAGFlowApiClient::class);
            $instanceManager = $this->createMock(RAGFlowInstanceManagerInterface::class);
            $localDataSyncService = $this->createMock(LocalDataSyncService::class);
            $documentRepository = $this->createMock(DocumentRepository::class);
            $instanceManager->expects($this->once())->method('getDefaultClient')->willReturn($client);
            // Only set services if they haven't been initialized yet
            if (!self::getContainer()->has(RAGFlowInstanceManagerInterface::class)) {
                self::getContainer()->set(RAGFlowInstanceManagerInterface::class, $instanceManager);
            }
            if (!self::getContainer()->has(LocalDataSyncService::class)) {
                self::getContainer()->set(LocalDataSyncService::class, $localDataSyncService);
            }
            if (!self::getContainer()->has(DocumentRepository::class)) {
                self::getContainer()->set(DocumentRepository::class, $documentRepository);
            }
            $chunkService = self::getService(ChunkService::class);
            $client->expects($this->once())->method('request')->willReturn(['chunks' => [], 'total' => 0]);
            $result = $chunkService->retrieve($datasetId, 'test query');
            $this->assertIsArray($result);
        }
    }
}
