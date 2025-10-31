<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\RAGFlowApiBundle\Entity\Chunk;
use Tourze\RAGFlowApiBundle\Entity\Document;

#[When(env: 'test')]
#[When(env: 'dev')]
class ChunkFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public const CHUNK_REFERENCE_PREFIX = 'chunk-';
    public const CHUNK_COUNT = 6;

    public function load(ObjectManager $manager): void
    {
        $chunks = [
            [
                'content' => 'Python是一种解释型、面向对象、动态数据类型的高级程序设计语言。Python由Guido van Rossum于1989年底发明，第一个公开发行版发行于1991年。',
                'position' => 0,
                'size' => 87,
                'similarityScore' => 0.95,
                'pageNumber' => 1,
                'startPos' => 0,
                'endPos' => 87,
                'tokenCount' => 42,
                'keywords' => ['Python', '编程语言', '面向对象'],
                'documentIndex' => 0,
            ],
            [
                'content' => 'JavaScript是一种具有函数优先的轻量级，解释型或即时编译型的编程语言。虽然它是作为开发Web页面的脚本语言而出名的，但是它也被用到了很多非浏览器环境中。',
                'position' => 1,
                'size' => 98,
                'similarityScore' => 0.92,
                'pageNumber' => 1,
                'startPos' => 0,
                'endPos' => 98,
                'tokenCount' => 48,
                'keywords' => ['JavaScript', 'Web', '脚本语言'],
                'documentIndex' => 1,
            ],
            [
                'content' => '在进行代码审查时，需要关注代码的可读性、可维护性、性能和安全性。确保代码遵循团队的编码规范和最佳实践。',
                'position' => 2,
                'size' => 68,
                'similarityScore' => 0.88,
                'pageNumber' => 2,
                'startPos' => 100,
                'endPos' => 168,
                'tokenCount' => 35,
                'keywords' => ['代码审查', '最佳实践', '编码规范'],
                'documentIndex' => 1,
            ],
            [
                'content' => '产品需求应该明确定义用户故事、接受标准和优先级。每个需求都应该是可测试的，并且与业务目标保持一致。',
                'position' => 0,
                'size' => 62,
                'similarityScore' => 0.90,
                'pageNumber' => 1,
                'startPos' => 0,
                'endPos' => 62,
                'tokenCount' => 32,
                'keywords' => ['产品需求', '用户故事', '接受标准'],
                'documentIndex' => 2,
            ],
            [
                'content' => 'RESTful API设计原则包括使用合适的HTTP方法、清晰的URL结构、正确的状态码以及良好的错误处理机制。',
                'position' => 0,
                'size' => 66,
                'similarityScore' => 0.93,
                'pageNumber' => 1,
                'startPos' => 0,
                'endPos' => 66,
                'tokenCount' => 31,
                'keywords' => ['RESTful', 'API设计', 'HTTP'],
                'documentIndex' => 3,
            ],
            [
                'content' => 'API认证和授权是确保系统安全的重要环节。常见的认证方式包括JWT、OAuth 2.0和API Key。',
                'position' => 1,
                'size' => 58,
                'similarityScore' => 0.91,
                'pageNumber' => 2,
                'startPos' => 68,
                'endPos' => 126,
                'tokenCount' => 28,
                'keywords' => ['认证', '授权', 'JWT', 'OAuth'],
                'documentIndex' => 3,
            ],
        ];

        $now = new \DateTimeImmutable();

        for ($i = 0; $i < self::CHUNK_COUNT; ++$i) {
            $data = $chunks[$i];
            $chunk = new Chunk();

            $chunk->setRemoteId('chunk-' . ($i + 1));
            $chunk->setContent($data['content']);
            $chunk->setPosition($data['position']);
            $chunk->setSize($data['size']);
            $chunk->setSimilarityScore($data['similarityScore']);
            $chunk->setPageNumber($data['pageNumber']);
            $chunk->setStartPos($data['startPos']);
            $chunk->setEndPos($data['endPos']);
            $chunk->setTokenCount($data['tokenCount']);
            $chunk->setKeywords($data['keywords']);

            // 设置关联的文档
            /** @var Document $document */
            $document = $this->getReference(DocumentFixtures::DOCUMENT_REFERENCE_PREFIX . $data['documentIndex'], Document::class);
            $chunk->setDocument($document);

            // 设置元数据
            $chunk->setMetadata([
                'source' => 'ragflow-api',
                'version' => '1.0',
                'indexed_at' => $now->format('Y-m-d H:i:s'),
            ]);

            // 设置位置信息
            $chunk->setPositions([
                'page' => $data['pageNumber'],
                'start' => $data['startPos'],
                'end' => $data['endPos'],
            ]);

            // 设置带权重的内容（示例）
            $chunk->setContentWithWeight($data['content'] . ' [weight: ' . $data['similarityScore'] . ']');

            // 设置时间字段
            $dayOffset = $i + 1;
            $chunk->setRemoteCreateTime($now->modify("-{$dayOffset} days"));
            $chunk->setRemoteUpdateTime($now->modify('-' . (int) ($dayOffset / 2) . ' days'));
            $chunk->setLastSyncTime($now);

            // 设置嵌入向量（示例：简化版本，实际应该是1536维或其他维度）
            $chunk->setEmbeddingVector([0.1, 0.2, 0.3, 0.4, 0.5]);

            $manager->persist($chunk);
            $this->addReference(self::CHUNK_REFERENCE_PREFIX . $i, $chunk);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            DocumentFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return [
            'rag-flow-api',
            'chunk',
        ];
    }
}
