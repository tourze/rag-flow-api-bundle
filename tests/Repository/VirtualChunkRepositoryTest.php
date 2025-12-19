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
 * VirtualChunkRepository 测试
 *
 * 注意：VirtualChunk 是一个虚拟实体，但为了符合测试规范，
 * 仍然继承 AbstractRepositoryTestCase 并实现必要的方法
 *
 * @internal
 */
#[CoversClass(VirtualChunkRepository::class)]
#[RunTestsInSeparateProcesses]
class VirtualChunkRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): object
    {
        $uniqueSuffix = uniqid('', true);

        $chunk = new VirtualChunk();
        $chunk->setId('test-chunk-' . $uniqueSuffix);
        $chunk->setDatasetId('dataset-' . $uniqueSuffix);
        $chunk->setDocumentId('doc-' . $uniqueSuffix);
        $chunk->setTitle('测试文本块_' . $uniqueSuffix);
        $chunk->setContent('测试内容_' . $uniqueSuffix);
        $chunk->setKeywords('测试,关键词');
        $chunk->setSimilarityScore(0.85);
        $chunk->setPosition(1);
        $chunk->setLength(100);
        $chunk->setStatus('active');
        $chunk->setLanguage('zh');
        $chunk->setCreateTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        $chunk->setUpdateTime(new \DateTimeImmutable('2023-01-01 10:00:00'));

        return $chunk;
    }

    /**
     * @return ServiceEntityRepository<VirtualChunk>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return self::getService(VirtualChunkRepository::class);
    }

    protected function onSetUp(): void
    {
        // 虚拟Repository测试不需要特殊设置
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

        // 当id和title都为null时，getName()返回'Unknown'，__toString()返回'(new)'
        $this->assertEquals('Unknown', $chunk->getName());
        $this->assertEquals('(new)', (string) $chunk);
    }

    public function testTestDataStructure(): void
    {
        // 测试测试数据的结构
        $allChunks = $this->getRepository()->findAll();

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

    public function testCreateQueryBuilder(): void
    {
        $qb = $this->getRepository()->createQueryBuilder('v');
        $this->assertInstanceOf(QueryBuilder::class, $qb);
    }
}
