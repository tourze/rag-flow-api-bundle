<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\PHPUnitSymfonyKernelTest\DoctrineTrait;
use Tourze\PHPUnitSymfonyKernelTest\ServiceLocatorTrait;
use Tourze\RAGFlowApiBundle\Entity\VirtualChunk;

/**
 * @internal
 */
#[CoversClass(VirtualChunk::class)]
class VirtualChunkTest extends AbstractEntityTestCase
{
    use DoctrineTrait;
    use ServiceLocatorTrait;

    protected function createEntity(): VirtualChunk
    {
        $chunk = new VirtualChunk();
        $chunk->setId('test-chunk-id');
        $chunk->setTitle('test-chunk');

        return $chunk;
    }

    public function testCreateVirtualChunk(): void
    {
        $chunk = new VirtualChunk();
        $chunk->setId('chunk-123');
        $chunk->setDatasetId('dataset-456');
        $chunk->setDocumentId('doc-789');
        $chunk->setTitle('测试标题');
        $chunk->setContent('这是测试内容');
        $chunk->setSimilarityScore(0.85);

        $this->assertEquals('chunk-123', $chunk->getId());
        $this->assertEquals('dataset-456', $chunk->getDatasetId());
        $this->assertEquals('doc-789', $chunk->getDocumentId());
        $this->assertEquals('测试标题', $chunk->getTitle());
        $this->assertEquals('这是测试内容', $chunk->getContent());
        $this->assertEquals(0.85, $chunk->getSimilarityScore());
    }

    public function testIdGetterAndSetter(): void
    {
        $chunk = new VirtualChunk();

        $this->assertNull($chunk->getId());

        $chunk->setId('chunk-id-123');
        $this->assertEquals('chunk-id-123', $chunk->getId());
    }

    public function testDatasetIdGetterAndSetter(): void
    {
        $chunk = new VirtualChunk();

        $this->assertNull($chunk->getDatasetId());

        $chunk->setDatasetId('dataset-123');
        $this->assertEquals('dataset-123', $chunk->getDatasetId());
    }

    public function testDocumentIdGetterAndSetter(): void
    {
        $chunk = new VirtualChunk();

        $this->assertNull($chunk->getDocumentId());

        $chunk->setDocumentId('doc-456');
        $this->assertEquals('doc-456', $chunk->getDocumentId());
    }

    public function testContentGetterAndSetter(): void
    {
        $chunk = new VirtualChunk();

        $this->assertNull($chunk->getContent());

        $content = 'This is the content of the chunk.';
        $chunk->setContent($content);
        $this->assertEquals($content, $chunk->getContent());
    }

    public function testTitleGetterAndSetter(): void
    {
        $chunk = new VirtualChunk();

        $this->assertNull($chunk->getTitle());

        $chunk->setTitle('Chunk Title');
        $this->assertEquals('Chunk Title', $chunk->getTitle());
    }

    public function testKeywordsGetterAndSetter(): void
    {
        $chunk = new VirtualChunk();

        $this->assertNull($chunk->getKeywords());

        $keywords = 'keyword1, keyword2, keyword3';
        $chunk->setKeywords($keywords);
        $this->assertEquals($keywords, $chunk->getKeywords());
    }

    public function testSimilarityScoreGetterAndSetter(): void
    {
        $chunk = new VirtualChunk();

        $this->assertNull($chunk->getSimilarityScore());

        $chunk->setSimilarityScore(0.92);
        $this->assertEquals(0.92, $chunk->getSimilarityScore());
    }

    public function testPositionGetterAndSetter(): void
    {
        $chunk = new VirtualChunk();

        $this->assertNull($chunk->getPosition());

        $chunk->setPosition(5);
        $this->assertEquals(5, $chunk->getPosition());
    }

    public function testLengthGetterAndSetter(): void
    {
        $chunk = new VirtualChunk();

        $this->assertNull($chunk->getLength());

        $chunk->setLength(1024);
        $this->assertEquals(1024, $chunk->getLength());
    }

    public function testStatusGetterAndSetter(): void
    {
        $chunk = new VirtualChunk();

        $this->assertNull($chunk->getStatus());

        $chunk->setStatus('active');
        $this->assertEquals('active', $chunk->getStatus());
    }

    public function testLanguageGetterAndSetter(): void
    {
        $chunk = new VirtualChunk();

        $this->assertNull($chunk->getLanguage());

        $chunk->setLanguage('zh-CN');
        $this->assertEquals('zh-CN', $chunk->getLanguage());
    }

    public function testMetadataGetterAndSetter(): void
    {
        $chunk = new VirtualChunk();

        $this->assertNull($chunk->getMetadata());

        $metadata = [
            'source' => 'document.pdf',
            'page' => 5,
            'author' => 'John Doe',
        ];
        $chunk->setMetadata($metadata);
        $this->assertEquals($metadata, $chunk->getMetadata());

        $chunk->setMetadata(null);
        $this->assertNull($chunk->getMetadata());
    }

    public function testGetNameMethod(): void
    {
        $chunk = new VirtualChunk();

        // 无 title 和 id 时
        $this->assertEquals('Unknown', $chunk->getName());

        // 设置 title 后
        $chunk->setTitle('Test Title');
        $this->assertEquals('Test Title', $chunk->getName());

        // title 为 null 但有 id
        $chunk2 = new VirtualChunk();
        $chunk2->setId('chunk-999');
        $this->assertEquals('chunk-999', $chunk2->getName());
    }

    public function testIsAccessibleMethod(): void
    {
        $chunk = new VirtualChunk();

        $this->assertTrue($chunk->isAccessible());
    }

    public function testToStringWithTitle(): void
    {
        $chunk = new VirtualChunk();
        $chunk->setTitle('测试文本块标题');

        $this->assertEquals('测试文本块标题', (string) $chunk);
    }

    public function testToStringWithoutTitleButWithId(): void
    {
        $chunk = new VirtualChunk();
        $chunk->setId('chunk-id-789');

        $this->assertEquals('chunk-id-789', (string) $chunk);
    }

    public function testToStringWithoutTitleAndId(): void
    {
        $chunk = new VirtualChunk();

        $this->assertEquals('(new)', (string) $chunk);
    }

    public function testNullableProperties(): void
    {
        $chunk = new VirtualChunk();

        // 所有属性初始值都应为 null
        $this->assertNull($chunk->getId());
        $this->assertNull($chunk->getDatasetId());
        $this->assertNull($chunk->getDocumentId());
        $this->assertNull($chunk->getContent());
        $this->assertNull($chunk->getTitle());
        $this->assertNull($chunk->getKeywords());
        $this->assertNull($chunk->getSimilarityScore());
        $this->assertNull($chunk->getPosition());
        $this->assertNull($chunk->getLength());
        $this->assertNull($chunk->getStatus());
        $this->assertNull($chunk->getLanguage());
        $this->assertNull($chunk->getMetadata());
    }

    /**
     * @param mixed $value
     */
    #[DataProvider('propertiesProvider')]
    public function testPropertyGettersAndSetters(string $property, $value): void
    {
        $chunk = new VirtualChunk();

        // 根据属性名直接调用对应的 getter 和 setter
        match ($property) {
            'id' => $this->assertPropertyGetterSetter($chunk, $value, fn ($e, $v) => $e->setId($v), fn ($e) => $e->getId()),
            'datasetId' => $this->assertPropertyGetterSetter($chunk, $value, fn ($e, $v) => $e->setDatasetId($v), fn ($e) => $e->getDatasetId()),
            'documentId' => $this->assertPropertyGetterSetter($chunk, $value, fn ($e, $v) => $e->setDocumentId($v), fn ($e) => $e->getDocumentId()),
            'content' => $this->assertPropertyGetterSetter($chunk, $value, fn ($e, $v) => $e->setContent($v), fn ($e) => $e->getContent()),
            'title' => $this->assertPropertyGetterSetter($chunk, $value, fn ($e, $v) => $e->setTitle($v), fn ($e) => $e->getTitle()),
            'keywords' => $this->assertPropertyGetterSetter($chunk, $value, fn ($e, $v) => $e->setKeywords($v), fn ($e) => $e->getKeywords()),
            'similarityScore' => $this->assertPropertyGetterSetter($chunk, $value, fn ($e, $v) => $e->setSimilarityScore($v), fn ($e) => $e->getSimilarityScore()),
            'position' => $this->assertPropertyGetterSetter($chunk, $value, fn ($e, $v) => $e->setPosition($v), fn ($e) => $e->getPosition()),
            'length' => $this->assertPropertyGetterSetter($chunk, $value, fn ($e, $v) => $e->setLength($v), fn ($e) => $e->getLength()),
            'status' => $this->assertPropertyGetterSetter($chunk, $value, fn ($e, $v) => $e->setStatus($v), fn ($e) => $e->getStatus()),
            'language' => $this->assertPropertyGetterSetter($chunk, $value, fn ($e, $v) => $e->setLanguage($v), fn ($e) => $e->getLanguage()),
            'metadata' => $this->assertPropertyGetterSetter($chunk, $value, fn ($e, $v) => $e->setMetadata($v), fn ($e) => $e->getMetadata()),
            default => self::fail("Unknown property: {$property}"),
        };
    }

    /**
     * @param callable(VirtualChunk, mixed): void $setter
     * @param callable(VirtualChunk): mixed       $getter
     * @param mixed                               $value
     */
    private function assertPropertyGetterSetter(VirtualChunk $entity, $value, callable $setter, callable $getter): void
    {
        $setter($entity, $value);
        $this->assertEquals($value, $getter($entity));
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'id' => ['id', 'chunk-123'];
        yield 'datasetId' => ['datasetId', 'dataset-456'];
        yield 'documentId' => ['documentId', 'doc-789'];
        yield 'content' => ['content', 'This is test content'];
        yield 'title' => ['title', 'Test Title'];
        yield 'keywords' => ['keywords', 'keyword1, keyword2'];
        yield 'similarityScore' => ['similarityScore', 0.85];
        yield 'position' => ['position', 10];
        yield 'length' => ['length', 2048];
        yield 'status' => ['status', 'active'];
        yield 'language' => ['language', 'en'];
        yield 'metadata' => ['metadata', ['key' => 'value', 'nested' => ['data' => 'test']]];
    }
}
