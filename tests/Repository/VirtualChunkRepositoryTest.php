<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\RAGFlowApiBundle\Entity\VirtualChunk;
use Tourze\RAGFlowApiBundle\Repository\VirtualChunkRepository;

/**
 * @internal
 */
#[CoversClass(VirtualChunkRepository::class)]
#[RunTestsInSeparateProcesses]
class VirtualChunkRepositoryTest extends AbstractRepositoryTestCase
{
    private VirtualChunkRepository $repository;

    protected function createNewEntity(): object
    {
        // 创建VirtualChunk实体
        $chunk = new VirtualChunk();
        $chunk->setId('test-chunk-' . uniqid());
        $chunk->setDatasetId('test-dataset');
        $chunk->setDocumentId('test-document');
        $chunk->setTitle('测试文本块');
        $chunk->setContent('这是测试文本块的内容');
        $chunk->setKeywords('测试,关键词');
        $chunk->setSimilarityScore(0.85);
        $chunk->setPosition(1);
        $chunk->setLength(20);
        $chunk->setStatus('active');
        $chunk->setLanguage('zh');
        $chunk->setCreateTime(new \DateTimeImmutable());
        $chunk->setUpdateTime(new \DateTimeImmutable());

        return $chunk;
    }

    protected function getRepository(): VirtualChunkRepository
    {
        if (!isset($this->repository)) {
            $this->repository = self::getService(VirtualChunkRepository::class);
        }

        return $this->repository;
    }

    protected function onSetUp(): void
    {
        // 初始化repository
        $this->repository = self::getService(VirtualChunkRepository::class);
    }

    public function testRepositoryCreation(): void
    {
        $this->assertInstanceOf(VirtualChunkRepository::class, $this->repository);
    }

    public function testFind(): void
    {
        // VirtualChunkRepository的find方法总是返回null，因为这是虚拟实体
        $result = $this->repository->find('test-id');
        $this->assertNull($result);
    }

    public function testFindAll(): void
    {
        // 在测试环境中，findAll应该返回测试数据
        $results = $this->repository->findAll();
        $this->assertIsArray($results);

        // 在测试环境中，应该有一些测试数据
        if ([] !== $results) {
            foreach ($results as $result) {
                $this->assertInstanceOf(VirtualChunk::class, $result);
                $this->assertNotNull($result->getId());
                $this->assertNotNull($result->getTitle());
                $this->assertNotNull($result->getContent());
            }
        }
    }

    public function testFindBy(): void
    {
        // VirtualChunkRepository的findBy方法总是返回空数组
        $results = $this->repository->findBy(['title' => 'test']);
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testFindOneBy(): void
    {
        // VirtualChunkRepository的findOneBy方法总是返回null
        $result = $this->repository->findOneBy(['title' => 'test']);
        $this->assertNull($result);
    }

    public function testCount(): void
    {
        // 在测试环境中，count应该返回测试数据的数量
        $count = $this->repository->count();
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);

        // 测试带条件的计数
        $countWithCriteria = $this->repository->count(['title' => 'test']);
        $this->assertIsInt($countWithCriteria);
        $this->assertGreaterThanOrEqual(0, $countWithCriteria);
    }

    public function testCreateQueryBuilder(): void
    {
        // 测试QueryBuilder创建
        $qb = $this->repository->createQueryBuilder('v');
        $this->assertInstanceOf(QueryBuilder::class, $qb);
    }

    public function testVirtualChunkEntity(): void
    {
        // 测试VirtualChunk实体的基本功能
        $chunk = new VirtualChunk();

        // 测试基本的setter和getter
        $chunk->setId('test-chunk-id');
        $this->assertEquals('test-chunk-id', $chunk->getId());

        $chunk->setDatasetId('dataset-123');
        $this->assertEquals('dataset-123', $chunk->getDatasetId());

        $chunk->setDocumentId('doc-123');
        $this->assertEquals('doc-123', $chunk->getDocumentId());

        $chunk->setTitle('测试标题');
        $this->assertEquals('测试标题', $chunk->getTitle());

        $chunk->setContent('测试内容');
        $this->assertEquals('测试内容', $chunk->getContent());

        $chunk->setKeywords('测试,关键词');
        $this->assertEquals('测试,关键词', $chunk->getKeywords());

        $chunk->setSimilarityScore(0.85);
        $this->assertEquals(0.85, $chunk->getSimilarityScore());

        $chunk->setPosition(1);
        $this->assertEquals(1, $chunk->getPosition());

        $chunk->setLength(100);
        $this->assertEquals(100, $chunk->getLength());

        $chunk->setStatus('active');
        $this->assertEquals('active', $chunk->getStatus());

        $chunk->setLanguage('zh');
        $this->assertEquals('zh', $chunk->getLanguage());

        $testTime = new \DateTimeImmutable('2023-01-01 10:00:00');
        $chunk->setCreateTime($testTime);
        $this->assertEquals($testTime, $chunk->getCreateTime());

        $chunk->setUpdateTime($testTime);
        $this->assertEquals($testTime, $chunk->getUpdateTime());

        // 测试EasyAdmin兼容性方法
        $this->assertEquals('测试标题', $chunk->getName());
        $this->assertTrue($chunk->isAccessible());

        // 测试__toString方法
        $this->assertEquals('测试标题', (string) $chunk);
    }

    public function testVirtualChunkWithNullTitle(): void
    {
        // 测试title为null时的getName方法
        $chunk = new VirtualChunk();
        $chunk->setId('test-id');

        // 当title为null时，应该返回id
        $this->assertEquals('test-id', $chunk->getName());
        $this->assertEquals('test-id', (string) $chunk);
    }

    public function testVirtualChunkWithNullId(): void
    {
        // 测试id和title都为null时的getName方法
        $chunk = new VirtualChunk();

        // 当id和title都为null时，应该返回'Unknown'
        $this->assertEquals('Unknown', $chunk->getName());
        $this->assertEquals('Unknown', (string) $chunk);
    }

    public function testTestDataStructure(): void
    {
        // 测试测试数据的结构
        $allChunks = $this->repository->findAll();

        if ([] !== $allChunks) {
            foreach ($allChunks as $chunk) {
                $this->assertInstanceOf(VirtualChunk::class, $chunk);

                // 验证必需的字段
                $this->assertNotNull($chunk->getId());
                $this->assertNotNull($chunk->getTitle());
                $this->assertNotNull($chunk->getContent());
                $this->assertNotNull($chunk->getDatasetId());
                $this->assertNotNull($chunk->getDocumentId());
                $this->assertNotNull($chunk->getStatus());
                $this->assertNotNull($chunk->getLanguage());
                $this->assertNotNull($chunk->getCreateTime());
                $this->assertNotNull($chunk->getUpdateTime());

                // Getter方法已经有明确的类型声明，无需额外验证类型
                $this->assertInstanceOf(\DateTimeImmutable::class, $chunk->getCreateTime());
                $this->assertInstanceOf(\DateTimeImmutable::class, $chunk->getUpdateTime());

                // 验证数值字段
                $this->assertIsFloat($chunk->getSimilarityScore());
                $this->assertGreaterThanOrEqual(0.0, $chunk->getSimilarityScore());
                $this->assertLessThanOrEqual(1.0, $chunk->getSimilarityScore());

                $this->assertIsInt($chunk->getPosition());
                $this->assertGreaterThanOrEqual(0, $chunk->getPosition());

                $this->assertIsInt($chunk->getLength());
                $this->assertGreaterThanOrEqual(0, $chunk->getLength());
            }
        }
    }
}
