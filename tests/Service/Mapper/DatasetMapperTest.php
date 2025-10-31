<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service\Mapper;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Service\Mapper\DatasetMapper;

/**
 * @internal
 */
#[CoversClass(DatasetMapper::class)]
final class DatasetMapperTest extends TestCase
{
    private DatasetMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new DatasetMapper();
    }

    public function testMapApiDataToEntityWithCompleteData(): void
    {
        $dataset = new Dataset();
        $apiData = [
            'id' => 'dataset-remote-123',
            'name' => 'Test Dataset',
            'description' => 'This is a test dataset for unit testing.',
            'chunk_method' => 'recursive',
            'language' => 'zh',
            'embedding_model' => 'text-embedding-ada-002',
            'status' => 'active',
            'create_time' => 1640995200000,
            'update_time' => '2024-01-15 10:30:45',
        ];

        $this->mapper->mapApiDataToEntity($dataset, $apiData);

        $this->assertSame('dataset-remote-123', $dataset->getRemoteId());
        $this->assertSame('Test Dataset', $dataset->getName());
        $this->assertSame('This is a test dataset for unit testing.', $dataset->getDescription());
        $this->assertSame('recursive', $dataset->getChunkMethod());
        $this->assertSame('zh', $dataset->getLanguage());
        $this->assertSame('text-embedding-ada-002', $dataset->getEmbeddingModel());
        $this->assertSame('active', $dataset->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $dataset->getRemoteCreateTime());
        $this->assertSame('2022-01-01', $dataset->getRemoteCreateTime()->format('Y-m-d'));
        $this->assertInstanceOf(\DateTimeImmutable::class, $dataset->getRemoteUpdateTime());
        $this->assertSame('2024-01-15', $dataset->getRemoteUpdateTime()->format('Y-m-d'));
    }

    public function testMapApiDataToEntityWithMinimalData(): void
    {
        $dataset = new Dataset();
        $apiData = [
            'name' => 'Minimal Dataset',
        ];

        $this->mapper->mapApiDataToEntity($dataset, $apiData);

        $this->assertSame('Minimal Dataset', $dataset->getName());
        $this->assertNull($dataset->getRemoteId());
        $this->assertNull($dataset->getDescription());
        $this->assertNull($dataset->getChunkMethod());
        $this->assertNull($dataset->getLanguage());
        $this->assertNull($dataset->getEmbeddingModel());
        $this->assertNull($dataset->getStatus());
    }

    public function testMapApiDataToEntityIgnoresNonStringFields(): void
    {
        $dataset = new Dataset();
        $apiData = [
            'id' => 12345,
            'name' => 'Test Dataset',
            'description' => ['not', 'a', 'string'],
            'chunk_method' => null,
            'language' => 123,
            'embedding_model' => true,
            'status' => [],
        ];

        $this->mapper->mapApiDataToEntity($dataset, $apiData);

        $this->assertNull($dataset->getRemoteId());
        $this->assertSame('Test Dataset', $dataset->getName());
        $this->assertNull($dataset->getDescription());
        $this->assertNull($dataset->getChunkMethod());
        $this->assertNull($dataset->getLanguage());
        $this->assertNull($dataset->getEmbeddingModel());
        $this->assertNull($dataset->getStatus());
    }

    public function testMapApiDataToEntityConvertsTimestampFromMilliseconds(): void
    {
        $dataset = new Dataset();
        $apiData = [
            'name' => 'Test Dataset',
            'create_time' => 1640995200000,
            'update_time' => 1642204800000,
        ];

        $this->mapper->mapApiDataToEntity($dataset, $apiData);

        $this->assertInstanceOf(\DateTimeImmutable::class, $dataset->getRemoteCreateTime());
        $this->assertSame('2022-01-01', $dataset->getRemoteCreateTime()->format('Y-m-d'));
        $this->assertInstanceOf(\DateTimeImmutable::class, $dataset->getRemoteUpdateTime());
        $this->assertSame('2022-01-15', $dataset->getRemoteUpdateTime()->format('Y-m-d'));
    }

    public function testMapApiDataToEntityConvertsTimestampFromString(): void
    {
        $dataset = new Dataset();
        $apiData = [
            'name' => 'Test Dataset',
            'create_time' => '2024-01-15 10:30:45',
            'update_time' => '2024-01-20 15:45:30',
        ];

        $this->mapper->mapApiDataToEntity($dataset, $apiData);

        $this->assertInstanceOf(\DateTimeImmutable::class, $dataset->getRemoteCreateTime());
        $this->assertSame('2024-01-15', $dataset->getRemoteCreateTime()->format('Y-m-d'));
        $this->assertInstanceOf(\DateTimeImmutable::class, $dataset->getRemoteUpdateTime());
        $this->assertSame('2024-01-20', $dataset->getRemoteUpdateTime()->format('Y-m-d'));
    }

    public function testMapApiDataToEntityHandlesInvalidTimestamp(): void
    {
        $dataset = new Dataset();
        $apiData = [
            'name' => 'Test Dataset',
            'create_time' => 'invalid-date',
            'update_time' => null,
        ];

        $this->mapper->mapApiDataToEntity($dataset, $apiData);

        $this->assertInstanceOf(\DateTimeImmutable::class, $dataset->getRemoteCreateTime());
        $this->assertSame('1970-01-01', $dataset->getRemoteCreateTime()->format('Y-m-d'));
        $this->assertNull($dataset->getRemoteUpdateTime());
    }

    public function testMapApiDataToEntityHandlesAllFieldTypes(): void
    {
        $dataset = new Dataset();
        $apiData = [
            'id' => 'remote-456',
            'name' => 'Comprehensive Dataset',
            'description' => 'Full test coverage',
            'chunk_method' => 'sliding_window',
            'language' => 'en',
            'embedding_model' => 'bge-large-zh',
            'status' => 'processing',
            'create_time' => 1640995200000,
            'update_time' => 1642204800000,
        ];

        $this->mapper->mapApiDataToEntity($dataset, $apiData);

        $this->assertSame('remote-456', $dataset->getRemoteId());
        $this->assertSame('Comprehensive Dataset', $dataset->getName());
        $this->assertSame('Full test coverage', $dataset->getDescription());
        $this->assertSame('sliding_window', $dataset->getChunkMethod());
        $this->assertSame('en', $dataset->getLanguage());
        $this->assertSame('bge-large-zh', $dataset->getEmbeddingModel());
        $this->assertSame('processing', $dataset->getStatus());
        $this->assertNotNull($dataset->getRemoteCreateTime());
        $this->assertNotNull($dataset->getRemoteUpdateTime());
    }

    public function testMapApiDataToEntityIgnoresUnknownFields(): void
    {
        $dataset = new Dataset();
        $apiData = [
            'name' => 'Test Dataset',
            'unknown_field_1' => 'should be ignored',
            'unknown_field_2' => 12345,
            'extra_data' => ['foo' => 'bar'],
        ];

        $this->mapper->mapApiDataToEntity($dataset, $apiData);

        $this->assertSame('Test Dataset', $dataset->getName());
    }
}
