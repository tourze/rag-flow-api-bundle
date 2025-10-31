<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service\Mapper;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Entity\Chunk;
use Tourze\RAGFlowApiBundle\Service\Mapper\ChunkMapper;

/**
 * @internal
 */
#[CoversClass(ChunkMapper::class)]
final class ChunkMapperTest extends TestCase
{
    private ChunkMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new ChunkMapper();
    }

    public function testMapApiDataToEntityWithCompleteData(): void
    {
        $chunk = new Chunk();
        $apiData = [
            'content' => 'This is test chunk content.',
            'content_with_weight' => 'This is test chunk content. [weight: 0.95]',
            'page_number' => 5,
            'position' => 2,
            'start_pos' => 100,
            'end_pos' => 250,
            'token_count' => 42,
            'similarity_score' => 0.87,
            'positions' => [
                ['start' => 100, 'end' => 150],
                ['start' => 200, 'end' => 250],
            ],
            'embedding_vector' => [0.1, 0.2, 0.3, 0.4],
            'keywords' => ['test', 'chunk', 'content'],
            'metadata' => [
                'source' => 'test-document',
                'category' => 'technical',
            ],
            'create_time' => 1640995200000,
            'update_time' => '2024-01-15 10:30:45',
        ];

        $this->mapper->mapApiDataToEntity($chunk, $apiData);

        $this->assertSame('This is test chunk content.', $chunk->getContent());
        $this->assertSame('This is test chunk content. [weight: 0.95]', $chunk->getContentWithWeight());
        $this->assertSame(5, $chunk->getPageNumber());
        $this->assertSame(2, $chunk->getPosition());
        $this->assertSame(100, $chunk->getStartPos());
        $this->assertSame(250, $chunk->getEndPos());
        $this->assertSame(42, $chunk->getTokenCount());
        $this->assertSame(0.87, $chunk->getSimilarityScore());
        $this->assertSame([
            ['start' => 100, 'end' => 150],
            ['start' => 200, 'end' => 250],
        ], $chunk->getPositions());
        $this->assertSame([0.1, 0.2, 0.3, 0.4], $chunk->getEmbeddingVector());
        $this->assertSame(['test', 'chunk', 'content'], $chunk->getKeywords());
        $this->assertSame([
            'source' => 'test-document',
            'category' => 'technical',
        ], $chunk->getMetadata());
        $this->assertNotNull($chunk->getRemoteCreateTime());
        $this->assertSame('2022-01-01', $chunk->getRemoteCreateTime()->format('Y-m-d'));
        $this->assertNotNull($chunk->getRemoteUpdateTime());
        $this->assertSame('2024-01-15', $chunk->getRemoteUpdateTime()->format('Y-m-d'));
    }

    public function testMapApiDataToEntityWithMinimalData(): void
    {
        $chunk = new Chunk();
        $apiData = [
            'content' => 'Minimal chunk content.',
        ];

        $this->mapper->mapApiDataToEntity($chunk, $apiData);

        $this->assertSame('Minimal chunk content.', $chunk->getContent());
        $this->assertNull($chunk->getContentWithWeight());
        $this->assertNull($chunk->getPageNumber());
        $this->assertNull($chunk->getPosition());
        $this->assertNull($chunk->getStartPos());
        $this->assertNull($chunk->getEndPos());
        $this->assertNull($chunk->getTokenCount());
        $this->assertNull($chunk->getSimilarityScore());
    }

    public function testMapApiDataToEntityWithNumericStringFields(): void
    {
        $chunk = new Chunk();
        $apiData = [
            'content' => 'Test content',
            'page_number' => '10',
            'position' => '5',
            'start_pos' => '200',
            'end_pos' => '350',
            'token_count' => '75',
            'similarity_score' => '0.95',
        ];

        $this->mapper->mapApiDataToEntity($chunk, $apiData);

        $this->assertSame(10, $chunk->getPageNumber());
        $this->assertSame(5, $chunk->getPosition());
        $this->assertSame(200, $chunk->getStartPos());
        $this->assertSame(350, $chunk->getEndPos());
        $this->assertSame(75, $chunk->getTokenCount());
        $this->assertSame(0.95, $chunk->getSimilarityScore());
    }

    public function testMapApiDataToEntityFiltersEmbeddingVector(): void
    {
        $chunk = new Chunk();
        $apiData = [
            'content' => 'Test content',
            'embedding_vector' => [1, '2.5', 'abc', null, 3.7, true],
        ];

        $this->mapper->mapApiDataToEntity($chunk, $apiData);

        // array_filter 保留原始键，所以结果是 [0 => 1.0, 1 => 2.5, 4 => 3.7]
        $this->assertSame([0 => 1.0, 1 => 2.5, 4 => 3.7], $chunk->getEmbeddingVector());
    }

    public function testMapApiDataToEntityFiltersKeywords(): void
    {
        $chunk = new Chunk();
        $apiData = [
            'content' => 'Test content',
            'keywords' => ['valid', 123, 'another-valid', null, '', 'third'],
        ];

        $this->mapper->mapApiDataToEntity($chunk, $apiData);

        // is_string('') 返回 true，所以空字符串会被保留
        $this->assertSame(['valid', 'another-valid', '', 'third'], $chunk->getKeywords());
    }

    public function testMapApiDataToEntityPreservesPositionsStructure(): void
    {
        $chunk = new Chunk();
        $apiData = [
            'content' => 'Test content',
            'positions' => [
                'paragraph' => ['start' => 0, 'end' => 120],
                'sentence' => ['start' => 50, 'end' => 80],
            ],
        ];

        $this->mapper->mapApiDataToEntity($chunk, $apiData);

        $this->assertSame([
            'paragraph' => ['start' => 0, 'end' => 120],
            'sentence' => ['start' => 50, 'end' => 80],
        ], $chunk->getPositions());
    }

    public function testMapApiDataToEntityConvertsTimestampFromMilliseconds(): void
    {
        $chunk = new Chunk();
        $apiData = [
            'content' => 'Test content',
            'create_time' => 1640995200000,
            'update_time' => 1642204800000,
        ];

        $this->mapper->mapApiDataToEntity($chunk, $apiData);

        $this->assertInstanceOf(\DateTimeImmutable::class, $chunk->getRemoteCreateTime());
        $this->assertSame('2022-01-01', $chunk->getRemoteCreateTime()->format('Y-m-d'));
        $this->assertInstanceOf(\DateTimeImmutable::class, $chunk->getRemoteUpdateTime());
        $this->assertSame('2022-01-15', $chunk->getRemoteUpdateTime()->format('Y-m-d'));
    }

    public function testMapApiDataToEntityConvertsTimestampFromString(): void
    {
        $chunk = new Chunk();
        $apiData = [
            'content' => 'Test content',
            'create_time' => '2024-01-15 10:30:45',
            'update_time' => '2024-01-20 15:45:30',
        ];

        $this->mapper->mapApiDataToEntity($chunk, $apiData);

        $this->assertInstanceOf(\DateTimeImmutable::class, $chunk->getRemoteCreateTime());
        $this->assertSame('2024-01-15', $chunk->getRemoteCreateTime()->format('Y-m-d'));
        $this->assertInstanceOf(\DateTimeImmutable::class, $chunk->getRemoteUpdateTime());
        $this->assertSame('2024-01-20', $chunk->getRemoteUpdateTime()->format('Y-m-d'));
    }

    public function testMapApiDataToEntityHandlesInvalidTimestamp(): void
    {
        $chunk = new Chunk();
        $apiData = [
            'content' => 'Test content',
            'create_time' => 'invalid-date',
            'update_time' => null,
        ];

        $this->mapper->mapApiDataToEntity($chunk, $apiData);

        $this->assertInstanceOf(\DateTimeImmutable::class, $chunk->getRemoteCreateTime());
        $this->assertSame('1970-01-01', $chunk->getRemoteCreateTime()->format('Y-m-d'));
        $this->assertNull($chunk->getRemoteUpdateTime());
    }

    public function testMapApiDataToEntityIgnoresInvalidFieldTypes(): void
    {
        $chunk = new Chunk();
        $apiData = [
            'content' => 'Test content',
            'page_number' => 'not-a-number',
            'similarity_score' => 'invalid',
            'positions' => 'not-an-array',
            'embedding_vector' => 'not-an-array',
            'keywords' => 'not-an-array',
            'metadata' => 'not-an-array',
        ];

        $this->mapper->mapApiDataToEntity($chunk, $apiData);

        $this->assertNull($chunk->getPageNumber());
        $this->assertNull($chunk->getSimilarityScore());
        $this->assertNull($chunk->getPositions());
        $this->assertNull($chunk->getEmbeddingVector());
        $this->assertNull($chunk->getKeywords());
        $this->assertNull($chunk->getMetadata());
    }

    public function testMapApiDataToEntityHandlesEmptyArrays(): void
    {
        $chunk = new Chunk();
        $apiData = [
            'content' => 'Test content',
            'positions' => [],
            'embedding_vector' => [],
            'keywords' => [],
            'metadata' => [],
        ];

        $this->mapper->mapApiDataToEntity($chunk, $apiData);

        $this->assertSame([], $chunk->getPositions());
        $this->assertSame([], $chunk->getEmbeddingVector());
        $this->assertSame([], $chunk->getKeywords());
        $this->assertSame([], $chunk->getMetadata());
    }
}
