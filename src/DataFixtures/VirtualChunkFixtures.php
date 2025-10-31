<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\RAGFlowApiBundle\Entity\VirtualChunk;

#[When(env: 'test')]
#[When(env: 'dev')]
class VirtualChunkFixtures extends Fixture implements FixtureGroupInterface
{
    public const VIRTUAL_CHUNK_REFERENCE_PREFIX = 'virtual-chunk-';
    public const VIRTUAL_CHUNK_COUNT = 4;

    public function load(ObjectManager $manager): void
    {
        $chunks = [
            [
                'id' => 'virtual-chunk-001',
                'datasetId' => 'dataset-1',
                'documentId' => 'doc-1',
                'title' => 'Python基础语法',
                'content' => 'Python是一种解释型、面向对象、动态数据类型的高级程序设计语言。它具有简洁清晰的语法，易于学习和使用。',
                'keywords' => 'Python, 编程语言, 基础语法',
                'similarityScore' => 0.95,
                'position' => 0,
                'length' => 87,
                'status' => 'indexed',
                'language' => 'zh',
                'metadata' => [
                    'source' => 'python-guide.pdf',
                    'page' => 1,
                    'section' => 'introduction',
                ],
            ],
            [
                'id' => 'virtual-chunk-002',
                'datasetId' => 'dataset-2',
                'documentId' => 'doc-2',
                'title' => 'JavaScript异步编程',
                'content' => 'JavaScript的异步编程是其核心特性之一。通过Promise、async/await等机制，可以更优雅地处理异步操作。',
                'keywords' => 'JavaScript, 异步, Promise',
                'similarityScore' => 0.92,
                'position' => 5,
                'length' => 76,
                'status' => 'indexed',
                'language' => 'zh',
                'metadata' => [
                    'source' => 'js-best-practices.md',
                    'section' => 'async-programming',
                ],
            ],
            [
                'id' => 'virtual-chunk-003',
                'datasetId' => 'dataset-1',
                'documentId' => 'doc-3',
                'title' => 'RESTful API设计',
                'content' => 'RESTful API是一种架构风格，强调资源的表现层状态转换。它使用标准的HTTP方法来操作资源。',
                'keywords' => 'RESTful, API, HTTP',
                'similarityScore' => 0.88,
                'position' => 12,
                'length' => 68,
                'status' => 'indexed',
                'language' => 'zh',
                'metadata' => [
                    'source' => 'api-documentation.txt',
                    'section' => 'design-principles',
                ],
            ],
            [
                'id' => 'virtual-chunk-004',
                'datasetId' => 'dataset-3',
                'documentId' => 'doc-4',
                'title' => '敏捷开发实践',
                'content' => '敏捷开发是一种以人为核心、迭代、循序渐进的软件开发方法。它强调快速响应变化和持续交付价值。',
                'keywords' => '敏捷开发, 迭代, Scrum',
                'similarityScore' => 0.90,
                'position' => 8,
                'length' => 72,
                'status' => 'indexed',
                'language' => 'zh',
                'metadata' => [
                    'source' => 'product-requirements.docx',
                    'page' => 3,
                    'section' => 'methodology',
                ],
            ],
        ];

        for ($i = 0; $i < self::VIRTUAL_CHUNK_COUNT; ++$i) {
            $data = $chunks[$i];
            $chunk = new VirtualChunk();

            $chunk->setId($data['id']);
            $chunk->setDatasetId($data['datasetId']);
            $chunk->setDocumentId($data['documentId']);
            $chunk->setTitle($data['title']);
            $chunk->setContent($data['content']);
            $chunk->setKeywords($data['keywords']);
            $chunk->setSimilarityScore($data['similarityScore']);
            $chunk->setPosition($data['position']);
            $chunk->setLength($data['length']);
            $chunk->setStatus($data['status']);
            $chunk->setLanguage($data['language']);
            $chunk->setMetadata($data['metadata']);

            // 注意：虚拟实体通常不需要持久化到数据库
            // 这里仅作为示例，实际使用中可能需要通过API获取数据
            // $manager->persist($chunk);
            $this->addReference(self::VIRTUAL_CHUNK_REFERENCE_PREFIX . $i, $chunk);
        }

        // 注意：虚拟实体不需要flush
        // $manager->flush();
    }

    public static function getGroups(): array
    {
        return [
            'rag-flow-api',
            'virtual-chunk',
        ];
    }
}
