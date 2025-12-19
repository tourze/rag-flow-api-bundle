<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service\Mapper;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Service\Mapper\DocumentMapper;

/**
 * @internal
 */
#[CoversClass(DocumentMapper::class)]
final class DocumentMapperTest extends TestCase
{
    private DocumentMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new DocumentMapper();
    }

    public function testMapApiDataToEntity(): void
    {
        $document = new Document();
        $apiData = [
            'id' => 'doc-remote-123',
            'name' => 'Test Document',
            'filename' => 'test.pdf',
            'type' => 'pdf',
            'language' => 'zh',
            'size' => 1024000,
            'chunk_num' => 50,
            'status' => 'completed',
            'progress' => 0.85,
            'progress_msg' => 'Processing completed',
            'create_time' => 1640995200000,
            'update_time' => '2024-01-15 10:30:45',
        ];

        $this->mapper->mapApiDataToEntity($document, $apiData);

        $this->assertSame('doc-remote-123', $document->getRemoteId());
        $this->assertSame('Test Document', $document->getName());
        $this->assertSame('test.pdf', $document->getFilename());
        $this->assertSame('pdf', $document->getType());
        $this->assertSame('zh', $document->getLanguage());
        $this->assertSame(1024000, $document->getSize());
        $this->assertSame(50, $document->getChunkCount());
        $this->assertSame('completed', $document->getStatus()->value);
        $this->assertSame(85.0, $document->getProgress());
        $this->assertSame('Processing completed', $document->getProgressMsg());
        $this->assertInstanceOf(\DateTimeImmutable::class, $document->getRemoteCreateTime());
        $this->assertSame('2022-01-01', $document->getRemoteCreateTime()->format('Y-m-d'));
        $this->assertInstanceOf(\DateTimeImmutable::class, $document->getRemoteUpdateTime());
        $this->assertSame('2024-01-15', $document->getRemoteUpdateTime()->format('Y-m-d'));
    }

    public function test完整数据映射(): void
    {
        $document = new Document();
        $apiData = [
            'id' => 'doc-remote-123',
            'name' => 'Test Document',
            'filename' => 'test.pdf',
            'type' => 'pdf',
            'language' => 'zh',
            'size' => 1024000,
            'chunk_num' => 50,
            'status' => 'completed',
            'progress' => 0.85,
            'progress_msg' => 'Processing completed',
            'create_time' => 1640995200000,
            'update_time' => '2024-01-15 10:30:45',
        ];

        $this->mapper->mapApiDataToEntity($document, $apiData);

        $this->assertSame('doc-remote-123', $document->getRemoteId());
        $this->assertSame('Test Document', $document->getName());
        $this->assertSame('test.pdf', $document->getFilename());
        $this->assertSame('pdf', $document->getType());
        $this->assertSame('zh', $document->getLanguage());
        $this->assertSame(1024000, $document->getSize());
        $this->assertSame(50, $document->getChunkCount());
        $this->assertSame('completed', $document->getStatus()->value);
        $this->assertSame(85.0, $document->getProgress());
        $this->assertSame('Processing completed', $document->getProgressMsg());
        $this->assertInstanceOf(\DateTimeImmutable::class, $document->getRemoteCreateTime());
        $this->assertSame('2022-01-01', $document->getRemoteCreateTime()->format('Y-m-d'));
        $this->assertInstanceOf(\DateTimeImmutable::class, $document->getRemoteUpdateTime());
        $this->assertSame('2024-01-15', $document->getRemoteUpdateTime()->format('Y-m-d'));
    }

    public function test最小数据映射(): void
    {
        $document = new Document();
        $apiData = [
            'name' => 'Minimal Document',
        ];

        $this->mapper->mapApiDataToEntity($document, $apiData);

        $this->assertSame('Minimal Document', $document->getName());
        $this->assertNull($document->getRemoteId());
        $this->assertNull($document->getFilename());
        $this->assertNull($document->getType());
        $this->assertNull($document->getLanguage());
        $this->assertNull($document->getSize());
        $this->assertNull($document->getChunkCount());
    }

    public function test无效数据类型处理(): void
    {
        $document = new Document();
        $apiData = [
            'id' => 12345,
            'name' => 'Test Document',
            'filename' => ['not', 'a', 'string'],
            'type' => null,
            'language' => 123,
            'size' => 'not-a-number',
            'chunk_num' => true,
        ];

        $this->mapper->mapApiDataToEntity($document, $apiData);

        $this->assertNull($document->getRemoteId());
        $this->assertSame('Test Document', $document->getName());
        $this->assertNull($document->getFilename());
        $this->assertNull($document->getType());
        $this->assertNull($document->getLanguage());
        $this->assertNull($document->getSize());
        $this->assertNull($document->getChunkCount());
    }

    public function test时间戳转换从毫秒(): void
    {
        $document = new Document();
        $apiData = [
            'name' => 'Test Document',
            'create_time' => 1640995200000,
            'update_time' => 1642204800000,
        ];

        $this->mapper->mapApiDataToEntity($document, $apiData);

        $this->assertInstanceOf(\DateTimeImmutable::class, $document->getRemoteCreateTime());
        $this->assertSame('2022-01-01', $document->getRemoteCreateTime()->format('Y-m-d'));
        $this->assertInstanceOf(\DateTimeImmutable::class, $document->getRemoteUpdateTime());
        $this->assertSame('2022-01-15', $document->getRemoteUpdateTime()->format('Y-m-d'));
    }

    public function test时间戳转换从字符串(): void
    {
        $document = new Document();
        $apiData = [
            'name' => 'Test Document',
            'create_time' => '2024-01-15 10:30:45',
            'update_time' => '2024-01-20 15:45:30',
        ];

        $this->mapper->mapApiDataToEntity($document, $apiData);

        $this->assertInstanceOf(\DateTimeImmutable::class, $document->getRemoteCreateTime());
        $this->assertSame('2024-01-15', $document->getRemoteCreateTime()->format('Y-m-d'));
        $this->assertInstanceOf(\DateTimeImmutable::class, $document->getRemoteUpdateTime());
        $this->assertSame('2024-01-20', $document->getRemoteUpdateTime()->format('Y-m-d'));
    }

    public function test进度转换为百分比(): void
    {
        $document = new Document();
        $apiData = [
            'name' => 'Test Document',
            'progress' => 0.75,
        ];

        $this->mapper->mapApiDataToEntity($document, $apiData);

        $this->assertSame(75.0, $document->getProgress());
    }

    public function test解析器配置映射(): void
    {
        $document = new Document();
        $apiData = [
            'name' => 'Test Document',
            'parser_config' => [
                'chunk_token_num' => 100,
            ],
        ];

        $this->mapper->mapApiDataToEntity($document, $apiData);

        $this->assertSame(100, $document->getChunkCount());
    }

    public function test处理无效时间戳(): void
    {
        $document = new Document();
        $apiData = [
            'name' => 'Test Document',
            'create_time' => 'invalid-date',
            'update_time' => null,
        ];

        $this->mapper->mapApiDataToEntity($document, $apiData);

        $this->assertInstanceOf(\DateTimeImmutable::class, $document->getRemoteCreateTime());
        $this->assertSame('1970-01-01', $document->getRemoteCreateTime()->format('Y-m-d'));
        $this->assertNull($document->getRemoteUpdateTime());
    }
}
