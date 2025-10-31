<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Client\RAGFlowApiClient;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Request\CreateDatasetRequest;
use Tourze\RAGFlowApiBundle\Request\DeleteDatasetRequest;
use Tourze\RAGFlowApiBundle\Request\GetKnowledgeGraphRequest;
use Tourze\RAGFlowApiBundle\Request\ListDatasetsRequest;
use Tourze\RAGFlowApiBundle\Request\UpdateDatasetRequest;
use Tourze\RAGFlowApiBundle\Service\DatasetService;
use Tourze\RAGFlowApiBundle\Service\LocalDataSyncService;
use Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManagerInterface;

/**
 * @internal
 */
#[CoversClass(DatasetService::class)]
#[RunTestsInSeparateProcesses]
class DatasetServiceTest extends AbstractIntegrationTestCase
{
    /** @var RAGFlowApiClient&MockObject */
    private RAGFlowApiClient $client;

    /** @var RAGFlowInstanceManagerInterface&MockObject */
    private RAGFlowInstanceManagerInterface $instanceManager;

    /** @var LocalDataSyncService&MockObject */
    private LocalDataSyncService $localDataSyncService;

    private DatasetService $datasetService;

    protected function onSetUp(): void
    {
        $this->client = $this->createMock(RAGFlowApiClient::class);
        $this->instanceManager = $this->createMock(RAGFlowInstanceManagerInterface::class);
        $this->localDataSyncService = $this->createMock(LocalDataSyncService::class);
        $this->instanceManager->expects($this->once())->method('getDefaultClient')->willReturn($this->client);
        // Only set services if they haven't been initialized yet
        if (!self::getContainer()->has(RAGFlowInstanceManagerInterface::class)) {
            self::getContainer()->set(RAGFlowInstanceManagerInterface::class, $this->instanceManager);
        }
        if (!self::getContainer()->has(LocalDataSyncService::class)) {
            self::getContainer()->set(LocalDataSyncService::class, $this->localDataSyncService);
        }
        $this->datasetService = self::getService(DatasetService::class);
    }

    public function testCreate(): void
    {
        $config = ['name' => 'Test Dataset', 'description' => 'A test dataset', 'language' => 'Chinese', 'chunk_method' => 'manual'];
        $apiResponse = ['dataset' => ['id' => 'dataset-123', 'name' => 'Test Dataset', 'description' => 'A test dataset', 'language' => 'Chinese', 'chunk_method' => 'manual']];
        $expectedDataset = $this->createMock(Dataset::class);
        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof CreateDatasetRequest;
        }))->willReturn($apiResponse);
        $this->client->expects($this->once())->method('getInstance')->willReturn($this->createMock(RAGFlowInstance::class));
        $this->localDataSyncService->expects($this->once())->method('syncDatasetFromApi')->willReturn($expectedDataset);
        $result = $this->datasetService->create($config);
        $this->assertSame($expectedDataset, $result);
    }

    public function testList(): void
    {
        $filters = ['name' => 'test', 'limit' => 10];
        $apiResponse = [['id' => 'dataset-1', 'name' => 'Dataset 1', 'description' => 'First dataset', 'language' => 'Chinese'], ['id' => 'dataset-2', 'name' => 'Dataset 2', 'description' => 'Second dataset', 'language' => 'English']];
        $expectedDatasets = [$this->createMock(Dataset::class), $this->createMock(Dataset::class)];
        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof ListDatasetsRequest;
        }))->willReturn($apiResponse);
        $this->client->expects($this->exactly(2))->method('getInstance')->willReturn($this->createMock(RAGFlowInstance::class));
        $this->localDataSyncService->expects($this->exactly(2))->method('syncDatasetFromApi')->willReturnOnConsecutiveCalls(...$expectedDatasets);
        $result = $this->datasetService->list($filters);
        $this->assertEquals($expectedDatasets, $result);
    }

    public function testListWithoutFilters(): void
    {
        $apiResponse = [['id' => 'dataset-1', 'name' => 'Dataset 1', 'description' => 'First dataset']];
        $expectedDataset = $this->createMock(Dataset::class);
        $this->client->expects($this->once())->method('request')->with(self::isInstanceOf(ListDatasetsRequest::class))->willReturn($apiResponse);
        $this->client->expects($this->once())->method('getInstance')->willReturn($this->createMock(RAGFlowInstance::class));
        $this->localDataSyncService->expects($this->once())->method('syncDatasetFromApi')->willReturn($expectedDataset);
        $result = $this->datasetService->list();
        $this->assertEquals([$expectedDataset], $result);
    }

    public function testUpdate(): void
    {
        $datasetId = 'dataset-123';
        $config = ['name' => 'Updated Dataset', 'description' => 'Updated description'];
        $apiResponse = ['dataset' => ['id' => 'dataset-123', 'name' => 'Updated Dataset', 'description' => 'Updated description']];
        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof UpdateDatasetRequest;
        }))->willReturn($apiResponse);
        $this->datasetService->update($datasetId, $config);
        // update方法返回void，无需断言返回值
        // 无意义的断言已移除
    }

    public function testDelete(): void
    {
        $datasetId = 'dataset-123';
        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof DeleteDatasetRequest;
        }))->willReturn(['success' => true]);
        $this->client->expects($this->once())->method('getInstance')->willReturn($this->createMock(RAGFlowInstance::class));
        $this->localDataSyncService->expects($this->once())->method('deleteLocalDataset');
        $result = $this->datasetService->delete($datasetId);
        $this->assertTrue($result);
    }

    public function testGetKnowledgeGraph(): void
    {
        $datasetId = 'dataset-123';
        $expectedResponse = ['knowledge_graph' => ['nodes' => [['id' => 'node-1', 'label' => 'Entity 1'], ['id' => 'node-2', 'label' => 'Entity 2']], 'edges' => [['from' => 'node-1', 'to' => 'node-2', 'relation' => 'related_to']]]];
        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof GetKnowledgeGraphRequest;
        }))->willReturn($expectedResponse);
        $result = $this->datasetService->getKnowledgeGraph($datasetId);
        $this->assertEquals($expectedResponse, $result);
    }

    public function testDeleteWithException(): void
    {
        $datasetId = 'dataset-123';
        $this->client->expects($this->once())->method('request')->with(self::isInstanceOf(DeleteDatasetRequest::class))->willThrowException(new \RuntimeException('Delete failed'));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Delete failed');
        $this->datasetService->delete($datasetId);
    }

    public function testServiceWithDifferentDatasetIds(): void
    {
        $testCases = ['simple-id', 'complex_id-123', 'id-with-numbers-456'];
        foreach ($testCases as $datasetId) {
            $this->client = $this->createMock(RAGFlowApiClient::class);
            $this->instanceManager = $this->createMock(RAGFlowInstanceManagerInterface::class);
            $this->localDataSyncService = $this->createMock(LocalDataSyncService::class);
            $this->instanceManager->expects($this->once())->method('getDefaultClient')->willReturn($this->client);
            // Only set services if they haven't been initialized yet
            if (!self::getContainer()->has(RAGFlowInstanceManagerInterface::class)) {
                self::getContainer()->set(RAGFlowInstanceManagerInterface::class, $this->instanceManager);
            }
            if (!self::getContainer()->has(LocalDataSyncService::class)) {
                self::getContainer()->set(LocalDataSyncService::class, $this->localDataSyncService);
            }
            $this->datasetService = self::getService(DatasetService::class);
            $this->client->expects($this->once())->method('request')->willReturn(['success' => true]);
            $this->client->expects($this->once())->method('getInstance')->willReturn($this->createMock(RAGFlowInstance::class));
            $this->localDataSyncService->expects($this->once())->method('deleteLocalDataset');
            $result = $this->datasetService->delete($datasetId);
            $this->assertTrue($result);
        }
    }

    public function testCreateWithComplexConfig(): void
    {
        $config = ['name' => 'Complex Dataset', 'description' => 'A complex dataset with multiple settings', 'language' => 'Chinese', 'chunk_method' => 'auto', 'chunk_size' => 512, 'chunk_overlap' => 50, 'parser_config' => ['layout_recognize' => true, 'table_recognize' => true, 'formula_recognize' => false]];
        $apiResponse = ['dataset' => array_merge(['id' => 'dataset-456'], $config)];
        $expectedDataset = $this->createMock(Dataset::class);
        $this->client->expects($this->once())->method('request')->with(self::isInstanceOf(CreateDatasetRequest::class))->willReturn($apiResponse);
        $this->client->expects($this->once())->method('getInstance')->willReturn($this->createMock(RAGFlowInstance::class));
        $this->localDataSyncService->expects($this->once())->method('syncDatasetFromApi')->willReturn($expectedDataset);
        $result = $this->datasetService->create($config);
        $this->assertSame($expectedDataset, $result);
    }
}
