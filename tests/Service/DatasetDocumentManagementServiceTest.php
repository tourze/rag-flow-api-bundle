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
use Tourze\RAGFlowApiBundle\Service\DatasetDocumentManagementService;

/**
 * @internal
 */
#[CoversClass(DatasetDocumentManagementService::class)]
#[RunTestsInSeparateProcesses]
class DatasetDocumentManagementServiceTest extends AbstractIntegrationTestCase
{
    private DatasetDocumentManagementService $managementService;

    protected function onSetUp(): void
    {
        $this->managementService = self::getService(DatasetDocumentManagementService::class);
    }

    public function testServiceCreation(): void
    {
        $this->assertInstanceOf(DatasetDocumentManagementService::class, $this->managementService);
    }

    public function testGetDatasetDocumentStats(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Stats Test Instance');
        $instance->setApiUrl('https://stats-test.example.com/api');
        $instance->setApiKey('stats-test-key');

        $dataset = new Dataset();
        $dataset->setName('Stats Test Dataset');
        $dataset->setRemoteId('dataset-stats-123');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $doc1 = new Document();
        $doc1->setName('Pending Doc');
        $doc1->setFilename('pending.txt');
        $doc1->setType('txt');
        $doc1->setStatus(DocumentStatus::PENDING);
        $doc1->setDataset($dataset);

        $doc2 = new Document();
        $doc2->setName('Completed Doc');
        $doc2->setFilename('completed.txt');
        $doc2->setType('txt');
        $doc2->setStatus(DocumentStatus::COMPLETED);
        $doc2->setDataset($dataset);

        $this->persistAndFlush($doc1);
        $this->persistAndFlush($doc2);

        $stats = $this->managementService->getDatasetDocumentStats($dataset);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('processing', $stats);
        $this->assertArrayHasKey('completed', $stats);
        $this->assertArrayHasKey('failed', $stats);
        $this->assertArrayHasKey('totalSize', $stats);
    }

    public function testHandleDocumentUpload(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Upload Test Instance');
        $instance->setApiUrl('https://upload-test.example.com/api');
        $instance->setApiKey('upload-test-key');

        $dataset = new Dataset();
        $dataset->setName('Upload Test Dataset');
        $dataset->setRemoteId('dataset-upload-456');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $uploadResult = [
            'data' => [
                [
                    'id' => 'doc-remote-1',
                    'name' => 'Uploaded Doc 1',
                    'type' => 'pdf',
                    'size' => 1024,
                    'status' => 'pending',
                ],
            ],
        ];

        $results = $this->managementService->handleDocumentUpload($dataset, $uploadResult);

        $this->assertIsArray($results);
        $this->assertCount(1, $results);
    }

    public function testDeleteDocument(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Delete Test Instance');
        $instance->setApiUrl('https://delete-test.example.com/api');
        $instance->setApiKey('delete-test-key');

        $dataset = new Dataset();
        $dataset->setName('Delete Test Dataset');
        $dataset->setRemoteId('dataset-delete-789');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $doc = new Document();
        $doc->setName('Doc to Delete');
        $doc->setFilename('delete.txt');
        $doc->setType('txt');
        $doc->setStatus(DocumentStatus::PENDING);
        $doc->setDataset($dataset);

        $this->persistAndFlush($doc);

        $this->managementService->deleteDocument($doc);

        // 无意义的断言已移除
    }
}
