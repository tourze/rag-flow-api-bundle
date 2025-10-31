<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Enum\DocumentStatus;
use Tourze\RAGFlowApiBundle\Service\DocumentUpdateService;

/**
 * @internal
 */
#[CoversClass(DocumentUpdateService::class)]
#[RunTestsInSeparateProcesses]
final class DocumentUpdateServiceTest extends AbstractIntegrationTestCase
{
    private DocumentUpdateService $updateService;

    protected function onSetUp(): void
    {
        $this->updateService = self::getService(DocumentUpdateService::class);
    }

    public function test服务创建成功(): void
    {
        $this->assertInstanceOf(DocumentUpdateService::class, $this->updateService);
    }

    public function test更新全部字段成功(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Update Instance');
        $instance->setApiUrl('https://update.example.com/api');
        $instance->setApiKey('update-key');

        $dataset = new Dataset();
        $dataset->setName('Update Dataset');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $document = new Document();
        $document->setName('Original Name');
        $document->setFilename('original.txt');
        $document->setType('txt');
        $document->setStatus(DocumentStatus::PENDING);
        $document->setDataset($dataset);
        $document->setSummary('Original Summary');
        $document->setLanguage('en');

        $this->persistAndFlush($document);

        $originalUpdateTime = $document->getUpdateTime();

        // 等待一小段时间确保时间戳不同
        sleep(1);

        $updateData = [
            'name' => 'Updated Name',
            'summary' => 'Updated Summary',
            'language' => 'zh',
        ];

        $this->updateService->updateDocumentFromData($document, $updateData);

        $this->assertSame('Updated Name', $document->getName());
        $this->assertSame('Updated Summary', $document->getSummary());
        $this->assertSame('zh', $document->getLanguage());

        // 验证时间戳已更新
        $newUpdateTime = $document->getUpdateTime();
        $this->assertNotNull($newUpdateTime);
        if (null !== $originalUpdateTime) {
            $this->assertGreaterThanOrEqual($originalUpdateTime->getTimestamp(), $newUpdateTime->getTimestamp());
        }

        $this->assertNotNull($document->getLastSyncTime());
    }

    public function test忽略空值不覆盖原数据(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Null Test Instance');
        $instance->setApiUrl('https://null-test.example.com/api');
        $instance->setApiKey('null-test-key');

        $dataset = new Dataset();
        $dataset->setName('Null Test Dataset');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $document = new Document();
        $document->setName('Original Name');
        $document->setFilename('null-test.txt');
        $document->setType('txt');
        $document->setStatus(DocumentStatus::PENDING);
        $document->setDataset($dataset);
        $document->setSummary('Original Summary');
        $document->setLanguage('en');

        $this->persistAndFlush($document);

        // 传入包含null值的数据
        $updateData = [
            'name' => null,
            'summary' => null,
            'language' => null,
        ];

        $this->updateService->updateDocumentFromData($document, $updateData);

        // 原值应保持不变
        $this->assertSame('Original Name', $document->getName());
        $this->assertSame('Original Summary', $document->getSummary());
        $this->assertSame('en', $document->getLanguage());
    }

    public function test非字符串输入被忽略(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Type Test Instance');
        $instance->setApiUrl('https://type-test.example.com/api');
        $instance->setApiKey('type-test-key');

        $dataset = new Dataset();
        $dataset->setName('Type Test Dataset');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $document = new Document();
        $document->setName('Original Name');
        $document->setFilename('type-test.txt');
        $document->setType('txt');
        $document->setStatus(DocumentStatus::PENDING);
        $document->setDataset($dataset);
        $document->setSummary('Original Summary');
        $document->setLanguage('en');

        $this->persistAndFlush($document);

        // 传入非字符串类型的数据
        $updateData = [
            'name' => 123,
            'summary' => ['not', 'a', 'string'],
            'language' => true,
        ];

        $this->updateService->updateDocumentFromData($document, $updateData);

        // 原值应保持不变
        $this->assertSame('Original Name', $document->getName());
        $this->assertSame('Original Summary', $document->getSummary());
        $this->assertSame('en', $document->getLanguage());
    }

    public function test更新时间戳刷新(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Timestamp Instance');
        $instance->setApiUrl('https://timestamp.example.com/api');
        $instance->setApiKey('timestamp-key');

        $dataset = new Dataset();
        $dataset->setName('Timestamp Dataset');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $document = new Document();
        $document->setName('Timestamp Doc');
        $document->setFilename('timestamp.txt');
        $document->setType('txt');
        $document->setStatus(DocumentStatus::PENDING);
        $document->setDataset($dataset);

        $this->persistAndFlush($document);

        $originalUpdateTime = $document->getUpdateTime();
        $originalSyncTime = $document->getLastSyncTime();

        // 等待确保时间戳不同
        sleep(1);

        $updateData = [
            'name' => 'Updated Timestamp Doc',
        ];

        $this->updateService->updateDocumentFromData($document, $updateData);

        $newUpdateTime = $document->getUpdateTime();
        $newSyncTime = $document->getLastSyncTime();

        // 验证updateTime已更新
        $this->assertNotNull($newUpdateTime);
        if (null !== $originalUpdateTime) {
            $this->assertGreaterThanOrEqual($originalUpdateTime->getTimestamp(), $newUpdateTime->getTimestamp());
        }

        // 验证lastSyncTime已更新
        $this->assertNotNull($newSyncTime);
        if (null !== $originalSyncTime) {
            $this->assertGreaterThanOrEqual($originalSyncTime->getTimestamp(), $newSyncTime->getTimestamp());
        }
    }

    public function test部分更新字段成功(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Partial Instance');
        $instance->setApiUrl('https://partial.example.com/api');
        $instance->setApiKey('partial-key');

        $dataset = new Dataset();
        $dataset->setName('Partial Dataset');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $document = new Document();
        $document->setName('Original Name');
        $document->setFilename('partial.txt');
        $document->setType('txt');
        $document->setStatus(DocumentStatus::PENDING);
        $document->setDataset($dataset);
        $document->setSummary('Original Summary');
        $document->setLanguage('en');

        $this->persistAndFlush($document);

        // 只更新summary字段
        $updateData = [
            'summary' => 'New Summary Only',
        ];

        $this->updateService->updateDocumentFromData($document, $updateData);

        $this->assertSame('Original Name', $document->getName());
        $this->assertSame('New Summary Only', $document->getSummary());
        $this->assertSame('en', $document->getLanguage());
    }
}
