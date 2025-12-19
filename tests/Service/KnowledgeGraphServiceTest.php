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
use Tourze\RAGFlowApiBundle\Request\GetKnowledgeGraphRequest;
use Tourze\RAGFlowApiBundle\Service\DatasetService;
use Tourze\RAGFlowApiBundle\Service\KnowledgeGraphService;
use Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManagerInterface;

/**
 * KnowledgeGraphService 集成测试
 *
 * 这是一个集成测试，使用真实的服务和数据库，但 Mock RAGFlowApiClient 网络请求
 *
 * @internal
 */
#[CoversClass(KnowledgeGraphService::class)]
#[RunTestsInSeparateProcesses]
class KnowledgeGraphServiceTest extends AbstractIntegrationTestCase
{
    private KnowledgeGraphService $service;
    private DatasetService $datasetService;
    private EntityManagerInterface $em;
    private RAGFlowInstance $ragFlowInstance;
    private Dataset $dataset;

    /** @var RAGFlowApiClient&MockObject */
    private RAGFlowApiClient $client;

    private RAGFlowInstanceManagerInterface $instanceManager;

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

        // 创建并持久化真实的 Dataset
        $this->dataset = new Dataset();
        $this->dataset->setName('Test Dataset');
        $this->dataset->setRemoteId('dataset-123');
        $this->dataset->setRagFlowInstance($this->ragFlowInstance);
        $this->em->persist($this->dataset);
        $this->em->flush();

        // Mock RAGFlowApiClient（网络请求客户端）
        $this->client = $this->createMock(RAGFlowApiClient::class);
        $this->client->method('getInstance')->willReturn($this->ragFlowInstance);

        // Mock RAGFlowInstanceManagerInterface
        $this->instanceManager = $this->createMock(RAGFlowInstanceManagerInterface::class);
        $this->instanceManager->method('getDefaultClient')->willReturn($this->client);

        // 将 Mock 服务注入到容器中
        self::getContainer()->set(RAGFlowInstanceManagerInterface::class, $this->instanceManager);

        // 从服务容器获取服务
        $this->datasetService = self::getService(DatasetService::class);
        $this->service = self::getService(KnowledgeGraphService::class);
    }

    public function test服务实例化(): void
    {
        $this->assertInstanceOf(KnowledgeGraphService::class, $this->service);
    }

    public function testSearchEntities(): void
    {
        $graphResult = ['entities' => [['id' => 'entity1', 'name' => 'Test Entity', 'type' => 'person'], ['id' => 'entity2', 'name' => 'Another Entity', 'type' => 'organization'], ['id' => 'entity3', 'name' => 'Test Company', 'type' => 'organization']]];

        // Mock API 客户端响应
        $this->client->method('request')->with(self::callback(function ($request) {
            return $request instanceof GetKnowledgeGraphRequest;
        }))->willReturn($graphResult);

        $searchData = ['query' => 'test', 'entity_type' => 'organization', 'limit' => 10];
        $results = $this->service->searchEntities('dataset-123', $searchData);

        $this->assertCount(1, $results);
        $this->assertSame('entity3', $results[0]['id']);
        $this->assertSame('Test Company', $results[0]['name']);
    }

    public function testCalculateStats(): void
    {
        $graphResult = ['entities' => [['id' => 'entity1', 'name' => 'Entity 1', 'type' => 'person'], ['id' => 'entity2', 'name' => 'Entity 2', 'type' => 'person'], ['id' => 'entity3', 'name' => 'Entity 3', 'type' => 'organization']], 'relations' => [['source' => 'entity1', 'target' => 'entity2', 'type' => 'knows'], ['source' => 'entity2', 'target' => 'entity3', 'type' => 'works_at']]];

        // Mock API 客户端响应
        $this->client->method('request')->with(self::callback(function ($request) {
            return $request instanceof GetKnowledgeGraphRequest;
        }))->willReturn($graphResult);

        $stats = $this->service->calculateStats('dataset-123');

        $this->assertSame(3, $stats['total_entities']);
        $this->assertSame(2, $stats['total_relations']);
        $this->assertSame(2, $stats['entity_types']['person']);
        $this->assertSame(1, $stats['entity_types']['organization']);
        $this->assertSame(1, $stats['relation_types']['knows']);
        $this->assertSame(1, $stats['relation_types']['works_at']);
    }

    public function test搜索实体返回匹配结果(): void
    {
        $graphResult = ['entities' => [['id' => 'entity1', 'name' => 'Test Entity', 'type' => 'person'], ['id' => 'entity2', 'name' => 'Another Entity', 'type' => 'organization'], ['id' => 'entity3', 'name' => 'Test Company', 'type' => 'organization']]];

        // Mock API 客户端响应
        $this->client->method('request')->with(self::callback(function ($request) {
            return $request instanceof GetKnowledgeGraphRequest;
        }))->willReturn($graphResult);

        $searchData = ['query' => 'test', 'entity_type' => 'organization', 'limit' => 10];
        $results = $this->service->searchEntities('dataset-123', $searchData);

        $this->assertCount(1, $results);
        $this->assertSame('entity3', $results[0]['id']);
        $this->assertSame('Test Company', $results[0]['name']);
    }

    public function test搜索实体不含类型限制(): void
    {
        $graphResult = ['entities' => [['id' => 'entity1', 'name' => 'Test Entity', 'type' => 'person'], ['id' => 'entity2', 'name' => 'Test Company', 'type' => 'organization']]];

        // Mock API 客户端响应
        $this->client->method('request')->with(self::callback(function ($request) {
            return $request instanceof GetKnowledgeGraphRequest;
        }))->willReturn($graphResult);

        $searchData = ['query' => 'test', 'limit' => 10];
        $results = $this->service->searchEntities('dataset-123', $searchData);

        $this->assertCount(2, $results);
    }

    public function test搜索实体限制返回数量(): void
    {
        $graphResult = ['entities' => [['id' => 'entity1', 'name' => 'Entity 1', 'type' => 'person'], ['id' => 'entity2', 'name' => 'Entity 2', 'type' => 'person'], ['id' => 'entity3', 'name' => 'Entity 3', 'type' => 'person']]];

        // Mock API 客户端响应
        $this->client->method('request')->with(self::callback(function ($request) {
            return $request instanceof GetKnowledgeGraphRequest;
        }))->willReturn($graphResult);

        $searchData = ['query' => '', 'limit' => 2];
        $results = $this->service->searchEntities('dataset-123', $searchData);

        $this->assertCount(2, $results);
    }

    public function test搜索实体处理空图谱(): void
    {
        $graphResult = [];

        // Mock API 客户端响应
        $this->client->method('request')->with(self::callback(function ($request) {
            return $request instanceof GetKnowledgeGraphRequest;
        }))->willReturn($graphResult);

        $searchData = ['query' => 'test', 'limit' => 10];
        $results = $this->service->searchEntities('dataset-123', $searchData);

        $this->assertCount(0, $results);
    }

    public function test获取实体关系(): void
    {
        $graphResult = ['entities' => [['id' => 'entity1', 'name' => 'Entity 1'], ['id' => 'entity2', 'name' => 'Entity 2']], 'relations' => [['source' => 'entity1', 'target' => 'entity2', 'type' => 'knows'], ['source' => 'entity2', 'target' => 'entity3', 'type' => 'works_at']]];

        // Mock API 客户端响应
        $this->client->method('request')->with(self::callback(function ($request) {
            return $request instanceof GetKnowledgeGraphRequest;
        }))->willReturn($graphResult);

        $result = $this->service->getEntityRelations('dataset-123', 'entity1', 10);

        $this->assertArrayHasKey('relations', $result);
        $this->assertArrayHasKey('entities', $result);
        $this->assertCount(1, $result['relations']);
        $this->assertSame('entity1', $result['relations'][0]['source']);
    }

    public function test获取实体关系处理空关系(): void
    {
        $graphResult = ['entities' => [['id' => 'entity1', 'name' => 'Entity 1']]];

        // Mock API 客户端响应
        $this->client->method('request')->with(self::callback(function ($request) {
            return $request instanceof GetKnowledgeGraphRequest;
        }))->willReturn($graphResult);

        $result = $this->service->getEntityRelations('dataset-123', 'entity1', 10);

        $this->assertArrayHasKey('relations', $result);
        $this->assertArrayHasKey('entities', $result);
        $this->assertCount(0, $result['relations']);
        $this->assertCount(0, $result['entities']);
    }

    public function test计算统计信息(): void
    {
        $graphResult = ['entities' => [['id' => 'entity1', 'name' => 'Entity 1', 'type' => 'person'], ['id' => 'entity2', 'name' => 'Entity 2', 'type' => 'person'], ['id' => 'entity3', 'name' => 'Entity 3', 'type' => 'organization']], 'relations' => [['source' => 'entity1', 'target' => 'entity2', 'type' => 'knows'], ['source' => 'entity2', 'target' => 'entity3', 'type' => 'works_at']]];

        // Mock API 客户端响应
        $this->client->method('request')->with(self::callback(function ($request) {
            return $request instanceof GetKnowledgeGraphRequest;
        }))->willReturn($graphResult);

        $stats = $this->service->calculateStats('dataset-123');

        $this->assertSame(3, $stats['total_entities']);
        $this->assertSame(2, $stats['total_relations']);
        $this->assertSame(2, $stats['entity_types']['person']);
        $this->assertSame(1, $stats['entity_types']['organization']);
        $this->assertSame(1, $stats['relation_types']['knows']);
        $this->assertSame(1, $stats['relation_types']['works_at']);
    }

    public function test计算统计信息处理空图谱(): void
    {
        $graphResult = [];

        // Mock API 客户端响应
        $this->client->method('request')->with(self::callback(function ($request) {
            return $request instanceof GetKnowledgeGraphRequest;
        }))->willReturn($graphResult);

        $stats = $this->service->calculateStats('dataset-123');

        $this->assertSame(0, $stats['total_entities']);
        $this->assertSame(0, $stats['total_relations']);
        $this->assertEmpty($stats['entity_types']);
        $this->assertEmpty($stats['relation_types']);
    }
}
