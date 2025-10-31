<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Client\RAGFlowApiClient;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Repository\DatasetRepository;
use Tourze\RAGFlowApiBundle\Request\DeleteDocumentRequest;
use Tourze\RAGFlowApiBundle\Request\GetParseStatusRequest;
use Tourze\RAGFlowApiBundle\Request\ListChunksRequest;
use Tourze\RAGFlowApiBundle\Request\ListDocumentsRequest;
use Tourze\RAGFlowApiBundle\Request\ParseChunksRequest;
use Tourze\RAGFlowApiBundle\Request\ParseDocumentRequest;
use Tourze\RAGFlowApiBundle\Request\StopParsingRequest;
use Tourze\RAGFlowApiBundle\Service\CurlUploadService;
use Tourze\RAGFlowApiBundle\Service\DocumentService;
use Tourze\RAGFlowApiBundle\Service\LocalDataSyncService;
use Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManagerInterface;

/**
 * @internal
 */
#[CoversClass(DocumentService::class)]
#[RunTestsInSeparateProcesses]
class DocumentServiceTest extends AbstractIntegrationTestCase
{
    /** @var RAGFlowApiClient&MockObject */
    private RAGFlowApiClient $client;

    /** @var RAGFlowInstanceManagerInterface&MockObject */
    private RAGFlowInstanceManagerInterface $instanceManager;

    /** @var LocalDataSyncService&MockObject */
    private LocalDataSyncService $localDataSyncService;

    /** @var DatasetRepository&MockObject */
    private DatasetRepository $datasetRepository;

    /** @var CurlUploadService&MockObject */
    private CurlUploadService $curlUploadService;

    private DocumentService $documentService;

    protected function onSetUp(): void
    {
        self::clearServiceLocatorCache();
        $this->client = $this->createMock(RAGFlowApiClient::class);
        $this->instanceManager = $this->createMock(RAGFlowInstanceManagerInterface::class);
        $this->localDataSyncService = $this->createMock(LocalDataSyncService::class);
        $this->datasetRepository = $this->createMock(DatasetRepository::class);
        $this->curlUploadService = $this->createMock(CurlUploadService::class);
        $this->instanceManager->expects($this->once())->method('getDefaultClient')->willReturn($this->client);
        $container = self::getContainer();
        $container->set(RAGFlowInstanceManagerInterface::class, $this->instanceManager);
        $container->set(LocalDataSyncService::class, $this->localDataSyncService);
        $container->set(DatasetRepository::class, $this->datasetRepository);
        $container->set(CurlUploadService::class, $this->curlUploadService);
        $this->documentService = self::getService(DocumentService::class);
    }

    public function testUpload(): void
    {
        $datasetId = 'dataset-123';
        $files = ['document1' => '/path/to/document1.pdf', 'document2' => '/path/to/document2.docx'];
        $expectedResponse = ['retcode' => 0, 'retmsg' => 'success', 'data' => [['id' => 'doc-1', 'name' => 'document1.pdf', 'size' => 1024, 'status' => 'uploaded'], ['id' => 'doc-2', 'name' => 'document2.docx', 'size' => 2048, 'status' => 'uploaded']]];
        $instance = new RAGFlowInstance();
        $instance->setName('Test Instance');
        $this->client->expects($this->once())->method('getInstance')->willReturn($instance);
        $this->curlUploadService->expects($this->exactly(2))->method('uploadDocument')->willReturnOnConsecutiveCalls(['data' => [['id' => 'doc-1', 'name' => 'document1.pdf', 'size' => 1024, 'status' => 'uploaded']]], ['data' => [['id' => 'doc-2', 'name' => 'document2.docx', 'size' => 2048, 'status' => 'uploaded']]]);
        $result = $this->documentService->upload($datasetId, $files);
        $this->assertEquals($expectedResponse, $result);
    }

    public function testList(): void
    {
        $datasetId = 'dataset-123';
        $filters = ['status' => 'parsed', 'limit' => 10];
        $apiResponse = [['id' => 'doc-1', 'name' => 'document1.pdf', 'size' => 1024, 'status' => 'parsed', 'created_time' => '2024-01-01 10:00:00'], ['id' => 'doc-2', 'name' => 'document2.docx', 'size' => 2048, 'status' => 'parsed', 'created_time' => '2024-01-02 10:00:00']];
        $mockDataset = $this->createMock(Dataset::class);
        $expectedDocuments = [$this->createMock(Document::class), $this->createMock(Document::class)];
        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof ListDocumentsRequest;
        }))->willReturn($apiResponse);
        $this->datasetRepository->expects($this->once())->method('findOneBy')->willReturn($mockDataset);
        $this->localDataSyncService->expects($this->exactly(2))->method('syncDocumentFromApi')->willReturnOnConsecutiveCalls(...$expectedDocuments);
        $result = $this->documentService->list($datasetId, $filters);
        $this->assertEquals($expectedDocuments, $result);
    }

    public function testListWithoutFilters(): void
    {
        $datasetId = 'dataset-123';
        $apiResponse = [['id' => 'doc-1', 'name' => 'document1.pdf', 'size' => 1024, 'status' => 'uploaded']];
        $mockDataset = $this->createMock(Dataset::class);
        $expectedDocument = $this->createMock(Document::class);
        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof ListDocumentsRequest;
        }))->willReturn($apiResponse);
        $this->datasetRepository->expects($this->once())->method('findOneBy')->willReturn($mockDataset);
        $this->localDataSyncService->expects($this->once())->method('syncDocumentFromApi')->willReturn($expectedDocument);
        $result = $this->documentService->list($datasetId);
        $this->assertEquals([$expectedDocument], $result);
    }

    public function testDelete(): void
    {
        $datasetId = 'dataset-123';
        $documentId = 'doc-456';
        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof DeleteDocumentRequest;
        }))->willReturn(['success' => true]);
        $result = $this->documentService->delete($datasetId, $documentId);
        $this->assertTrue($result);
    }

    public function testParse(): void
    {
        $datasetId = 'dataset-123';
        $documentId = 'doc-456';
        $options = ['layout_recognize' => true, 'table_recognize' => true];
        $expectedResponse = ['document' => ['id' => 'doc-456', 'status' => 'parsing', 'parse_job_id' => 'job-789']];
        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof ParseDocumentRequest;
        }))->willReturn($expectedResponse);
        $result = $this->documentService->parse($datasetId, $documentId, $options);
        $this->assertEquals($expectedResponse, $result);
    }

    public function testParseWithoutOptions(): void
    {
        $datasetId = 'dataset-123';
        $documentId = 'doc-456';
        $expectedResponse = ['document' => ['id' => 'doc-456', 'status' => 'parsing', 'parse_job_id' => 'job-789']];
        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof ParseDocumentRequest;
        }))->willReturn($expectedResponse);
        $result = $this->documentService->parse($datasetId, $documentId);
        $this->assertEquals($expectedResponse, $result);
    }

    public function testGetParseStatus(): void
    {
        $datasetId = 'dataset-123';
        $documentId = 'doc-456';
        $expectedResponse = ['document' => ['id' => 'doc-456', 'status' => 'parsed', 'progress' => 100, 'chunks_count' => 25, 'parse_time' => 120]];
        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof GetParseStatusRequest;
        }))->willReturn($expectedResponse);
        $result = $this->documentService->getParseStatus($datasetId, $documentId);
        $this->assertEquals($expectedResponse, $result);
    }

    public function testDeleteWithException(): void
    {
        $datasetId = 'dataset-123';
        $documentId = 'doc-456';
        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof DeleteDocumentRequest;
        }))->willThrowException(new \RuntimeException('Delete failed'));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Delete failed');
        $this->documentService->delete($datasetId, $documentId);
    }

    public function testUploadWithEmptyFiles(): void
    {
        $datasetId = 'dataset-123';
        $files = [];
        $expectedResponse = ['retcode' => 0, 'retmsg' => 'success', 'data' => []];
        $result = $this->documentService->upload($datasetId, $files);
        $this->assertEquals($expectedResponse, $result);
    }

    public function testServiceWithDifferentIds(): void
    {
        $testCases = [['dataset-simple', 'doc-simple'], ['dataset_complex-123', 'doc_complex-456'], ['dataset-with-numbers-789', 'doc-with-numbers-012']];
        $this->client->expects($this->exactly(3))->method('request')->willReturn(['success' => true]);
        foreach ($testCases as [$datasetId, $documentId]) {
            $result = $this->documentService->delete($datasetId, $documentId);
            $this->assertTrue($result);
        }
    }

    public function testParseChunks(): void
    {
        $datasetId = 'dataset-123';
        $documentIds = ['doc-1', 'doc-2', 'doc-3'];
        $parserConfig = ['layout_recognize' => true, 'table_recognize' => true];
        $expectedResponse = ['retcode' => 0, 'retmsg' => 'success', 'data' => ['chunk_count' => 45, 'status' => 'parsing', 'job_id' => 'parse-job-789']];
        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof ParseChunksRequest;
        }))->willReturn($expectedResponse);
        $result = $this->documentService->parseChunks($datasetId, $documentIds, $parserConfig);
        $this->assertEquals($expectedResponse, $result);
    }

    public function testParseChunksWithoutConfig(): void
    {
        $datasetId = 'dataset-123';
        $documentIds = ['doc-1'];
        $expectedResponse = ['retcode' => 0, 'retmsg' => 'success', 'data' => ['status' => 'parsing']];
        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof ParseChunksRequest;
        }))->willReturn($expectedResponse);
        $result = $this->documentService->parseChunks($datasetId, $documentIds);
        $this->assertEquals($expectedResponse, $result);
    }

    public function testStopParsing(): void
    {
        $datasetId = 'dataset-123';
        $documentIds = ['doc-1', 'doc-2'];
        $expectedResponse = ['retcode' => 0, 'retmsg' => 'success', 'data' => ['stopped_count' => 2, 'status' => 'stopped']];
        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof StopParsingRequest;
        }))->willReturn($expectedResponse);
        $result = $this->documentService->stopParsing($datasetId, $documentIds);
        $this->assertEquals($expectedResponse, $result);
    }

    public function testListChunks(): void
    {
        $datasetId = 'dataset-123';
        $documentId = 'doc-456';
        $keywords = 'important content';
        $page = 2;
        $pageSize = 50;
        $expectedResponse = ['chunks' => [['id' => 'chunk-1', 'content' => 'This is important content', 'page_number' => 1], ['id' => 'chunk-2', 'content' => 'Another important section', 'page_number' => 2]], 'total' => 150, 'page' => 2, 'page_size' => 50];
        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof ListChunksRequest;
        }))->willReturn($expectedResponse);
        $result = $this->documentService->listChunks($datasetId, $documentId, $keywords, $page, $pageSize);
        $this->assertEquals($expectedResponse, $result);
    }

    public function testListChunksWithDefaults(): void
    {
        $datasetId = 'dataset-123';
        $documentId = 'doc-456';
        $expectedResponse = ['chunks' => [['id' => 'chunk-1', 'content' => 'First chunk'], ['id' => 'chunk-2', 'content' => 'Second chunk']], 'total' => 2, 'page' => 1, 'page_size' => 100];
        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof ListChunksRequest;
        }))->willReturn($expectedResponse);
        $result = $this->documentService->listChunks($datasetId, $documentId);
        $this->assertEquals($expectedResponse, $result);
    }

    public function testListAll(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Test Instance');
        $dataset1 = new Dataset();
        $dataset1->setName('Dataset 1');
        $dataset1->setRemoteId('dataset-1');
        $dataset1->setRagFlowInstance($instance);
        $dataset2 = new Dataset();
        $dataset2->setName('Dataset 2');
        $dataset2->setRemoteId('dataset-2');
        $dataset2->setRagFlowInstance($instance);
        $document1 = new Document();
        $document1->setName('Document 1');
        $document1->setRemoteId('doc-1');
        $document2 = new Document();
        $document2->setName('Document 2');
        $document2->setRemoteId('doc-2');
        $this->client->expects($this->once())->method('getInstance')->willReturn($instance);
        $this->datasetRepository->expects($this->once())->method('findBy')->with(['ragFlowInstance' => $instance])->willReturn([$dataset1, $dataset2]);
        $this->datasetRepository->expects($this->once())->method('findOneBy')->willReturnCallback(function ($criteria) use ($dataset1, $dataset2) {
            if ('dataset-1' === $criteria['remoteId']) {
                return $dataset1;
            }
            if ('dataset-2' === $criteria['remoteId']) {
                return $dataset2;
            }

            return null;
        });
        $this->client->expects($this->exactly(2))->method('request')->willReturnOnConsecutiveCalls(['docs' => [['id' => 'doc-1', 'name' => 'Document 1']]], ['docs' => [['id' => 'doc-2', 'name' => 'Document 2']]]);
        $this->localDataSyncService->expects($this->exactly(2))->method('syncDocumentFromApi')->willReturnOnConsecutiveCalls($document1, $document2);
        $result = $this->documentService->listAll();
        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(Document::class, $result);
    }

    public function testListAllWithNoDatasets(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Test Instance');
        $this->client->expects($this->once())->method('getInstance')->willReturn($instance);
        $this->datasetRepository->expects($this->once())->method('findBy')->with(['ragFlowInstance' => $instance])->willReturn([]);
        $result = $this->documentService->listAll();
        $this->assertCount(0, $result);
    }
}
