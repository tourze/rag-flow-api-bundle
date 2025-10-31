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
use Tourze\RAGFlowApiBundle\Service\DatasetDocumentSyncService;

/**
 * @internal
 */
#[CoversClass(DatasetDocumentSyncService::class)]
#[RunTestsInSeparateProcesses]
class DatasetDocumentSyncServiceTest extends AbstractIntegrationTestCase
{
    private DatasetDocumentSyncService $syncService;

    protected function onSetUp(): void
    {
        $this->syncService = self::getService(DatasetDocumentSyncService::class);
    }

    public function testServiceCreation(): void
    {
        $this->assertInstanceOf(DatasetDocumentSyncService::class, $this->syncService);
    }

    public function testSyncAllDatasetDocuments(): void
    {
        $result = $this->syncService->syncAllDatasetDocuments();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_synced', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertIsInt($result['total_synced']);
        $this->assertIsArray($result['errors']);
    }

    public function testSyncDatasetDocumentsWithoutRemoteId(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Sync Test Instance');
        $instance->setApiUrl('https://sync-test.example.com/api');
        $instance->setApiKey('sync-test-key');

        $dataset = new Dataset();
        $dataset->setName('Dataset Without Remote ID');
        $dataset->setRemoteId(null);
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $count = $this->syncService->syncDatasetDocuments($dataset);

        $this->assertEquals(0, $count);
    }

    public function testAutoSyncDocumentsChunks(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Chunks Test Instance');
        $instance->setApiUrl('https://chunks-test.example.com/api');
        $instance->setApiKey('chunks-test-key');

        $dataset = new Dataset();
        $dataset->setName('Chunks Test Dataset');
        $dataset->setRemoteId('dataset-chunks-123');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $doc = new Document();
        $doc->setName('Doc with Chunks');
        $doc->setFilename('chunks.txt');
        $doc->setType('txt');
        $doc->setStatus(DocumentStatus::COMPLETED);
        $doc->setDataset($dataset);
        $doc->setRemoteId('doc-remote-123');
        $doc->setProgress(1.0);

        $this->persistAndFlush($doc);

        // 不会抛出异常即为成功
        $this->syncService->autoSyncDocumentsChunks($dataset, [$doc]);

        // 无意义的断言已移除
    }

    public function testSyncDocumentChunks(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Single Chunk Test');
        $instance->setApiUrl('https://single-chunk.example.com/api');
        $instance->setApiKey('single-chunk-key');

        $dataset = new Dataset();
        $dataset->setName('Single Chunk Dataset');
        $dataset->setRemoteId('dataset-single-456');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $doc = new Document();
        $doc->setName('Single Chunk Doc');
        $doc->setFilename('single.txt');
        $doc->setType('txt');
        $doc->setStatus(DocumentStatus::COMPLETED);
        $doc->setDataset($dataset);
        $doc->setRemoteId('doc-remote-456');
        $doc->setProgress(1.0);

        $this->persistAndFlush($doc);

        // 不会抛出异常即为成功
        $this->syncService->syncDocumentChunks($doc, 'dataset-single-456');

        // 无意义的断言已移除
    }

    public function testProcessSingleDocumentChunkSync(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Process Test Instance');
        $instance->setApiUrl('https://process-test.example.com/api');
        $instance->setApiKey('process-test-key');

        $dataset = new Dataset();
        $dataset->setName('Process Test Dataset');
        $dataset->setRemoteId('dataset-process-789');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $doc = new Document();
        $doc->setName('Process Doc');
        $doc->setFilename('process.txt');
        $doc->setType('txt');
        $doc->setStatus(DocumentStatus::COMPLETED);
        $doc->setDataset($dataset);
        $doc->setRemoteId('doc-remote-789');

        $this->persistAndFlush($doc);

        $result = $this->syncService->processSingleDocumentChunkSync($dataset, $doc);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('synced_count', $result);
        $this->assertArrayHasKey('total_count', $result);
    }

    public function testProcessBatchChunkSync(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Batch Sync Test');
        $instance->setApiUrl('https://batch-sync.example.com/api');
        $instance->setApiKey('batch-sync-key');

        $dataset = new Dataset();
        $dataset->setName('Batch Sync Dataset');
        $dataset->setRemoteId('dataset-batch-999');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $result = $this->syncService->processBatchChunkSync($dataset);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('synced_count', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertIsInt($result['synced_count']);
        $this->assertIsArray($result['errors']);
    }
}
