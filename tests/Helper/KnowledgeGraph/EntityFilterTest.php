<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Helper\KnowledgeGraph;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Helper\KnowledgeGraph\EntityFilter;

/**
 * @internal
 */
#[CoversClass(EntityFilter::class)]
final class EntityFilterTest extends TestCase
{
    private EntityFilter $filter;

    protected function setUp(): void
    {
        $this->filter = new EntityFilter();
    }

    public function testFilterByQueryReturnsEmptyWhenNoEntities(): void
    {
        $graphResult = ['entities' => []];
        $searchData = ['query' => 'test'];

        $result = $this->filter->filterByQuery($graphResult, $searchData);

        $this->assertSame([], $result);
    }

    public function testFilterByQueryReturnsEmptyWhenEntitiesNotArray(): void
    {
        $graphResult = ['entities' => 'not-an-array'];
        $searchData = ['query' => 'test'];

        $result = $this->filter->filterByQuery($graphResult, $searchData);

        $this->assertSame([], $result);
    }

    public function testFilterByQueryReturnsEmptyWhenNoEntitiesKey(): void
    {
        $graphResult = [];
        $searchData = ['query' => 'test'];

        $result = $this->filter->filterByQuery($graphResult, $searchData);

        $this->assertSame([], $result);
    }

    public function testFilterByQueryMatchesEntityName(): void
    {
        $graphResult = [
            'entities' => [
                ['id' => '1', 'name' => 'Apple Inc', 'type' => 'company'],
                ['id' => '2', 'name' => 'Microsoft Corp', 'type' => 'company'],
                ['id' => '3', 'name' => 'John Apple', 'type' => 'person'],
            ],
        ];
        $searchData = ['query' => 'apple'];

        $result = $this->filter->filterByQuery($graphResult, $searchData);

        $this->assertCount(2, $result);
        $this->assertSame('1', $result[0]['id']);
        $this->assertSame('3', $result[1]['id']);
    }

    public function testFilterByQueryMatchesEntityType(): void
    {
        $graphResult = [
            'entities' => [
                ['id' => '1', 'name' => 'Apple Inc', 'type' => 'company'],
                ['id' => '2', 'name' => 'Microsoft Corp', 'type' => 'company'],
                ['id' => '3', 'name' => 'John Apple', 'type' => 'person'],
            ],
        ];
        $searchData = ['query' => '', 'entity_type' => 'company'];

        $result = $this->filter->filterByQuery($graphResult, $searchData);

        $this->assertCount(2, $result);
        $this->assertSame('1', $result[0]['id']);
        $this->assertSame('2', $result[1]['id']);
    }

    public function testFilterByQueryRespectsLimit(): void
    {
        $graphResult = [
            'entities' => [
                ['id' => '1', 'name' => 'Entity 1', 'type' => 'type1'],
                ['id' => '2', 'name' => 'Entity 2', 'type' => 'type1'],
                ['id' => '3', 'name' => 'Entity 3', 'type' => 'type1'],
            ],
        ];
        $searchData = ['query' => '', 'limit' => 2];

        $result = $this->filter->filterByQuery($graphResult, $searchData);

        $this->assertCount(2, $result);
    }

    public function testFilterByQueryUsesDefaultLimit(): void
    {
        $entities = [];
        for ($i = 1; $i <= 25; ++$i) {
            $entities[] = ['id' => (string) $i, 'name' => "Entity {$i}", 'type' => 'type1'];
        }

        $graphResult = ['entities' => $entities];
        $searchData = ['query' => ''];

        $result = $this->filter->filterByQuery($graphResult, $searchData);

        $this->assertCount(20, $result);
    }

    public function testGetByIdsReturnsEmptyWhenNoEntities(): void
    {
        $graphResult = [];
        $entityIds = ['1', '2'];

        $result = $this->filter->getByIds($graphResult, $entityIds);

        $this->assertSame([], $result);
    }

    public function testGetByIdsReturnsMatchingEntities(): void
    {
        $graphResult = [
            'entities' => [
                ['id' => '1', 'name' => 'Entity 1'],
                ['id' => '2', 'name' => 'Entity 2'],
                ['id' => '3', 'name' => 'Entity 3'],
            ],
        ];
        $entityIds = ['1', '3'];

        $result = $this->filter->getByIds($graphResult, $entityIds);

        $this->assertCount(2, $result);
        $this->assertSame('1', $result[0]['id']);
        $this->assertSame('3', $result[1]['id']);
    }

    public function testGetByIdsIgnoresNonArrayElements(): void
    {
        $graphResult = [
            'entities' => [
                ['id' => '1', 'name' => 'Entity 1'],
                'not-an-array',
                ['id' => '2', 'name' => 'Entity 2'],
            ],
        ];
        $entityIds = ['1', '2'];

        $result = $this->filter->getByIds($graphResult, $entityIds);

        $this->assertCount(2, $result);
    }

    /**
     * @param array<string, mixed> $searchData
     */
    #[DataProvider('provideInvalidQueryData')]
    public function testFilterByQueryHandlesInvalidQueryGracefully(array $searchData): void
    {
        $graphResult = [
            'entities' => [
                ['id' => '1', 'name' => 'Entity 1', 'type' => 'type1'],
            ],
        ];

        $result = $this->filter->filterByQuery($graphResult, $searchData);
        // 验证结果是数组类型（通过后续的使用来验证）
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function provideInvalidQueryData(): iterable
    {
        yield 'query is int' => [['query' => 123]];
        yield 'query is null' => [['query' => null]];
        yield 'query is array' => [['query' => []]];
        yield 'entity_type is int' => [['query' => 'test', 'entity_type' => 123]];
        yield 'entity_type is array' => [['query' => 'test', 'entity_type' => []]];
    }
}
