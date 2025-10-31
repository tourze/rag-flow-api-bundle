<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Helper\KnowledgeGraph;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Helper\KnowledgeGraph\RelationExtractor;

/**
 * @internal
 */
#[CoversClass(RelationExtractor::class)]
final class RelationExtractorTest extends TestCase
{
    private RelationExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new RelationExtractor();
    }

    public function testExtractEntityRelationsReturnsEmptyWhenNoRelations(): void
    {
        $graphResult = [];
        $entityId = 'entity1';
        $maxRelations = 10;

        $result = $this->extractor->extractEntityRelations($graphResult, $entityId, $maxRelations);

        $this->assertSame(['relations' => [], 'entities' => []], $result);
    }

    public function testExtractEntityRelationsReturnsEmptyWhenRelationsNotArray(): void
    {
        $graphResult = ['relations' => 'not-an-array'];
        $entityId = 'entity1';
        $maxRelations = 10;

        $result = $this->extractor->extractEntityRelations($graphResult, $entityId, $maxRelations);

        $this->assertSame(['relations' => [], 'entities' => []], $result);
    }

    public function testExtractEntityRelationsFindsSourceRelations(): void
    {
        $graphResult = [
            'relations' => [
                ['source' => 'entity1', 'target' => 'entity2', 'type' => 'knows'],
                ['source' => 'entity2', 'target' => 'entity3', 'type' => 'works_with'],
                ['source' => 'entity1', 'target' => 'entity3', 'type' => 'manages'],
            ],
        ];
        $entityId = 'entity1';
        $maxRelations = 10;

        $result = $this->extractor->extractEntityRelations($graphResult, $entityId, $maxRelations);

        $this->assertCount(2, $result['relations']);
        $this->assertSame('entity1', $result['relations'][0]['source']);
        $this->assertSame('entity1', $result['relations'][1]['source']);
        $this->assertContains('entity2', $result['entities']);
        $this->assertContains('entity3', $result['entities']);
    }

    public function testExtractEntityRelationsFindsTargetRelations(): void
    {
        $graphResult = [
            'relations' => [
                ['source' => 'entity2', 'target' => 'entity1', 'type' => 'reports_to'],
                ['source' => 'entity3', 'target' => 'entity1', 'type' => 'reports_to'],
            ],
        ];
        $entityId = 'entity1';
        $maxRelations = 10;

        $result = $this->extractor->extractEntityRelations($graphResult, $entityId, $maxRelations);

        $this->assertCount(2, $result['relations']);
        $this->assertContains('entity2', $result['entities']);
        $this->assertContains('entity3', $result['entities']);
    }

    public function testExtractEntityRelationsRespectMaxRelationsLimit(): void
    {
        $graphResult = [
            'relations' => [
                ['source' => 'entity1', 'target' => 'entity2', 'type' => 'type1'],
                ['source' => 'entity1', 'target' => 'entity3', 'type' => 'type2'],
                ['source' => 'entity1', 'target' => 'entity4', 'type' => 'type3'],
                ['source' => 'entity1', 'target' => 'entity5', 'type' => 'type4'],
            ],
        ];
        $entityId = 'entity1';
        $maxRelations = 2;

        $result = $this->extractor->extractEntityRelations($graphResult, $entityId, $maxRelations);

        $this->assertCount(2, $result['relations']);
        $this->assertCount(2, $result['entities']);
    }

    public function testExtractEntityRelationsIgnoresNonArrayRelations(): void
    {
        $graphResult = [
            'relations' => [
                ['source' => 'entity1', 'target' => 'entity2', 'type' => 'type1'],
                'not-an-array',
                ['source' => 'entity1', 'target' => 'entity3', 'type' => 'type2'],
            ],
        ];
        $entityId = 'entity1';
        $maxRelations = 10;

        $result = $this->extractor->extractEntityRelations($graphResult, $entityId, $maxRelations);

        $this->assertCount(2, $result['relations']);
    }

    public function testExtractEntityRelationsDeduplicatesRelatedEntities(): void
    {
        $graphResult = [
            'relations' => [
                ['source' => 'entity1', 'target' => 'entity2', 'type' => 'type1'],
                ['source' => 'entity2', 'target' => 'entity1', 'type' => 'type2'],
            ],
        ];
        $entityId = 'entity1';
        $maxRelations = 10;

        $result = $this->extractor->extractEntityRelations($graphResult, $entityId, $maxRelations);

        $this->assertCount(2, $result['relations']);
        $this->assertCount(1, $result['entities']);
        $this->assertSame(['entity2'], $result['entities']);
    }

    public function testExtractEntityRelationsHandlesBidirectionalRelations(): void
    {
        $graphResult = [
            'relations' => [
                ['source' => 'entity1', 'target' => 'entity2', 'type' => 'colleague'],
                ['source' => 'entity2', 'target' => 'entity1', 'type' => 'colleague'],
                ['source' => 'entity3', 'target' => 'entity1', 'type' => 'manager'],
            ],
        ];
        $entityId = 'entity1';
        $maxRelations = 10;

        $result = $this->extractor->extractEntityRelations($graphResult, $entityId, $maxRelations);

        $this->assertCount(3, $result['relations']);
        $this->assertCount(2, $result['entities']);
        $this->assertContains('entity2', $result['entities']);
        $this->assertContains('entity3', $result['entities']);
    }

    public function testExtractEntityRelationsExcludesSelfReferences(): void
    {
        $graphResult = [
            'relations' => [
                ['source' => 'entity1', 'target' => 'entity2', 'type' => 'type1'],
                ['source' => 'entity1', 'target' => 'entity1', 'type' => 'self_ref'],
            ],
        ];
        $entityId = 'entity1';
        $maxRelations = 10;

        $result = $this->extractor->extractEntityRelations($graphResult, $entityId, $maxRelations);

        $this->assertCount(2, $result['relations']);
        $this->assertSame(['entity2'], $result['entities']);
    }

    public function testExtractEntityRelationsHandlesMissingSourceOrTarget(): void
    {
        $graphResult = [
            'relations' => [
                ['source' => 'entity1', 'type' => 'type1'],
                ['target' => 'entity1', 'type' => 'type2'],
                ['source' => 'entity1', 'target' => 'entity2', 'type' => 'type3'],
            ],
        ];
        $entityId = 'entity1';
        $maxRelations = 10;

        $result = $this->extractor->extractEntityRelations($graphResult, $entityId, $maxRelations);

        $this->assertCount(3, $result['relations']);
        $this->assertSame(['entity2'], $result['entities']);
    }

    public function testExtractEntityRelationsHandlesNonStringIds(): void
    {
        $graphResult = [
            'relations' => [
                ['source' => 'entity1', 'target' => 123, 'type' => 'type1'],
                ['source' => 'entity1', 'target' => 'entity2', 'type' => 'type2'],
            ],
        ];
        $entityId = 'entity1';
        $maxRelations = 10;

        $result = $this->extractor->extractEntityRelations($graphResult, $entityId, $maxRelations);

        $this->assertCount(2, $result['relations']);
        $this->assertSame(['entity2'], $result['entities']);
    }
}
