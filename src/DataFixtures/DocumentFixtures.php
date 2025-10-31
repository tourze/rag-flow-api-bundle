<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Enum\DocumentStatus;

#[When(env: 'test')]
#[When(env: 'dev')]
class DocumentFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public const DOCUMENT_REFERENCE_PREFIX = 'document-';
    public const DOCUMENT_COUNT = 5;

    public function load(ObjectManager $manager): void
    {
        $documents = [
            [
                'name' => 'Python编程指南',
                'filename' => 'python-guide.pdf',
                'type' => 'pdf',
                'mimeType' => 'application/pdf',
                'size' => 1024000,
                'status' => DocumentStatus::COMPLETED,
                'language' => 'zh',
                'chunkCount' => 45,
                'summary' => '详细介绍Python编程语言的基础知识和高级特性',
                'datasetIndex' => 1,
            ],
            [
                'name' => 'JavaScript最佳实践',
                'filename' => 'js-best-practices.md',
                'type' => 'md',
                'mimeType' => 'text/markdown',
                'size' => 512000,
                'status' => DocumentStatus::PROCESSING,
                'language' => 'zh',
                'chunkCount' => 28,
                'summary' => 'JavaScript开发中的最佳实践和常见陷阱',
                'datasetIndex' => 2,
            ],
            [
                'name' => '产品需求文档',
                'filename' => 'product-requirements.docx',
                'type' => 'docx',
                'mimeType' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'size' => 768000,
                'status' => DocumentStatus::COMPLETED,
                'language' => 'zh',
                'chunkCount' => 35,
                'summary' => '新产品功能的详细需求说明',
                'datasetIndex' => 3,
            ],
            [
                'name' => 'API接口文档',
                'filename' => 'api-documentation.txt',
                'type' => 'txt',
                'mimeType' => 'text/plain',
                'size' => 256000,
                'status' => DocumentStatus::UPLOADED,
                'language' => 'en',
                'chunkCount' => 18,
                'summary' => 'RESTful API接口的详细说明文档',
                'datasetIndex' => 1,
            ],
            [
                'name' => '数据分析报告',
                'filename' => 'data-analysis.xlsx',
                'type' => 'xlsx',
                'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'size' => 2048000,
                'status' => DocumentStatus::PENDING,
                'language' => 'zh',
                'chunkCount' => null,
                'summary' => null,
                'datasetIndex' => 2,
            ],
        ];

        $now = new \DateTimeImmutable();

        for ($i = 0; $i < self::DOCUMENT_COUNT; ++$i) {
            $data = $documents[$i];
            $document = new Document();

            $document->setRemoteId('doc-' . ($i + 1));
            $document->setName($data['name']);
            $document->setFilename($data['filename']);
            $document->setType($data['type']);
            $document->setMimeType($data['mimeType']);
            $document->setSize($data['size']);
            $document->setStatus($data['status']);
            $document->setLanguage($data['language']);

            if (null !== $data['chunkCount']) {
                $document->setChunkCount($data['chunkCount']);
            }

            if (null !== $data['summary']) {
                $document->setSummary($data['summary']);
            }

            // 设置关联的数据集
            /** @var Dataset $dataset */
            $dataset = $this->getReference('dataset-' . $data['datasetIndex'], Dataset::class);
            $document->setDataset($dataset);

            // 设置解析进度
            if (DocumentStatus::COMPLETED === $data['status']) {
                $document->setProgress(100.0);
                $document->setProgressMsg('文档处理完成');
                $document->setParseStatus('completed');
            } elseif (DocumentStatus::PROCESSING === $data['status']) {
                $document->setProgress(65.0);
                $document->setProgressMsg('正在处理文档内容...');
                $document->setParseStatus('processing');
            }

            // 设置时间字段
            $dayOffset = $i + 1;
            $document->setRemoteCreateTime($now->modify("-{$dayOffset} days"));
            $document->setRemoteUpdateTime($now->modify('-' . (int) ($dayOffset / 2) . ' days'));
            $document->setLastSyncTime($now);

            // 设置文件路径（仅供示例）
            $document->setFilePath('/var/storage/documents/' . $data['filename']);

            $manager->persist($document);
            $this->addReference(self::DOCUMENT_REFERENCE_PREFIX . $i, $document);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            DatasetFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return [
            'rag-flow-api',
            'document',
        ];
    }
}
