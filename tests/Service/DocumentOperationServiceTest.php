<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Enum\DocumentStatus;
use Tourze\RAGFlowApiBundle\Service\DocumentOperationService;

/**
 * @internal
 */
#[CoversClass(DocumentOperationService::class)]
#[RunTestsInSeparateProcesses]
class DocumentOperationServiceTest extends AbstractIntegrationTestCase
{
    private DocumentOperationService $documentOperationService;

    private RAGFlowInstance $testInstance;

    protected function onSetUp(): void
    {
        $this->documentOperationService = self::getService(DocumentOperationService::class);

        // 创建测试用的 RAGFlowInstance
        $this->testInstance = new RAGFlowInstance();
        $this->testInstance->setName('test-instance-' . uniqid());
        $this->testInstance->setApiUrl('https://test.example.com/api');
        $this->testInstance->setApiKey('test-key');
        $this->testInstance->setEnabled(true);
        $this->persistAndFlush($this->testInstance);
    }

    public function testServiceCreation(): void
    {
        $this->assertInstanceOf(DocumentOperationService::class, $this->documentOperationService);
    }

    public function testRetryFailedDocuments(): void
    {
        // 创建测试数据集
        $dataset = new Dataset();
        $dataset->setRagFlowInstance($this->testInstance);
        $dataset->setName('重试测试数据集');
        $dataset->setDescription('用于测试重试功能的数据集');
        $dataset->setRemoteId('retry-dataset-123');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 创建失败的文档
        $failedDoc1 = new Document();
        $failedDoc1->setName('失败文档1.pdf');
        $failedDoc1->setDataset($persistedDataset);
        $failedDoc1->setStatus(DocumentStatus::SYNC_FAILED);
        $failedDoc1->setFilePath('/tmp/test-file1.pdf');
        $this->persistAndFlush($failedDoc1);

        $failedDoc2 = new Document();
        $failedDoc2->setName('失败文档2.docx');
        $failedDoc2->setDataset($persistedDataset);
        $failedDoc2->setStatus(DocumentStatus::FAILED);
        $failedDoc2->setFilePath('/tmp/test-file2.docx');
        $this->persistAndFlush($failedDoc2);

        // 由于没有真实的API，这个测试会因为文件不存在而产生异常
        $result = $this->documentOperationService->retryFailedDocuments($persistedDataset);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('retry_count', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertIsInt($result['retry_count']);
        $this->assertIsArray($result['errors']);
    }

    public function testPrepareDocumentListData(): void
    {
        // 创建测试数据集
        $dataset = new Dataset();
        $dataset->setRagFlowInstance($this->testInstance);
        $dataset->setName('列表测试数据集');
        $dataset->setDescription('用于测试列表功能的数据集');
        $dataset->setRemoteId('list-dataset-456');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        $request = new Request();
        $result = $this->documentOperationService->prepareDocumentListData($persistedDataset, $request);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('dataset', $result);
        $this->assertArrayHasKey('flash_messages', $result);
        $this->assertSame($persistedDataset, $result['dataset']);
        $this->assertIsArray($result['flash_messages']);
    }

    public function testBatchDeleteDocuments(): void
    {
        // 创建测试数据集
        $dataset = new Dataset();
        $dataset->setRagFlowInstance($this->testInstance);
        $dataset->setName('批量删除测试数据集');
        $dataset->setDescription('用于测试批量删除的数据集');
        $dataset->setRemoteId('batch-delete-dataset-789');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 创建测试文档
        $doc1 = new Document();
        $doc1->setName('待删除文档1.pdf');
        $doc1->setDataset($persistedDataset);
        $doc1->setStatus(DocumentStatus::COMPLETED);
        $persistedDoc1 = $this->persistAndFlush($doc1);

        $doc2 = new Document();
        $doc2->setName('待删除文档2.docx');
        $doc2->setDataset($persistedDataset);
        $doc2->setStatus(DocumentStatus::UPLOADED);
        $persistedDoc2 = $this->persistAndFlush($doc2);

        $documentIds = [$persistedDoc1->getId(), $persistedDoc2->getId()];

        $result = $this->documentOperationService->batchDeleteDocuments($persistedDataset, $documentIds);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('deleted_count', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertIsInt($result['deleted_count']);
        $this->assertIsArray($result['errors']);
    }

    public function testRetryDocumentUpload(): void
    {
        // 创建测试数据集
        $dataset = new Dataset();
        $dataset->setRagFlowInstance($this->testInstance);
        $dataset->setName('单个重试测试数据集');
        $dataset->setDescription('用于测试单个文档重试的数据集');
        $dataset->setRemoteId('single-retry-dataset-999');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 创建需要重试的文档（文档不存在的情况）
        $document = new Document();
        $document->setName('重试文档.pdf');
        $document->setDataset($persistedDataset);
        $document->setStatus(DocumentStatus::SYNC_FAILED);
        $document->setFilePath('/tmp/retry-test-file-not-exists.pdf');
        $persistedDocumentResult = $this->persistAndFlush($document);
        $this->assertInstanceOf(Document::class, $persistedDocumentResult);
        /** @var Document $persistedDocument */
        $persistedDocument = $persistedDocumentResult;

        $result = $this->documentOperationService->retryDocumentUpload($persistedDocument, $persistedDataset);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertIsBool($result['success']);
        $this->assertIsString($result['message']);

        // 文件不存在时，返回 success=false 但不是异常，不应有 error 键
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('不需要重试或文件不存在', $result['message']);
        $this->assertArrayNotHasKey('error', $result);
    }

    public function testReparseDocument(): void
    {
        // 创建测试数据集
        $dataset = new Dataset();
        $dataset->setRagFlowInstance($this->testInstance);
        $dataset->setName('重新解析测试数据集');
        $dataset->setDescription('用于测试重新解析的数据集');
        $dataset->setRemoteId('reparse-dataset-111');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 创建已上传的文档
        $document = new Document();
        $document->setName('重新解析文档.pdf');
        $document->setDataset($persistedDataset);
        $document->setStatus(DocumentStatus::UPLOADED);
        $document->setRemoteId('remote-doc-123');
        $persistedDocumentResult = $this->persistAndFlush($document);
        $this->assertInstanceOf(Document::class, $persistedDocumentResult);
        /** @var Document $persistedDocument */
        $persistedDocument = $persistedDocumentResult;

        $result = $this->documentOperationService->reparseDocument($persistedDocument, $persistedDataset);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertIsBool($result['success']);
        $this->assertIsString($result['message']);

        if (!$result['success']) {
            $this->assertArrayHasKey('error', $result);
        }
    }

    public function testStopDocumentParsing(): void
    {
        // 创建测试数据集
        $dataset = new Dataset();
        $dataset->setRagFlowInstance($this->testInstance);
        $dataset->setName('停止解析测试数据集');
        $dataset->setDescription('用于测试停止解析的数据集');
        $dataset->setRemoteId('stop-parsing-dataset-222');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 创建正在解析的文档
        $document = new Document();
        $document->setName('停止解析文档.pdf');
        $document->setDataset($persistedDataset);
        $document->setStatus(DocumentStatus::PROCESSING);
        $document->setRemoteId('remote-doc-456');
        $persistedDocumentResult = $this->persistAndFlush($document);
        $this->assertInstanceOf(Document::class, $persistedDocumentResult);
        /** @var Document $persistedDocument */
        $persistedDocument = $persistedDocumentResult;

        $result = $this->documentOperationService->stopDocumentParsing($persistedDocument, $persistedDataset);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertIsBool($result['success']);
        $this->assertIsString($result['message']);

        if (!$result['success']) {
            $this->assertArrayHasKey('error', $result);
        }
    }

    public function testGetDatasetDocumentStats(): void
    {
        // 创建测试数据集
        $dataset = new Dataset();
        $dataset->setRagFlowInstance($this->testInstance);
        $dataset->setName('统计测试数据集');
        $dataset->setDescription('用于测试统计功能的数据集');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 创建不同状态的文档
        $statuses = [
            DocumentStatus::PENDING,
            DocumentStatus::UPLOADING,
            DocumentStatus::UPLOADED,
            DocumentStatus::PROCESSING,
            DocumentStatus::COMPLETED,
            DocumentStatus::FAILED,
        ];

        foreach ($statuses as $i => $status) {
            $document = new Document();
            $document->setName("统计文档{$i}.pdf");
            $document->setDataset($persistedDataset);
            $document->setStatus($status);
            $this->persistAndFlush($document);
        }

        $stats = $this->documentOperationService->getDatasetDocumentStats($persistedDataset);

        $this->assertIsArray($stats);
        // 验证统计数据的结构（具体键值取决于实现）
    }

    public function testHandleFileUploadWithValidFiles(): void
    {
        // 创建测试数据集
        $dataset = new Dataset();
        $dataset->setRagFlowInstance($this->testInstance);
        $dataset->setName('文件上传测试数据集');
        $dataset->setDescription('用于测试文件上传的数据集');
        $dataset->setRemoteId('upload-dataset-333');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 创建模拟的上传文件
        $tempFile1 = tempnam(sys_get_temp_dir(), 'test1');
        file_put_contents($tempFile1, 'Test content 1');
        $uploadedFile1 = new UploadedFile($tempFile1, 'test1.pdf', 'application/pdf', null, true);

        $tempFile2 = tempnam(sys_get_temp_dir(), 'test2');
        file_put_contents($tempFile2, 'Test content 2');
        $uploadedFile2 = new UploadedFile($tempFile2, 'test2.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

        $request = new Request();
        $request->files->set('files', [$uploadedFile1, $uploadedFile2]);

        $result = $this->documentOperationService->handleFileUpload($persistedDataset, $request);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('uploaded_count', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertIsInt($result['uploaded_count']);
        $this->assertIsArray($result['errors']);

        // 清理临时文件
        @unlink($tempFile1);
        @unlink($tempFile2);
    }

    public function testHandleFileUploadWithNoFiles(): void
    {
        // 创建测试数据集
        $dataset = new Dataset();
        $dataset->setRagFlowInstance($this->testInstance);
        $dataset->setName('空文件上传测试数据集');
        $dataset->setDescription('用于测试空文件上传的数据集');
        $dataset->setRemoteId('empty-upload-dataset-444');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        $request = new Request();
        // 不设置任何文件

        $result = $this->documentOperationService->handleFileUpload($persistedDataset, $request);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('uploaded_count', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertEquals(0, $result['uploaded_count']);
        $this->assertNotEmpty($result['errors']);
        $this->assertContains('未选择要上传的文件', $result['errors']);
    }

    public function testDeleteDocument(): void
    {
        // 创建测试数据集和文档
        $dataset = new Dataset();
        $dataset->setRagFlowInstance($this->testInstance);
        $dataset->setName('删除测试数据集');
        $dataset->setDescription('用于测试文档删除的数据集');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        $document = new Document();
        $document->setName('待删除文档.pdf');
        $document->setDataset($persistedDataset);
        $document->setStatus(DocumentStatus::COMPLETED);
        $persistedDocumentResult = $this->persistAndFlush($document);
        $this->assertInstanceOf(Document::class, $persistedDocumentResult);
        /** @var Document $persistedDocument */
        $persistedDocument = $persistedDocumentResult;

        // 测试删除操作
        $this->documentOperationService->deleteDocument($persistedDocument);

        // 由于使用了管理服务，这里只是确保方法能正常调用
        // 无意义的断言已移除
    }

    public function testUpdateDocumentStatusFromApi(): void
    {
        // 创建测试数据集
        $dataset = new Dataset();
        $dataset->setRagFlowInstance($this->testInstance);
        $dataset->setName('状态更新测试数据集');
        $dataset->setDescription('用于测试状态更新的数据集');
        $dataset->setRemoteId('status-update-dataset-555');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 创建文档
        $document = new Document();
        $document->setName('状态更新文档.pdf');
        $document->setDataset($persistedDataset);
        $document->setStatus(DocumentStatus::PROCESSING);
        $document->setRemoteId('remote-doc-789');
        $persistedDocumentResult = $this->persistAndFlush($document);
        $this->assertInstanceOf(Document::class, $persistedDocumentResult);
        /** @var Document $persistedDocument */
        $persistedDocument = $persistedDocumentResult;

        // 测试状态更新
        $this->documentOperationService->updateDocumentStatusFromApi($persistedDocument, $persistedDataset);

        // 由于这个方法不返回值，我们只是确保它能正常执行
        // 无意义的断言已移除
    }

    public function testErrorHandlingInRetryOperations(): void
    {
        // 创建测试数据集
        $dataset = new Dataset();
        $dataset->setRagFlowInstance($this->testInstance);
        $dataset->setName('错误处理测试数据集');
        $dataset->setDescription('用于测试错误处理的数据集');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 创建没有文件路径的失败文档
        $document = new Document();
        $document->setName('错误文档.pdf');
        $document->setDataset($persistedDataset);
        $document->setStatus(DocumentStatus::SYNC_FAILED);
        // 故意不设置文件路径
        $persistedDocumentResult = $this->persistAndFlush($document);
        $this->assertInstanceOf(Document::class, $persistedDocumentResult);
        /** @var Document $persistedDocument */
        $persistedDocument = $persistedDocumentResult;

        $result = $this->documentOperationService->retryDocumentUpload($persistedDocument, $persistedDataset);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('不需要重试或文件不存在', $result['message']);
    }

    public function testReparseDocumentWithoutRemoteId(): void
    {
        // 创建测试数据集
        $dataset = new Dataset();
        $dataset->setRagFlowInstance($this->testInstance);
        $dataset->setName('重解析错误测试数据集');
        $dataset->setDescription('用于测试重解析错误的数据集');
        $dataset->setRemoteId('reparse-error-dataset-666');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 创建没有远程ID的文档
        $document = new Document();
        $document->setName('无远程ID文档.pdf');
        $document->setDataset($persistedDataset);
        $document->setStatus(DocumentStatus::UPLOADED);
        // 故意不设置远程ID
        $persistedDocumentResult = $this->persistAndFlush($document);
        $this->assertInstanceOf(Document::class, $persistedDocumentResult);
        /** @var Document $persistedDocument */
        $persistedDocument = $persistedDocumentResult;

        $result = $this->documentOperationService->reparseDocument($persistedDocument, $persistedDataset);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('尚未上传，无法解析', $result['message']);
    }
}
