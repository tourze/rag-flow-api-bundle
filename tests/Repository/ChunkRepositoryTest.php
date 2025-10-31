<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\RAGFlowApiBundle\Entity\Chunk;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Repository\ChunkRepository;

/**
 * @internal
 */
#[CoversClass(ChunkRepository::class)]
#[RunTestsInSeparateProcesses]
#[AsRepository(entityClass: Chunk::class)]
class ChunkRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // Repository tests don't need additional setup
    }

    protected function getRepository(): ChunkRepository
    {
        /** @var ChunkRepository */
        return self::getService(ChunkRepository::class);
    }

    protected function createNewEntity(): object
    {
        $ragFlowInstance = $this->createRAGFlowInstance();
        $dataset = $this->createDataset($ragFlowInstance, '测试数据集');
        $document = new Document();
        $document->setName('测试文档.pdf');
        $document->setDataset($dataset);
        $this->persistAndFlush($document);

        $chunk = new Chunk();
        $chunk->setRemoteId('test-chunk-' . uniqid());
        $chunk->setDocument($document);
        $chunk->setContent('测试文档块内容');
        $chunk->setPosition(1);

        return $chunk;
    }

    /**
     * 创建测试RAGFlow实例
     */
    private function createRAGFlowInstance(): RAGFlowInstance
    {
        $ragFlowInstance = new RAGFlowInstance();
        $ragFlowInstance->setName('测试实例');
        $ragFlowInstance->setApiUrl('https://example.com/api');
        $ragFlowInstance->setApiKey('test-api-key');
        $persistedInstance = $this->persistAndFlush($ragFlowInstance);

        $this->assertInstanceOf(RAGFlowInstance::class, $persistedInstance);
        /** @var RAGFlowInstance $persistedInstance */

        return $persistedInstance;
    }

    /**
     * 创建测试数据集
     */
    private function createDataset(RAGFlowInstance $ragFlowInstance, string $name): Dataset
    {
        $dataset = new Dataset();
        $dataset->setName($name);
        $dataset->setDescription("用于{$name}测试的数据集");
        $dataset->setRagFlowInstance($ragFlowInstance);
        $persistedDataset = $this->persistAndFlush($dataset);

        $this->assertInstanceOf(Dataset::class, $persistedDataset);
        /** @var Dataset $persistedDataset */

        return $persistedDataset;
    }

    public function testRepositoryCreation(): void
    {
        $this->assertInstanceOf(ChunkRepository::class, $this->getRepository());
    }

    public function testFindByRemoteId(): void
    {
        // 创建测试数据集
        $ragFlowInstance = $this->createRAGFlowInstance();
        $dataset = $this->createDataset($ragFlowInstance, '文档块测试数据集');

        // 创建测试文档
        $document = new Document();
        $document->setName('测试文档.pdf');
        $document->setDataset($dataset);
        $persistedDocument = $this->persistAndFlush($document);
        $this->assertInstanceOf(Document::class, $persistedDocument);

        // 创建测试文档块
        $chunk = new Chunk();
        $chunk->setRemoteId('remote-chunk-456');
        $chunk->setDocument($persistedDocument);
        $chunk->setContent('这是测试文档块的内容');
        $persistedChunk = $this->persistAndFlush($chunk);
        $this->assertInstanceOf(Chunk::class, $persistedChunk);

        // 测试通过远程ID查找
        $foundChunk = $this->getRepository()->findByRemoteId('remote-chunk-456');
        $this->assertNotNull($foundChunk);
        $this->assertEquals($persistedChunk->getId(), $foundChunk->getId());
        $this->assertEquals('remote-chunk-456', $foundChunk->getRemoteId());

        // 测试查找不存在的远程ID
        $notFound = $this->getRepository()->findByRemoteId('non-existent-remote-id');
        $this->assertNull($notFound);
    }

    public function testFindByDocument(): void
    {
        // 创建测试数据集
        $dataset = $this->createDataset($this->createRAGFlowInstance(), '文档查找测试数据集');

        // 创建测试文档
        $document = new Document();
        $document->setName('测试文档.pdf');
        $document->setDataset($dataset);
        $persistedDocument = $this->persistAndFlush($document);
        $this->assertInstanceOf(Document::class, $persistedDocument);

        // 为文档创建多个文档块
        $chunk1 = new Chunk();
        $chunk1->setRemoteId('remote-chunk-1');
        $chunk1->setDocument($persistedDocument);
        $chunk1->setContent('第一个文档块');
        $chunk1->setPosition(1);
        $this->persistAndFlush($chunk1);

        $chunk2 = new Chunk();
        $chunk2->setRemoteId('remote-chunk-2');
        $chunk2->setDocument($persistedDocument);
        $chunk2->setContent('第二个文档块');
        $chunk2->setPosition(2);
        $this->persistAndFlush($chunk2);

        $chunk3 = new Chunk();
        $chunk3->setRemoteId('remote-chunk-3');
        $chunk3->setDocument($persistedDocument);
        $chunk3->setContent('第三个文档块');
        $chunk3->setPosition(3);
        $this->persistAndFlush($chunk3);

        // 测试按文档查找文档块
        $chunks = $this->getRepository()->findByDocument($persistedDocument);
        $this->assertCount(3, $chunks);

        // 验证按position排序
        $positions = array_map(static fn (Chunk $chunk): ?int => $chunk->getPosition(), $chunks);
        $this->assertEquals([1, 2, 3], $positions);
    }

    public function testFindByContent(): void
    {
        // 创建测试数据集
        $dataset = $this->createDataset($this->createRAGFlowInstance(), '内容搜索测试数据集');

        // 创建测试文档
        $document = new Document();
        $document->setName('测试文档.pdf');
        $document->setDataset($dataset);
        $persistedDocument = $this->persistAndFlush($document);
        $this->assertInstanceOf(Document::class, $persistedDocument);

        // 创建包含特定关键词的文档块
        $chunk1 = new Chunk();
        $chunk1->setRemoteId('remote-chunk-1');
        $chunk1->setDocument($persistedDocument);
        $chunk1->setContent('这是包含人工智能关键词的第一个文档块');
        $chunk1->setSimilarityScore(0.9);
        $this->persistAndFlush($chunk1);

        $chunk2 = new Chunk();
        $chunk2->setRemoteId('remote-chunk-2');
        $chunk2->setDocument($persistedDocument);
        $chunk2->setContent('机器学习相关的第二个文档块');
        $chunk2->setSimilarityScore(0.7);
        $this->persistAndFlush($chunk2);

        $chunk3 = new Chunk();
        $chunk3->setRemoteId('remote-chunk-3');
        $chunk3->setDocument($persistedDocument);
        $chunk3->setContent('深度学习文档内容');
        $chunk3->setSimilarityScore(0.95);
        $this->persistAndFlush($chunk3);

        // 测试内容搜索
        $aiChunks = $this->getRepository()->findByContent('人工智能');
        $this->assertGreaterThanOrEqual(1, count($aiChunks));

        foreach ($aiChunks as $chunk) {
            $this->assertStringContainsString('人工智能', $chunk->getContent());
        }

        $learningChunks = $this->getRepository()->findByContent('学习');
        $this->assertGreaterThanOrEqual(2, count($learningChunks));

        foreach ($learningChunks as $chunk) {
            $this->assertStringContainsString('学习', $chunk->getContent());
        }

        // 验证按相似度降序排列
        $learningScores = array_map(static fn (Chunk $chunk): ?float => $chunk->getSimilarityScore(), $learningChunks);
        $sortedScores = $learningScores;
        rsort($sortedScores);
        $this->assertEquals($sortedScores, $learningScores);
    }

    public function testFindPendingSync(): void
    {
        // 创建测试数据集
        $dataset = $this->createDataset($this->createRAGFlowInstance(), '同步测试数据集');

        // 创建测试文档
        $document = new Document();
        $document->setName('测试文档.pdf');
        $document->setDataset($dataset);
        $persistedDocument = $this->persistAndFlush($document);
        $this->assertInstanceOf(Document::class, $persistedDocument);

        $since = new \DateTimeImmutable('2024-01-01 00:00:00');

        // 创建未同步的文档块
        $unsyncedChunk = new Chunk();
        $unsyncedChunk->setRemoteId('remote-chunk-unsynced');
        $unsyncedChunk->setDocument($persistedDocument);
        $unsyncedChunk->setContent('未同步的文档块');
        // lastSyncTime 为 null
        $this->persistAndFlush($unsyncedChunk);

        // 创建同步时间较早的文档块
        $oldSyncChunk = new Chunk();
        $oldSyncChunk->setRemoteId('remote-chunk-old-sync');
        $oldSyncChunk->setDocument($persistedDocument);
        $oldSyncChunk->setContent('同步时间较早的文档块');
        $oldSyncChunk->setLastSyncTime(new \DateTimeImmutable('2023-12-01 00:00:00'));
        $this->persistAndFlush($oldSyncChunk);

        // 创建最近同步的文档块
        $recentSyncChunk = new Chunk();
        $recentSyncChunk->setRemoteId('remote-chunk-recent-sync');
        $recentSyncChunk->setDocument($persistedDocument);
        $recentSyncChunk->setContent('最近同步的文档块');
        $recentSyncChunk->setLastSyncTime(new \DateTimeImmutable('2024-01-15 00:00:00'));
        $this->persistAndFlush($recentSyncChunk);

        // 测试查找需要同步的文档块
        $pendingSyncChunks = $this->getRepository()->findPendingSync($since);
        $this->assertGreaterThanOrEqual(2, count($pendingSyncChunks));

        foreach ($pendingSyncChunks as $chunk) {
            $lastSyncTime = $chunk->getLastSyncTime();
            $this->assertTrue(null === $lastSyncTime || $lastSyncTime < $since);
        }
    }

    public function testFindWithFilters(): void
    {
        // 创建测试数据集
        $dataset1 = $this->createDataset($this->createRAGFlowInstance(), '筛选测试数据集1');
        $dataset2 = $this->createDataset($this->createRAGFlowInstance(), '筛选测试数据集2');

        // 创建测试文档
        $document1 = new Document();
        $document1->setName('测试文档1.pdf');
        $document1->setDataset($dataset1);
        $persistedDocument1 = $this->persistAndFlush($document1);
        $this->assertInstanceOf(Document::class, $persistedDocument1);

        $document2 = new Document();
        $document2->setName('测试文档2.pdf');
        $document2->setDataset($dataset2);
        $persistedDocument2 = $this->persistAndFlush($document2);
        $this->assertInstanceOf(Document::class, $persistedDocument2);

        // 创建不同类型的文档块
        $chunk1 = new Chunk();
        $chunk1->setRemoteId('remote-chunk-filter-1');
        $chunk1->setDocument($persistedDocument1);
        $chunk1->setContent('人工智能相关内容');
        $chunk1->setSimilarityScore(0.8);
        $chunk1->setPosition(1);
        $this->persistAndFlush($chunk1);

        $chunk2 = new Chunk();
        $chunk2->setRemoteId('remote-chunk-filter-2');
        $chunk2->setDocument($persistedDocument1);
        $chunk2->setContent('机器学习相关内容');
        $chunk2->setSimilarityScore(0.6);
        $chunk2->setPosition(2);
        $this->persistAndFlush($chunk2);

        $chunk3 = new Chunk();
        $chunk3->setRemoteId('remote-chunk-filter-3');
        $chunk3->setDocument($persistedDocument2);
        $chunk3->setContent('深度学习内容');
        $chunk3->setSimilarityScore(0.9);
        $chunk3->setPosition(1);
        $this->persistAndFlush($chunk3);

        // 测试内容筛选
        $aiFilters = ['content' => '人工智能'];
        $aiResult = $this->getRepository()->findWithFilters($aiFilters);
        $this->assertGreaterThanOrEqual(1, $aiResult['total']);

        foreach ($aiResult['items'] as $chunk) {
            $this->assertStringContainsString('人工智能', $chunk->getContent());
        }

        // 测试文档筛选
        $documentFilters = ['document_id' => $persistedDocument1->getId()];
        $documentResult = $this->getRepository()->findWithFilters($documentFilters);
        $this->assertEquals(2, $documentResult['total']);

        foreach ($documentResult['items'] as $chunk) {
            $document = $chunk->getDocument();
            $this->assertNotNull($document);
            $this->assertEquals($persistedDocument1->getId(), $document->getId());
        }

        // 测试数据集筛选
        $datasetFilters = ['dataset_id' => $dataset2->getId()];
        $datasetResult = $this->getRepository()->findWithFilters($datasetFilters);
        $this->assertEquals(1, $datasetResult['total']);

        foreach ($datasetResult['items'] as $chunk) {
            $document = $chunk->getDocument();
            $this->assertNotNull($document);
            $dataset = $document->getDataset();
            $this->assertNotNull($dataset);
            $this->assertEquals($dataset2->getId(), $dataset->getId());
        }

        // 测试相似度筛选
        $similarityFilters = ['min_similarity' => 0.7];
        $similarityResult = $this->getRepository()->findWithFilters($similarityFilters);
        $this->assertGreaterThanOrEqual(2, $similarityResult['total']);

        foreach ($similarityResult['items'] as $chunk) {
            $this->assertGreaterThanOrEqual(0.7, $chunk->getSimilarityScore());
        }

        // 测试分页
        $paginationResult = $this->getRepository()->findWithFilters([], 1, 2);
        $this->assertGreaterThanOrEqual(2, $paginationResult['total']);
        $this->assertCount(2, $paginationResult['items']);
    }

    public function testCountByDocument(): void
    {
        // 创建测试数据集
        $dataset = $this->createDataset($this->createRAGFlowInstance(), '计数测试数据集');

        // 创建测试文档
        $document = new Document();
        $document->setName('测试文档.pdf');
        $document->setDataset($dataset);
        $persistedDocument = $this->persistAndFlush($document);
        $this->assertInstanceOf(Document::class, $persistedDocument);

        $initialCount = $this->getRepository()->countByDocument($persistedDocument);

        // 为文档创建多个文档块
        for ($i = 1; $i <= 5; ++$i) {
            $chunk = new Chunk();
            $chunk->setRemoteId("remote-chunk-count-{$i}");
            $chunk->setDocument($persistedDocument);
            $chunk->setContent("文档块{$i}的内容");
            $chunk->setPosition($i);
            $this->persistAndFlush($chunk);
        }

        $finalCount = $this->getRepository()->countByDocument($persistedDocument);
        $this->assertEquals($initialCount + 5, $finalCount);
    }

    public function testSave(): void
    {
        // 创建测试数据集
        $dataset = $this->createDataset($this->createRAGFlowInstance(), '保存测试数据集');

        // 创建测试文档
        $document = new Document();
        $document->setName('测试文档.pdf');
        $document->setDataset($dataset);
        $persistedDocument = $this->persistAndFlush($document);
        $this->assertInstanceOf(Document::class, $persistedDocument);

        // 创建新的文档块并保存
        $chunk = new Chunk();
        $chunk->setRemoteId('remote-chunk-save-test');
        $chunk->setDocument($persistedDocument);
        $chunk->setContent('测试保存的文档块');
        $chunk->setSimilarityScore(0.85);
        $chunk->setPosition(1);

        // 测试保存
        $this->getRepository()->save($chunk);

        // 验证文档块已保存到数据库
        $savedChunk = $this->getEntityManagerInstance()->find(Chunk::class, $chunk->getId());
        $this->assertNotNull($savedChunk);
        $this->assertEquals('remote-chunk-save-test', $savedChunk->getRemoteId());
        $this->assertEquals('测试保存的文档块', $savedChunk->getContent());
        $this->assertEquals(0.85, $savedChunk->getSimilarityScore());
    }

    public function testRemove(): void
    {
        // 创建测试数据集
        $dataset = $this->createDataset($this->createRAGFlowInstance(), '删除测试数据集');

        // 创建测试文档
        $document = new Document();
        $document->setName('测试文档.pdf');
        $document->setDataset($dataset);
        $persistedDocument = $this->persistAndFlush($document);
        $this->assertInstanceOf(Document::class, $persistedDocument);

        // 创建要删除的文档块
        $chunk = new Chunk();
        $chunk->setRemoteId('remote-chunk-remove-test');
        $chunk->setDocument($persistedDocument);
        $chunk->setContent('测试删除的文档块');
        $this->persistAndFlush($chunk);

        $chunkId = $chunk->getId();
        $this->assertNotNull($chunkId);

        // 验证文档块存在
        $existingChunk = $this->getEntityManagerInstance()->find(Chunk::class, $chunkId);
        $this->assertNotNull($existingChunk);

        // 测试删除
        $this->getRepository()->remove($chunk);

        // 验证文档块已从数据库删除
        $deletedChunk = $this->getEntityManagerInstance()->find(Chunk::class, $chunkId);
        $this->assertNull($deletedChunk);
    }
}
