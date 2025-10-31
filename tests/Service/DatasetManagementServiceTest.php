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
use Tourze\RAGFlowApiBundle\Service\DatasetManagementService;

/**
 * @internal
 */
#[CoversClass(DatasetManagementService::class)]
#[RunTestsInSeparateProcesses]
class DatasetManagementServiceTest extends AbstractIntegrationTestCase
{
    private DatasetManagementService $managementService;

    protected function onSetUp(): void
    {
        $this->managementService = self::getService(DatasetManagementService::class);
    }

    public function testServiceCreation(): void
    {
        $this->assertInstanceOf(DatasetManagementService::class, $this->managementService);
    }

    public function testValidateDatasetAccess(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Management Test Instance');
        $instance->setApiUrl('https://management-test.example.com/api');
        $instance->setApiKey('management-test-key');

        $dataset = new Dataset();
        $dataset->setName('Test Dataset for Management');
        $dataset->setRemoteId('dataset-mgmt-123');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $datasetId = $dataset->getId();
        $this->assertNotNull($datasetId);

        $validatedDataset = $this->managementService->validateDatasetAccess($datasetId);

        $this->assertInstanceOf(Dataset::class, $validatedDataset);
        $this->assertEquals('Test Dataset for Management', $validatedDataset->getName());
    }

    public function testValidateDatasetAccessNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('数据集 999999 不存在');

        $this->managementService->validateDatasetAccess(999999);
    }

    public function testFindDatasetByRemoteId(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Find Test Instance');
        $instance->setApiUrl('https://find-test.example.com/api');
        $instance->setApiKey('find-test-key');

        $dataset = new Dataset();
        $dataset->setName('Dataset by Remote ID');
        $dataset->setRemoteId('remote-unique-123');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $foundDataset = $this->managementService->findDatasetByRemoteId('remote-unique-123');

        $this->assertInstanceOf(Dataset::class, $foundDataset);
        $this->assertEquals('Dataset by Remote ID', $foundDataset->getName());
    }

    public function testFindDatasetByRemoteIdNotFound(): void
    {
        $result = $this->managementService->findDatasetByRemoteId('non-existent-id');
        $this->assertNull($result);
    }

    public function testGetDatasetDocumentStats(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Stats Test Instance');
        $instance->setApiUrl('https://stats-test.example.com/api');
        $instance->setApiKey('stats-test-key');

        $dataset = new Dataset();
        $dataset->setName('Dataset for Stats');
        $dataset->setRemoteId('dataset-stats-456');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $doc1 = new Document();
        $doc1->setName('Doc 1');
        $doc1->setFilename('doc1.txt');
        $doc1->setType('txt');
        $doc1->setStatus(DocumentStatus::PENDING);
        $doc1->setDataset($dataset);

        $doc2 = new Document();
        $doc2->setName('Doc 2');
        $doc2->setFilename('doc2.txt');
        $doc2->setType('txt');
        $doc2->setStatus(DocumentStatus::COMPLETED);
        $doc2->setDataset($dataset);

        $this->persistAndFlush($doc1);
        $this->persistAndFlush($doc2);

        $datasetId = $dataset->getId();
        $this->assertNotNull($datasetId);

        $stats = $this->managementService->getDatasetDocumentStats($datasetId);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_documents', $stats);
        $this->assertArrayHasKey('processed_documents', $stats);
        $this->assertArrayHasKey('pending_documents', $stats);
        $this->assertGreaterThanOrEqual(2, $stats['total_documents']);
    }

    public function testCanDeleteDataset(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Delete Check Instance');
        $instance->setApiUrl('https://delete-check.example.com/api');
        $instance->setApiKey('delete-check-key');

        $dataset = new Dataset();
        $dataset->setName('Empty Dataset');
        $dataset->setRemoteId('dataset-empty-789');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $datasetId = $dataset->getId();
        $this->assertNotNull($datasetId);

        $canDelete = $this->managementService->canDeleteDataset($datasetId);

        $this->assertTrue($canDelete);
    }

    public function testGetDatasetFullInfo(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Full Info Instance');
        $instance->setApiUrl('https://full-info.example.com/api');
        $instance->setApiKey('full-info-key');

        $dataset = new Dataset();
        $dataset->setName('Dataset Full Info');
        $dataset->setRemoteId('dataset-full-999');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $datasetId = $dataset->getId();
        $this->assertNotNull($datasetId);
        $this->assertIsInt($datasetId);

        $fullInfo = $this->managementService->getDatasetFullInfo($datasetId);

        $this->assertIsArray($fullInfo);
        $this->assertArrayHasKey('dataset', $fullInfo);
        $this->assertArrayHasKey('stats', $fullInfo);
        $this->assertArrayHasKey('can_delete', $fullInfo);
        $this->assertArrayHasKey('document_count', $fullInfo);
        $this->assertInstanceOf(Dataset::class, $fullInfo['dataset']);
    }
}
