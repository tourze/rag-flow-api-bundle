<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Entity;

use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\PHPUnitSymfonyKernelTest\DoctrineTrait;
use Tourze\PHPUnitSymfonyKernelTest\ServiceLocatorTrait;
use Tourze\RAGFlowApiBundle\Entity\ChatAssistant;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;

/**
 * @internal
 */
#[CoversClass(Dataset::class)]
class DatasetTest extends AbstractEntityTestCase
{
    use DoctrineTrait;
    use ServiceLocatorTrait;

    protected function createEntity(): Dataset
    {
        $dataset = new Dataset();
        $dataset->setName('test-dataset');

        return $dataset;
    }

    public function testCreateDataset(): void
    {
        $dataset = new Dataset();
        $dataset->setName('测试数据集');
        $dataset->setDescription('这是一个用于测试的数据集');

        $this->assertEquals('测试数据集', $dataset->getName());
        $this->assertEquals('这是一个用于测试的数据集', $dataset->getDescription());
    }

    public function testRemoteId(): void
    {
        $dataset = new Dataset();
        $dataset->setName('测试数据集');

        // 初始值为 null
        $this->assertNull($dataset->getRemoteId());

        $remoteId = 'remote-dataset-123';
        $dataset->setRemoteId($remoteId);
        $this->assertEquals($remoteId, $dataset->getRemoteId());
    }

    public function testDocumentsCollection(): void
    {
        $dataset = new Dataset();
        $dataset->setName('文档集合测试');

        // 初始状态下应该是空集合
        $this->assertCount(0, $dataset->getDocuments());

        $ragFlowInstance = new RAGFlowInstance();
        $ragFlowInstance->setName('文档集合测试实例');
        $ragFlowInstance->setApiUrl('https://test.ragflow.io');
        $ragFlowInstance->setApiKey('test-key');

        $dataset->setRagFlowInstance($ragFlowInstance);

        $document1 = new Document();
        $document1->setName('文档1.pdf');
        $document1->setDataset($dataset);

        $document2 = new Document();
        $document2->setName('文档2.docx');
        $document2->setDataset($dataset);

        // 添加文档
        $dataset->addDocument($document1);
        $dataset->addDocument($document2);

        $this->assertCount(2, $dataset->getDocuments());
        $this->assertTrue($dataset->getDocuments()->contains($document1));
        $this->assertTrue($dataset->getDocuments()->contains($document2));

        // 移除文档
        $dataset->removeDocument($document1);
        $this->assertCount(1, $dataset->getDocuments());
        $this->assertFalse($dataset->getDocuments()->contains($document1));
        $this->assertTrue($dataset->getDocuments()->contains($document2));
    }

    public function testChatAssistantsCollection(): void
    {
        $dataset = new Dataset();
        $dataset->setName('助手集合测试');

        // 初始状态下应该是空集合
        $this->assertCount(0, $dataset->getChatAssistants());

        $ragFlowInstance = new RAGFlowInstance();
        $ragFlowInstance->setName('助手集合测试实例');
        $ragFlowInstance->setApiUrl('https://test.ragflow.io');
        $ragFlowInstance->setApiKey('test-key');

        $dataset->setRagFlowInstance($ragFlowInstance);

        $assistant1 = new ChatAssistant();
        $assistant1->setName('助手1');
        $assistant1->setDataset($dataset);

        $assistant2 = new ChatAssistant();
        $assistant2->setName('助手2');
        $assistant2->setDataset($dataset);

        // 添加助手
        $dataset->addChatAssistant($assistant1);
        $dataset->addChatAssistant($assistant2);

        $this->assertCount(2, $dataset->getChatAssistants());
        $this->assertTrue($dataset->getChatAssistants()->contains($assistant1));
        $this->assertTrue($dataset->getChatAssistants()->contains($assistant2));

        // 移除助手
        $dataset->removeChatAssistant($assistant1);
        $this->assertCount(1, $dataset->getChatAssistants());
        $this->assertFalse($dataset->getChatAssistants()->contains($assistant1));
        $this->assertTrue($dataset->getChatAssistants()->contains($assistant2));
    }

    public function testChunkMethod(): void
    {
        $dataset = new Dataset();
        $dataset->setName('分块方法测试');

        // 测试分块方法
        $dataset->setChunkMethod('naive');
        $this->assertEquals('naive', $dataset->getChunkMethod());

        $dataset->setChunkMethod('manual');
        $this->assertEquals('manual', $dataset->getChunkMethod());

        $dataset->setChunkMethod('qa');
        $this->assertEquals('qa', $dataset->getChunkMethod());
    }

    public function testChunkConfiguration(): void
    {
        $dataset = new Dataset();
        $dataset->setName('分块配置测试');

        // 测试分块大小设置
        $dataset->setChunkSize(1000);
        $this->assertEquals(1000, $dataset->getChunkSize());

        // 测试设置为 null
        $dataset->setChunkSize(null);
        $this->assertNull($dataset->getChunkSize());

        // 测试语言设置
        $dataset->setLanguage('zh-CN');
        $this->assertEquals('zh-CN', $dataset->getLanguage());
    }

    public function testParsingConfiguration(): void
    {
        $dataset = new Dataset();
        $dataset->setName('解析配置测试');

        // 测试解析方法设置
        $dataset->setParserMethod('tesseract');
        $this->assertEquals('tesseract', $dataset->getParserMethod());

        // 测试设置为 null
        $dataset->setParserMethod(null);
        $this->assertNull($dataset->getParserMethod());

        // 测试相似度阈值
        $dataset->setSimilarityThreshold(0.8);
        $this->assertEquals(0.8, $dataset->getSimilarityThreshold());
    }

    public function testEmbeddingModel(): void
    {
        $dataset = new Dataset();
        $dataset->setName('嵌入模型测试');

        // 测试嵌入模型
        $dataset->setEmbeddingModel('text-embedding-ada-002');
        $this->assertEquals('text-embedding-ada-002', $dataset->getEmbeddingModel());

        $dataset->setEmbeddingModel('bge-large-zh-v1.5');
        $this->assertEquals('bge-large-zh-v1.5', $dataset->getEmbeddingModel());

        // 测试设置为 null
        $dataset->setEmbeddingModel(null);
        $this->assertNull($dataset->getEmbeddingModel());
    }

    public function testTimestamps(): void
    {
        $dataset = new Dataset();
        $dataset->setName('时间戳测试');

        $remoteCreateTime = new \DateTimeImmutable('2024-01-01 10:00:00');
        $dataset->setRemoteCreateTime($remoteCreateTime);
        $this->assertEquals($remoteCreateTime, $dataset->getRemoteCreateTime());

        $remoteUpdateTime = new \DateTimeImmutable('2024-01-01 11:00:00');
        $dataset->setRemoteUpdateTime($remoteUpdateTime);
        $this->assertEquals($remoteUpdateTime, $dataset->getRemoteUpdateTime());

        $lastSyncTime = new \DateTimeImmutable('2024-01-01 12:00:00');
        $dataset->setLastSyncTime($lastSyncTime);
        $this->assertEquals($lastSyncTime, $dataset->getLastSyncTime());
    }

    public function testLanguageAndStatus(): void
    {
        $dataset = new Dataset();
        $dataset->setName('语言和状态测试');

        // 测试语言设置
        $dataset->setLanguage('zh-CN');
        $this->assertEquals('zh-CN', $dataset->getLanguage());

        $dataset->setLanguage('en-US');
        $this->assertEquals('en-US', $dataset->getLanguage());

        // 测试状态设置
        $dataset->setStatus('active');
        $this->assertEquals('active', $dataset->getStatus());
    }

    public function testToString(): void
    {
        $dataset = new Dataset();
        $datasetName = '重要知识库';
        $dataset->setName($datasetName);

        $this->assertEquals($datasetName, (string) $dataset);
    }

    public function testComplexConfiguration(): void
    {
        $dataset = new Dataset();
        $dataset->setName('复杂配置测试');

        // 测试复杂的分块配置
        $dataset->setChunkMethod('semantic_chunking');
        $dataset->setChunkSize(1500);
        $dataset->setSimilarityThreshold(0.8);
        $dataset->setLanguage('zh-CN');

        $this->assertEquals('semantic_chunking', $dataset->getChunkMethod());
        $this->assertEquals(1500, $dataset->getChunkSize());
        $this->assertEquals(0.8, $dataset->getSimilarityThreshold());
        $this->assertEquals('zh-CN', $dataset->getLanguage());

        // 测试复杂的解析配置
        $dataset->setParserMethod('pymupdf');
        $dataset->setEmbeddingModel('text-embedding-ada-002');
        $dataset->setStatus('active');

        $this->assertEquals('pymupdf', $dataset->getParserMethod());
        $this->assertEquals('text-embedding-ada-002', $dataset->getEmbeddingModel());
        $this->assertEquals('active', $dataset->getStatus());
    }

    public function testNullableFields(): void
    {
        $dataset = new Dataset();
        $dataset->setName('可空字段测试');

        // 测试可为空的字段
        $this->assertNull($dataset->getRemoteId());
        $this->assertNull($dataset->getDescription());
        $this->assertNull($dataset->getParserMethod());
        $this->assertNull($dataset->getChunkMethod());
        $this->assertNull($dataset->getChunkSize());
        $this->assertNull($dataset->getLanguage());
        $this->assertNull($dataset->getEmbeddingModel());
        $this->assertNull($dataset->getSimilarityThreshold());
        $this->assertNull($dataset->getStatus());
        $this->assertNull($dataset->getRemoteCreateTime());
        $this->assertNull($dataset->getRemoteUpdateTime());
        $this->assertNull($dataset->getLastSyncTime());
    }

    public function testDefaultCollections(): void
    {
        $dataset = new Dataset();
        $dataset->setName('默认集合测试');

        // 测试默认的集合应该是空的但已初始化
        $this->assertInstanceOf(Collection::class, $dataset->getDocuments());
        $this->assertInstanceOf(Collection::class, $dataset->getChatAssistants());
        $this->assertCount(0, $dataset->getDocuments());
        $this->assertCount(0, $dataset->getChatAssistants());
    }

    public function testBidirectionalRelationshipWithDocuments(): void
    {
        $dataset = new Dataset();
        $dataset->setName('文档关系测试');

        $document = new Document();
        $document->setName('关系测试文档.pdf');

        // 设置双向关系
        $document->setDataset($dataset);
        $dataset->addDocument($document);

        $this->assertTrue($dataset->getDocuments()->contains($document));
        $this->assertSame($dataset, $document->getDataset());

        // 测试关系移除
        $dataset->removeDocument($document);
        $this->assertFalse($dataset->getDocuments()->contains($document));
        $this->assertNull($document->getDataset());
    }

    public function testBidirectionalRelationshipWithChatAssistants(): void
    {
        $dataset = new Dataset();
        $dataset->setName('助手关系测试');

        $assistant = new ChatAssistant();
        $assistant->setName('关系测试助手');

        // 设置双向关系
        $assistant->setDataset($dataset);
        $dataset->addChatAssistant($assistant);

        $this->assertTrue($dataset->getChatAssistants()->contains($assistant));
        $this->assertSame($dataset, $assistant->getDataset());

        // 测试关系移除
        $dataset->removeChatAssistant($assistant);
        $this->assertFalse($dataset->getChatAssistants()->contains($assistant));
        $this->assertNull($assistant->getDataset());
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'name' => ['name', 'Test Dataset'];
        yield 'remoteId' => ['remoteId', 'remote-dataset-123'];
        yield 'description' => ['description', 'Test description'];
        yield 'parserMethod' => ['parserMethod', 'tesseract'];
        yield 'chunkMethod' => ['chunkMethod', 'naive'];
        yield 'chunkSize' => ['chunkSize', 1000];
        yield 'language' => ['language', 'zh-CN'];
        yield 'embeddingModel' => ['embeddingModel', 'text-embedding-ada-002'];
        yield 'similarityThreshold' => ['similarityThreshold', 0.8];
        yield 'status' => ['status', 'active'];
    }
}
