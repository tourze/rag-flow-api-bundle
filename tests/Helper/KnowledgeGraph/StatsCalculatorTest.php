<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Helper\KnowledgeGraph;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Helper\KnowledgeGraph\StatsCalculator;

/**
 * @internal
 */
#[CoversClass(StatsCalculator::class)]
final class StatsCalculatorTest extends TestCase
{
    private StatsCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new StatsCalculator();
    }

    public function testCalculateReturnsZeroStatsForEmptyGraph(): void
    {
        $graphResult = [];

        $result = $this->calculator->calculate($graphResult);

        $this->assertSame(0, $result['total_entities']);
        $this->assertSame([], $result['entity_types']);
        $this->assertSame(0, $result['total_relations']);
        $this->assertSame([], $result['relation_types']);
    }

    public function testCalculateReturnsZeroStatsWhenEntitiesNotArray(): void
    {
        $graphResult = [
            'entities' => 'not-an-array',
            'relations' => [],
        ];

        $result = $this->calculator->calculate($graphResult);

        $this->assertSame(0, $result['total_entities']);
        $this->assertSame([], $result['entity_types']);
    }

    public function testCalculateReturnsZeroStatsWhenRelationsNotArray(): void
    {
        $graphResult = [
            'entities' => [],
            'relations' => 'not-an-array',
        ];

        $result = $this->calculator->calculate($graphResult);

        $this->assertSame(0, $result['total_relations']);
        $this->assertSame([], $result['relation_types']);
    }

    public function testCalculateCountsEntities(): void
    {
        $graphResult = [
            'entities' => [
                ['id' => '1', 'name' => 'Entity 1', 'type' => 'person'],
                ['id' => '2', 'name' => 'Entity 2', 'type' => 'company'],
                ['id' => '3', 'name' => 'Entity 3', 'type' => 'person'],
            ],
        ];

        $result = $this->calculator->calculate($graphResult);

        $this->assertSame(3, $result['total_entities']);
    }

    public function testCalculateCountsEntityTypes(): void
    {
        $graphResult = [
            'entities' => [
                ['id' => '1', 'name' => 'Entity 1', 'type' => 'person'],
                ['id' => '2', 'name' => 'Entity 2', 'type' => 'company'],
                ['id' => '3', 'name' => 'Entity 3', 'type' => 'person'],
                ['id' => '4', 'name' => 'Entity 4', 'type' => 'person'],
            ],
        ];

        $result = $this->calculator->calculate($graphResult);

        $this->assertSame(3, $result['entity_types']['person']);
        $this->assertSame(1, $result['entity_types']['company']);
    }

    public function testCalculateCountsRelations(): void
    {
        $graphResult = [
            'relations' => [
                ['source' => '1', 'target' => '2', 'type' => 'knows'],
                ['source' => '2', 'target' => '3', 'type' => 'works_with'],
                ['source' => '1', 'target' => '3', 'type' => 'manages'],
            ],
        ];

        $result = $this->calculator->calculate($graphResult);

        $this->assertSame(3, $result['total_relations']);
    }

    public function testCalculateCountsRelationTypes(): void
    {
        $graphResult = [
            'relations' => [
                ['source' => '1', 'target' => '2', 'type' => 'knows'],
                ['source' => '2', 'target' => '3', 'type' => 'knows'],
                ['source' => '1', 'target' => '3', 'type' => 'manages'],
                ['source' => '4', 'target' => '5', 'type' => 'knows'],
            ],
        ];

        $result = $this->calculator->calculate($graphResult);

        $this->assertSame(3, $result['relation_types']['knows']);
        $this->assertSame(1, $result['relation_types']['manages']);
    }

    public function testCalculateIgnoresNonArrayEntities(): void
    {
        $graphResult = [
            'entities' => [
                ['id' => '1', 'name' => 'Entity 1', 'type' => 'person'],
                'not-an-array',
                ['id' => '2', 'name' => 'Entity 2', 'type' => 'company'],
            ],
        ];

        $result = $this->calculator->calculate($graphResult);

        $this->assertSame(2, $result['total_entities']);
    }

    public function testCalculateIgnoresNonArrayRelations(): void
    {
        $graphResult = [
            'relations' => [
                ['source' => '1', 'target' => '2', 'type' => 'knows'],
                'not-an-array',
                ['source' => '2', 'target' => '3', 'type' => 'works_with'],
            ],
        ];

        $result = $this->calculator->calculate($graphResult);

        $this->assertSame(2, $result['total_relations']);
    }

    public function testCalculateIgnoresEntitiesWithoutType(): void
    {
        $graphResult = [
            'entities' => [
                ['id' => '1', 'name' => 'Entity 1', 'type' => 'person'],
                ['id' => '2', 'name' => 'Entity 2'],
                ['id' => '3', 'name' => 'Entity 3', 'type' => 'company'],
            ],
        ];

        $result = $this->calculator->calculate($graphResult);

        $this->assertSame(3, $result['total_entities']);
        $this->assertSame(1, $result['entity_types']['person']);
        $this->assertSame(1, $result['entity_types']['company']);
        $this->assertArrayNotHasKey('', $result['entity_types']);
    }

    public function testCalculateIgnoresRelationsWithoutType(): void
    {
        $graphResult = [
            'relations' => [
                ['source' => '1', 'target' => '2', 'type' => 'knows'],
                ['source' => '2', 'target' => '3'],
                ['source' => '3', 'target' => '4', 'type' => 'works_with'],
            ],
        ];

        $result = $this->calculator->calculate($graphResult);

        $this->assertSame(3, $result['total_relations']);
        $this->assertSame(1, $result['relation_types']['knows']);
        $this->assertSame(1, $result['relation_types']['works_with']);
    }

    public function testCalculateHandlesNonStringTypes(): void
    {
        $graphResult = [
            'entities' => [
                ['id' => '1', 'name' => 'Entity 1', 'type' => 'person'],
                ['id' => '2', 'name' => 'Entity 2', 'type' => 123],
                ['id' => '3', 'name' => 'Entity 3', 'type' => 'person'],
            ],
            'relations' => [
                ['source' => '1', 'target' => '2', 'type' => 'knows'],
                ['source' => '2', 'target' => '3', 'type' => []],
            ],
        ];

        $result = $this->calculator->calculate($graphResult);

        $this->assertSame(2, $result['entity_types']['person']);
        $this->assertArrayNotHasKey('123', $result['entity_types']);
        $this->assertSame(1, $result['relation_types']['knows']);
    }

    public function testCalculateWithCompleteGraph(): void
    {
        $graphResult = [
            'entities' => [
                ['id' => '1', 'name' => 'Alice', 'type' => 'person'],
                ['id' => '2', 'name' => 'Bob', 'type' => 'person'],
                ['id' => '3', 'name' => 'Acme Corp', 'type' => 'company'],
                ['id' => '4', 'name' => 'Tech Inc', 'type' => 'company'],
            ],
            'relations' => [
                ['source' => '1', 'target' => '3', 'type' => 'works_for'],
                ['source' => '2', 'target' => '3', 'type' => 'works_for'],
                ['source' => '1', 'target' => '2', 'type' => 'colleague'],
                ['source' => '3', 'target' => '4', 'type' => 'partner'],
            ],
        ];

        $result = $this->calculator->calculate($graphResult);

        $this->assertSame(4, $result['total_entities']);
        $this->assertSame(2, $result['entity_types']['person']);
        $this->assertSame(2, $result['entity_types']['company']);
        $this->assertSame(4, $result['total_relations']);
        $this->assertSame(2, $result['relation_types']['works_for']);
        $this->assertSame(1, $result['relation_types']['colleague']);
        $this->assertSame(1, $result['relation_types']['partner']);
    }
}
