<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Service\DatasetService;
use Tourze\RAGFlowApiBundle\Service\KnowledgeGraphService;

/**
 * @internal
 */
#[CoversClass(KnowledgeGraphService::class)]
final class KnowledgeGraphServiceTest extends TestCase
{
    private KnowledgeGraphService $service;

    private DatasetService $datasetService;

    protected function setUp(): void
    {
        $this->datasetService = $this->createMock(DatasetService::class);
        $this->service = new KnowledgeGraphService($this->datasetService);
    }

    public function test服务实例化(): void
    {
        $this->assertInstanceOf(KnowledgeGraphService::class, $this->service);
    }

    public function test搜索实体返回匹配结果(): void
    {
        $graphResult = ['entities' => [['id' => 'entity1', 'name' => 'Test Entity', 'type' => 'person'], ['id' => 'entity2', 'name' => 'Another Entity', 'type' => 'organization'], ['id' => 'entity3', 'name' => 'Test Company', 'type' => 'organization']]];
        $this->datasetService->expects($this->once())->method('getKnowledgeGraph')->with('dataset-123')->willReturn($graphResult);
        $searchData = ['query' => 'test', 'entity_type' => 'organization', 'limit' => 10];
        $results = $this->service->searchEntities('dataset-123', $searchData);
        $this->assertCount(1, $results);
        $this->assertSame('entity3', $results[0]['id']);
        $this->assertSame('Test Company', $results[0]['name']);
    }

    public function test搜索实体不含类型限制(): void
    {
        $graphResult = ['entities' => [['id' => 'entity1', 'name' => 'Test Entity', 'type' => 'person'], ['id' => 'entity2', 'name' => 'Test Company', 'type' => 'organization']]];
        $this->datasetService->expects($this->once())->method('getKnowledgeGraph')->with('dataset-123')->willReturn($graphResult);
        $searchData = ['query' => 'test', 'limit' => 10];
        $results = $this->service->searchEntities('dataset-123', $searchData);
        $this->assertCount(2, $results);
    }

    public function test搜索实体限制返回数量(): void
    {
        $graphResult = ['entities' => [['id' => 'entity1', 'name' => 'Entity 1', 'type' => 'person'], ['id' => 'entity2', 'name' => 'Entity 2', 'type' => 'person'], ['id' => 'entity3', 'name' => 'Entity 3', 'type' => 'person']]];
        $this->datasetService->expects($this->once())->method('getKnowledgeGraph')->with('dataset-123')->willReturn($graphResult);
        $searchData = ['query' => '', 'limit' => 2];
        $results = $this->service->searchEntities('dataset-123', $searchData);
        $this->assertCount(2, $results);
    }

    public function test搜索实体处理空图谱(): void
    {
        $graphResult = [];
        $this->datasetService->expects($this->once())->method('getKnowledgeGraph')->with('dataset-123')->willReturn($graphResult);
        $searchData = ['query' => 'test', 'limit' => 10];
        $results = $this->service->searchEntities('dataset-123', $searchData);
        $this->assertCount(0, $results);
    }

    public function test获取实体关系(): void
    {
        $graphResult = ['entities' => [['id' => 'entity1', 'name' => 'Entity 1'], ['id' => 'entity2', 'name' => 'Entity 2']], 'relations' => [['source' => 'entity1', 'target' => 'entity2', 'type' => 'knows'], ['source' => 'entity2', 'target' => 'entity3', 'type' => 'works_at']]];
        $this->datasetService->expects($this->once())->method('getKnowledgeGraph')->with('dataset-123')->willReturn($graphResult);
        $result = $this->service->getEntityRelations('dataset-123', 'entity1', 10);
        $this->assertArrayHasKey('relations', $result);
        $this->assertArrayHasKey('entities', $result);
        $this->assertCount(1, $result['relations']);
        $this->assertSame('entity1', $result['relations'][0]['source']);
    }

    public function test获取实体关系处理空关系(): void
    {
        $graphResult = ['entities' => [['id' => 'entity1', 'name' => 'Entity 1']]];
        $this->datasetService->expects($this->once())->method('getKnowledgeGraph')->with('dataset-123')->willReturn($graphResult);
        $result = $this->service->getEntityRelations('dataset-123', 'entity1', 10);
        $this->assertArrayHasKey('relations', $result);
        $this->assertArrayHasKey('entities', $result);
        $this->assertCount(0, $result['relations']);
        $this->assertCount(0, $result['entities']);
    }

    public function test计算统计信息(): void
    {
        $graphResult = ['entities' => [['id' => 'entity1', 'name' => 'Entity 1', 'type' => 'person'], ['id' => 'entity2', 'name' => 'Entity 2', 'type' => 'person'], ['id' => 'entity3', 'name' => 'Entity 3', 'type' => 'organization']], 'relations' => [['source' => 'entity1', 'target' => 'entity2', 'type' => 'knows'], ['source' => 'entity2', 'target' => 'entity3', 'type' => 'works_at']]];
        $this->datasetService->expects($this->once())->method('getKnowledgeGraph')->with('dataset-123')->willReturn($graphResult);
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
        $this->datasetService->expects($this->once())->method('getKnowledgeGraph')->with('dataset-123')->willReturn($graphResult);
        $stats = $this->service->calculateStats('dataset-123');
        $this->assertSame(0, $stats['total_entities']);
        $this->assertSame(0, $stats['total_relations']);
        $this->assertEmpty($stats['entity_types']);
        $this->assertEmpty($stats['relation_types']);
    }
}
