<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\PHPUnitSymfonyKernelTest\DoctrineTrait;
use Tourze\PHPUnitSymfonyKernelTest\ServiceLocatorTrait;
use Tourze\RAGFlowApiBundle\Entity\Chunk;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;

/**
 * @internal
 */
#[CoversClass(Chunk::class)]
class ChunkTest extends AbstractEntityTestCase
{
    use DoctrineTrait;
    use ServiceLocatorTrait;

    protected function createEntity(): Chunk
    {
        $chunk = new Chunk();
        $chunk->setRemoteId('test-chunk');

        return $chunk;
    }

    public function testCreateChunk(): void
    {
        $chunk = new Chunk();
        $chunk->setRemoteId('remote-chunk-001');
        $chunk->setContent('这是一段测试文本内容');
        $chunk->setSize(20); // 使用 size 代替 tokenCount
        $chunk->setSimilarityScore(0.85);

        $this->assertEquals('这是一段测试文本内容', $chunk->getContent());
        $this->assertEquals(20, $chunk->getSize());
        $this->assertEquals(0.85, $chunk->getSimilarityScore());
    }

    public function testDocumentRelation(): void
    {
        $dataset = new Dataset();
        $dataset->setName('测试数据集');
        $dataset->setDescription('测试数据集');

        $document = new Document();
        $document->setName('测试文档');
        $document->setDataset($dataset);

        $chunk = new Chunk();
        $chunk->setContent('文档分块内容');
        $chunk->setDocument($document);

        $this->assertSame($document, $chunk->getDocument());
    }

    public function testRemoteId(): void
    {
        $chunk = new Chunk();
        $chunk->setRemoteId('initial-remote-id'); // remoteId 是必需字段
        $chunk->setContent('测试内容');

        $this->assertEquals('initial-remote-id', $chunk->getRemoteId());

        $remoteId = 'remote-chunk-123';
        $chunk->setRemoteId($remoteId);
        $this->assertEquals($remoteId, $chunk->getRemoteId());
    }

    public function testPositionAndMetadata(): void
    {
        $chunk = new Chunk();
        $chunk->setRemoteId('position-test-chunk');
        $chunk->setContent('测试内容');

        // 测试位置信息
        $chunk->setPosition(5);
        $this->assertEquals(5, $chunk->getPosition());

        // 测试页码
        $chunk->setPageNumber(10);
        $this->assertEquals(10, $chunk->getPageNumber());

        // 测试位置信息数组（代替起始/结束位置）
        $positions = ['start' => 100, 'end' => 200];
        $chunk->setPositions($positions);
        $this->assertEquals($positions, $chunk->getPositions());
    }

    public function testSizeAndSimilarity(): void
    {
        $chunk = new Chunk();
        $chunk->setRemoteId('size-test-chunk');
        $chunk->setContent('测试内容');

        // 测试块大小（字符数）
        $chunk->setSize(50);
        $this->assertEquals(50, $chunk->getSize());

        // 测试相似度分数
        $chunk->setSimilarityScore(0.92);
        $this->assertEquals(0.92, $chunk->getSimilarityScore());
    }

    public function testContentWithWeight(): void
    {
        $chunk = new Chunk();
        $chunk->setRemoteId('weight-test-chunk');
        $chunk->setContent('测试内容');

        // 测试带权重的内容
        $weightedContent = '测试内容 [0.8]';
        $chunk->setContentWithWeight($weightedContent);
        $this->assertEquals($weightedContent, $chunk->getContentWithWeight());

        // 测试设置为 null
        $chunk->setContentWithWeight(null);
        $this->assertNull($chunk->getContentWithWeight());
    }

    public function testMetadata(): void
    {
        $chunk = new Chunk();
        $chunk->setRemoteId('metadata-test-chunk');
        $chunk->setContent('测试内容');

        // 测试元数据（包含关键词）
        $metadata = [
            'keywords' => ['AI', '机器学习', '自然语言处理'],
            'language' => 'zh-CN',
            'confidence' => 0.95,
        ];
        $chunk->setMetadata($metadata);
        $this->assertEquals($metadata, $chunk->getMetadata());

        // 测试设置为 null
        $chunk->setMetadata(null);
        $this->assertNull($chunk->getMetadata());
    }

    public function testTimestamps(): void
    {
        $chunk = new Chunk();
        $chunk->setRemoteId('timestamp-test-chunk');
        $chunk->setContent('测试内容');

        $remoteCreateTime = new \DateTimeImmutable('2024-01-01 10:00:00');
        $chunk->setRemoteCreateTime($remoteCreateTime);
        $this->assertEquals($remoteCreateTime, $chunk->getRemoteCreateTime());

        $remoteUpdateTime = new \DateTimeImmutable('2024-01-01 11:00:00');
        $chunk->setRemoteUpdateTime($remoteUpdateTime);
        $this->assertEquals($remoteUpdateTime, $chunk->getRemoteUpdateTime());

        $lastSyncTime = new \DateTimeImmutable('2024-01-01 12:00:00');
        $chunk->setLastSyncTime($lastSyncTime);
        $this->assertEquals($lastSyncTime, $chunk->getLastSyncTime());
    }

    public function testSearchMetadata(): void
    {
        $chunk = new Chunk();
        $chunk->setRemoteId('search-metadata-chunk');
        $chunk->setContent('测试内容');

        // 测试搜索元数据
        $metadata = [
            'source' => 'document.pdf',
            'page' => 5,
            'section' => '第三章',
            'tags' => ['重要', '概念'],
        ];
        $chunk->setMetadata($metadata);
        $this->assertEquals($metadata, $chunk->getMetadata());

        // 测试设置为 null
        $chunk->setMetadata(null);
        $this->assertNull($chunk->getMetadata());
    }

    public function testToString(): void
    {
        $chunk = new Chunk();
        $chunk->setRemoteId('tostring-test-chunk');
        $content = '这是用于toString测试的内容';
        $chunk->setContent($content);

        $this->assertEquals($content, (string) $chunk);
    }

    public function testContentValidation(): void
    {
        $chunk = new Chunk();
        $chunk->setRemoteId('validation-test-chunk');

        // 测试长内容
        $longContent = str_repeat('这是测试内容。', 1000);
        $chunk->setContent($longContent);
        $this->assertEquals($longContent, $chunk->getContent());

        // 测试空内容
        $chunk->setContent('');
        $this->assertEquals('', $chunk->getContent());
    }

    public function testNullableFields(): void
    {
        $chunk = new Chunk();
        $chunk->setRemoteId('nullable-test-chunk'); // remoteId 是必需字段
        $chunk->setContent('测试内容'); // content 是必需字段

        // 测试可为空的字段
        $this->assertNull($chunk->getDocument());
        $this->assertNull($chunk->getPosition());
        $this->assertNull($chunk->getPageNumber());
        $this->assertNull($chunk->getSize());
        $this->assertNull($chunk->getSimilarityScore());
        $this->assertNull($chunk->getMetadata());
        $this->assertNull($chunk->getRemoteCreateTime());
        $this->assertNull($chunk->getRemoteUpdateTime());
        $this->assertNull($chunk->getLastSyncTime());
        $this->assertNull($chunk->getContentWithWeight());
        $this->assertNull($chunk->getPositions());
    }

    public function testComplexMetadataAndPositions(): void
    {
        $chunk = new Chunk();
        $chunk->setRemoteId('complex-test-chunk');
        $chunk->setContent('复杂数据结构测试');

        // 测试复杂的位置信息
        $complexPositions = [
            ['start' => 0, 'end' => 100, 'page' => 1],
            ['start' => 100, 'end' => 200, 'page' => 1],
            ['start' => 200, 'end' => 300, 'page' => 2],
        ];
        $chunk->setPositions($complexPositions);
        $this->assertEquals($complexPositions, $chunk->getPositions());

        // 测试复杂的元数据（包含关键词和嵌入信息）
        $complexMetadata = [
            'extraction_method' => 'sliding_window',
            'chunk_overlap' => 20,
            'language' => 'zh-CN',
            'confidence' => 0.95,
            'entities' => ['OpenAI', 'GPT', 'ChatGPT'],
            'keywords' => ['人工智能', 'deep learning', 'neural networks', 'transformer', 'BERT'],
            'embedding' => array_fill(0, 768, 0.5), // 模拟768维向量
            'topics' => [
                'category' => 'technology',
                'subcategory' => 'artificial_intelligence',
            ],
        ];
        $chunk->setMetadata($complexMetadata);
        $this->assertEquals($complexMetadata, $chunk->getMetadata());

        // 验证元数据中的关键词
        $metadata = $chunk->getMetadata();
        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('keywords', $metadata);
        $this->assertContains('人工智能', $metadata['keywords']);
        $this->assertContains('BERT', $metadata['keywords']);
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'remoteId' => ['remoteId', 'remote-chunk-123'];
        yield 'content' => ['content', 'Test content'];
        yield 'position' => ['position', 5];
        yield 'size' => ['size', 100];
        yield 'similarityScore' => ['similarityScore', 0.85];
        yield 'contentWithWeight' => ['contentWithWeight', 'Test [0.9]'];
        yield 'pageNumber' => ['pageNumber', 10];
        yield 'startPos' => ['startPos', 0];
        yield 'endPos' => ['endPos', 100];
        yield 'tokenCount' => ['tokenCount', 50];
    }
}
