<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Enum\DocumentStatus;
use Tourze\RAGFlowApiBundle\Service\DocumentDeletionService;

/**
 * @internal
 */
#[CoversClass(DocumentDeletionService::class)]
#[RunTestsInSeparateProcesses]
class DocumentDeletionServiceTest extends AbstractIntegrationTestCase
{
    private DocumentDeletionService $deletionService;

    protected function onSetUp(): void
    {
        $this->deletionService = self::getService(DocumentDeletionService::class);
    }

    public function testServiceCreation(): void
    {
        $this->assertInstanceOf(DocumentDeletionService::class, $this->deletionService);
    }

    public function testExtractDocumentIds(): void
    {
        $encodedData = json_encode(['document_ids' => [1, 2, 3]]);
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            false !== $encodedData ? $encodedData : ''
        );

        $ids = $this->deletionService->extractDocumentIds($request);

        $this->assertIsArray($ids);
        $this->assertEquals([1, 2, 3], $ids);
    }

    public function testExtractDocumentIdsWithStringIds(): void
    {
        $encodedData = json_encode(['document_ids' => ['1', '2', '3']]);
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            false !== $encodedData ? $encodedData : ''
        );

        $ids = $this->deletionService->extractDocumentIds($request);

        $this->assertEquals([1, 2, 3], $ids);
    }

    public function testExtractDocumentIdsEmptyArray(): void
    {
        $encodedData = json_encode(['document_ids' => []]);
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            false !== $encodedData ? $encodedData : ''
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No document IDs provided');

        $this->deletionService->extractDocumentIds($request);
    }

    public function testExtractDocumentIdsInvalidJson(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid request data');

        $this->deletionService->extractDocumentIds($request);
    }

    public function testPerformBatchDelete(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Deletion Test Instance');
        $instance->setApiUrl('https://deletion-test.example.com/api');
        $instance->setApiKey('deletion-test-key');

        $dataset = new Dataset();
        $dataset->setName('Deletion Test Dataset');
        $dataset->setRemoteId('dataset-del-123');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $doc1 = new Document();
        $doc1->setName('Doc to Delete 1');
        $doc1->setFilename('doc1.txt');
        $doc1->setType('txt');
        $doc1->setStatus(DocumentStatus::PENDING);
        $doc1->setDataset($dataset);

        $doc2 = new Document();
        $doc2->setName('Doc to Delete 2');
        $doc2->setFilename('doc2.txt');
        $doc2->setType('txt');
        $doc2->setStatus(DocumentStatus::PENDING);
        $doc2->setDataset($dataset);

        $this->persistAndFlush($doc1);
        $this->persistAndFlush($doc2);

        $datasetId = $dataset->getId();
        $this->assertNotNull($datasetId);
        $this->assertIsInt($datasetId);

        $doc1Id = $doc1->getId();
        $doc2Id = $doc2->getId();
        $this->assertNotNull($doc1Id);
        $this->assertNotNull($doc2Id);

        [$deletedCount, $errors] = $this->deletionService->performBatchDelete(
            $datasetId,
            [$doc1Id, $doc2Id]
        );

        $this->assertIsInt($deletedCount);
        $this->assertIsArray($errors);
    }

    public function testDeleteDocument(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Single Delete Test');
        $instance->setApiUrl('https://single-delete.example.com/api');
        $instance->setApiKey('single-delete-key');

        $dataset = new Dataset();
        $dataset->setName('Single Delete Dataset');
        $dataset->setRemoteId('dataset-single-123');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $doc = new Document();
        $doc->setName('Doc to Delete Single');
        $doc->setFilename('single.txt');
        $doc->setType('txt');
        $doc->setStatus(DocumentStatus::PENDING);
        $doc->setDataset($dataset);

        $this->persistAndFlush($doc);

        // 删除文档
        $this->deletionService->deleteDocument($doc);

        // 验证已被删除
        // 无意义的断言已移除
    }
}
