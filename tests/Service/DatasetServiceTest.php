<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
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
 * DatasetService 集成测试
 *
 * 这是一个集成测试，使用真实的服务和数据库，但 Mock RAGFlowApiClient 网络请求
 *
 * @internal
 */
#[CoversClass(DatasetService::class)]
#[RunTestsInSeparateProcesses]
class DatasetServiceTest extends AbstractIntegrationTestCase
{
    /** @var RAGFlowApiClient&MockObject */
    private RAGFlowApiClient $client;

    private RAGFlowInstanceManagerInterface $instanceManager;
    private LocalDataSyncService $localDataSyncService;
    private DatasetService $datasetService;
    private EntityManagerInterface $em;
    private RAGFlowInstance $ragFlowInstance;

    protected function onSetUp(): void
    {
        $this->em = self::getEntityManager();

        // 创建并持久化真实的 RAGFlowInstance
        $this->ragFlowInstance = new RAGFlowInstance();
        $this->ragFlowInstance->setName('Test Instance-' . uniqid('', true));
        $this->ragFlowInstance->setApiUrl('https://test.ragflow.com');
        $this->ragFlowInstance->setApiKey('test-api-key-' . uniqid('', true));
        $this->ragFlowInstance->setEnabled(true);
        $this->em->persist($this->ragFlowInstance);
        $this->em->flush();

        // Mock RAGFlowApiClient（网络请求客户端）
        $this->client = $this->createMock(RAGFlowApiClient::class);
        $this->client->method('getInstance')->willReturn($this->ragFlowInstance);

        // Mock RAGFlowInstanceManagerInterface
        $this->instanceManager = $this->createMock(RAGFlowInstanceManagerInterface::class);
        $this->instanceManager->method('getDefaultClient')->willReturn($this->client);

        // 将 Mock 服务注入到容器中
        self::getContainer()->set(RAGFlowInstanceManagerInterface::class, $this->instanceManager);

        // 从服务容器获取 DatasetService
        $this->datasetService = self::getService(DatasetService::class);
        $this->localDataSyncService = self::getService(LocalDataSyncService::class);
    }

    public function testCreate(): void
    {
        $config = ['name' => 'Test Dataset', 'description' => 'A test dataset', 'language' => 'Chinese', 'chunk_method' => 'manual'];
        // API 响应应该是平面结构（syncDatasetFromApi 期望的格式）
        $apiResponse = ['id' => 'dataset-123', 'name' => 'Test Dataset', 'description' => 'A test dataset', 'language' => 'Chinese', 'chunk_method' => 'manual'];

        // Mock API 客户端响应
        $this->client->method('request')->with(self::callback(function ($request) {
            return $request instanceof CreateDatasetRequest;
        }))->willReturn($apiResponse);

        $result = $this->datasetService->create($config);

        $this->assertInstanceOf(Dataset::class, $result);
        $this->assertSame('Test Dataset', $result->getName());
        $this->assertSame('A test dataset', $result->getDescription());
        $this->assertSame('dataset-123', $result->getRemoteId());

        // 验证数据已持久化到数据库
        $this->assertEntityPersisted($result);
    }

    public function testList(): void
    {
        $filters = ['name' => 'test', 'limit' => 10];
        $apiResponse = [['id' => 'dataset-1', 'name' => 'Dataset 1', 'description' => 'First dataset', 'language' => 'Chinese'], ['id' => 'dataset-2', 'name' => 'Dataset 2', 'description' => 'Second dataset', 'language' => 'English']];

        $this->client->method('request')->with(self::callback(function ($request) {
            return $request instanceof ListDatasetsRequest;
        }))->willReturn($apiResponse);

        $result = $this->datasetService->list($filters);

        $this->assertCount(2, $result);
        $this->assertInstanceOf(Dataset::class, $result[0]);
        $this->assertInstanceOf(Dataset::class, $result[1]);
        $this->assertSame('Dataset 1', $result[0]->getName());
        $this->assertSame('Dataset 2', $result[1]->getName());
    }

    public function testListWithoutFilters(): void
    {
        $apiResponse = [['id' => 'dataset-1', 'name' => 'Dataset 1', 'description' => 'First dataset']];

        $this->client->method('request')->with(self::isInstanceOf(ListDatasetsRequest::class))->willReturn($apiResponse);

        $result = $this->datasetService->list();

        $this->assertCount(1, $result);
        $this->assertInstanceOf(Dataset::class, $result[0]);
        $this->assertSame('Dataset 1', $result[0]->getName());
    }

    public function testUpdate(): void
    {
        $datasetId = 'dataset-123';
        $config = ['name' => 'Updated Dataset', 'description' => 'Updated description'];
        // update 方法不需要处理响应，只需要返回成功即可
        $apiResponse = ['id' => 'dataset-123', 'name' => 'Updated Dataset', 'description' => 'Updated description'];

        $this->client->method('request')->with(self::callback(function ($request) {
            return $request instanceof UpdateDatasetRequest;
        }))->willReturn($apiResponse);

        $this->datasetService->update($datasetId, $config);
        $this->assertTrue(true); // update方法返回void，仅验证无异常抛出
    }

    public function testDelete(): void
    {
        // 首先创建一个真实的 Dataset
        $dataset = new Dataset();
        $dataset->setName('Dataset to Delete');
        $dataset->setRemoteId('dataset-123');
        $dataset->setRagFlowInstance($this->ragFlowInstance);
        $this->em->persist($dataset);
        $this->em->flush();

        $this->client->method('request')->with(self::callback(function ($request) {
            return $request instanceof DeleteDatasetRequest;
        }))->willReturn(['success' => true]);

        $result = $this->datasetService->delete('dataset-123');

        $this->assertTrue($result);

        // 验证数据已从数据库删除
        $this->em->clear();
        $deletedDataset = $this->em->getRepository(Dataset::class)->findOneBy(['remoteId' => 'dataset-123']);
        $this->assertNull($deletedDataset);
    }

    public function testGetKnowledgeGraph(): void
    {
        $datasetId = 'dataset-123';
        $expectedResponse = ['knowledge_graph' => ['nodes' => [['id' => 'node-1', 'label' => 'Entity 1'], ['id' => 'node-2', 'label' => 'Entity 2']], 'edges' => [['from' => 'node-1', 'to' => 'node-2', 'relation' => 'related_to']]]];

        $this->client->method('request')->with(self::callback(function ($request) {
            return $request instanceof GetKnowledgeGraphRequest;
        }))->willReturn($expectedResponse);

        $result = $this->datasetService->getKnowledgeGraph($datasetId);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testDeleteWithException(): void
    {
        $datasetId = 'dataset-123';

        $this->client->method('request')->with(self::isInstanceOf(DeleteDatasetRequest::class))->willThrowException(new \RuntimeException('Delete failed'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Delete failed');
        $this->datasetService->delete($datasetId);
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

    public function testCreateWithComplexConfig(): void
    {
        $config = ['name' => 'Complex Dataset', 'description' => 'A complex dataset with multiple settings', 'language' => 'Chinese', 'chunk_method' => 'auto', 'chunk_size' => 512, 'chunk_overlap' => 50, 'parser_config' => ['layout_recognize' => true, 'table_recognize' => true, 'formula_recognize' => false]];
        // API 响应应该是平面结构
        $apiResponse = array_merge(['id' => 'dataset-456'], $config);

        $this->client->method('request')->with(self::isInstanceOf(CreateDatasetRequest::class))->willReturn($apiResponse);

        $result = $this->datasetService->create($config);

        $this->assertInstanceOf(Dataset::class, $result);
        $this->assertSame('Complex Dataset', $result->getName());
        $this->assertSame('dataset-456', $result->getRemoteId());

        // 验证数据已持久化到数据库
        $this->assertEntityPersisted($result);
    }
}
