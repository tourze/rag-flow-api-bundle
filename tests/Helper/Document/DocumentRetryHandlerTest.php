<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Helper\Document;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Enum\DocumentStatus;
use Tourze\RAGFlowApiBundle\Helper\Document\DocumentRetryHandler;
use Tourze\RAGFlowApiBundle\Service\DocumentService;

/**
 * @internal
 */
#[CoversClass(DocumentRetryHandler::class)]
#[RunTestsInSeparateProcesses]
final class DocumentRetryHandlerTest extends AbstractIntegrationTestCase
{
    private DocumentRetryHandler $handler;
    private DocumentService $documentService;
    private Dataset $dataset;
    private RAGFlowInstance $instance;

    protected function onSetUp(): void
    {
        // 创建 Mock 的 DocumentService（网络请求相关）
        $this->documentService = $this->createMock(DocumentService::class);

        // 将 Mock 服务注入到容器中
        self::getContainer()->set(DocumentService::class, $this->documentService);

        // 从服务容器获取 Handler，这会使用我们注入的 Mock DocumentService
        $this->handler = self::getService(DocumentRetryHandler::class);

        // 创建测试所需的基础实体（使用唯一名称避免冲突）
        $this->instance = new RAGFlowInstance();
        $this->instance->setName('test-instance-' . uniqid());
        $this->instance->setApiUrl('https://test.example.com');
        $this->instance->setApiKey('test-key');
        $this->persistAndFlush($this->instance);

        $this->dataset = new Dataset();
        $this->dataset->setName('test-dataset-' . uniqid());
        $this->dataset->setRagFlowInstance($this->instance);
        $this->dataset->setRemoteId('dataset-123');
        $this->persistAndFlush($this->dataset);
    }

    public function testProcessRetry(): void
    {
        // 创建临时测试文件
        $tempFile = sys_get_temp_dir() . '/test_retry_' . uniqid() . '.txt';
        file_put_contents($tempFile, 'test content');

        try {
            // 创建真实的 Document
            $document = new Document();
            $document->setName('test.txt');
            $document->setFilePath($tempFile);
            $document->setDataset($this->dataset);
            $document->setStatus(DocumentStatus::SYNC_FAILED);
            $this->persistAndFlush($document);

            // Mock DocumentService 的 upload 方法（网络相关）
            $uploadResult = ['data' => [['id' => 'remote123', 'name' => 'test.txt']]];
            $this->documentService->expects($this->once())
                ->method('upload')
                ->with('dataset-123', ['file' => $tempFile], ['file' => 'test.txt'])
                ->willReturn($uploadResult);

            $this->handler->processRetry($document, $this->dataset);

            // 验证文档状态已更新
            self::getEntityManager()->refresh($document);
            $this->assertSame(DocumentStatus::UPLOADED, $document->getStatus());
            $this->assertSame('remote123', $document->getRemoteId());
            $this->assertInstanceOf(\DateTimeImmutable::class, $document->getLastSyncTime());
        } finally {
            @unlink($tempFile);
        }
    }

    public function testShouldRetryReturnsFalseWhenUploadNotRequired(): void
    {
        // 创建一个已经有 remoteId 的文档（不需要上传）
        $document = new Document();
        $document->setName('test.txt');
        $document->setFilePath('/tmp/test.txt');
        $document->setRemoteId('already-uploaded');
        $document->setStatus(DocumentStatus::UPLOADED);

        $result = $this->handler->shouldRetry($document);
        $this->assertFalse($result);
    }

    public function testShouldRetryReturnsFalseWhenFilePathIsNull(): void
    {
        // 创建一个需要上传但文件路径为 null 的文档
        $document = new Document();
        $document->setName('test.txt');
        $document->setFilePath(null);
        $document->setStatus(DocumentStatus::SYNC_FAILED);

        $result = $this->handler->shouldRetry($document);
        $this->assertFalse($result);
    }

    public function testShouldRetryReturnsFalseWhenFilePathIsEmpty(): void
    {
        // 创建一个需要上传但文件路径为空的文档
        $document = new Document();
        $document->setName('test.txt');
        $document->setFilePath('');
        $document->setStatus(DocumentStatus::SYNC_FAILED);

        $result = $this->handler->shouldRetry($document);
        $this->assertFalse($result);
    }

    public function testUpdateAfterRetryUpdatesDocumentWithRemoteId(): void
    {
        // 创建真实的 Document
        $document = new Document();
        $document->setName('test.pdf');
        $document->setDataset($this->dataset);
        $document->setStatus(DocumentStatus::UPLOADING);
        $this->persistAndFlush($document);

        $result = ['data' => [['id' => 'remote123', 'name' => 'test.pdf']]];

        $this->handler->updateAfterRetry($document, $result);

        // 验证文档状态已更新
        self::getEntityManager()->refresh($document);
        $this->assertSame('remote123', $document->getRemoteId());
        $this->assertSame(DocumentStatus::UPLOADED, $document->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $document->getLastSyncTime());
    }

    public function testUpdateAfterRetryHandlesEmptyData(): void
    {
        // 创建真实的 Document
        $document = new Document();
        $document->setName('test.pdf');
        $document->setDataset($this->dataset);
        $document->setStatus(DocumentStatus::UPLOADING);
        $this->persistAndFlush($document);

        $result = ['data' => []];

        $this->handler->updateAfterRetry($document, $result);

        // 验证文档状态已更新但没有设置 remoteId
        self::getEntityManager()->refresh($document);
        $this->assertNull($document->getRemoteId());
        $this->assertSame(DocumentStatus::UPLOADED, $document->getStatus());
    }

    public function testUpdateAfterRetryHandlesInvalidData(): void
    {
        // 创建真实的 Document
        $document = new Document();
        $document->setName('test.pdf');
        $document->setDataset($this->dataset);
        $document->setStatus(DocumentStatus::UPLOADING);
        $this->persistAndFlush($document);

        $result = ['data' => [['id' => 123]]];

        $this->handler->updateAfterRetry($document, $result);

        // 验证文档状态已更新但没有设置 remoteId（因为 id 不是字符串）
        self::getEntityManager()->refresh($document);
        $this->assertNull($document->getRemoteId());
        $this->assertSame(DocumentStatus::UPLOADED, $document->getStatus());
    }

    public function testHandleErrorSetsFailedStatusAndReturnsMessage(): void
    {
        // 创建真实的 Document
        $document = new Document();
        $document->setName('test.pdf');
        $document->setDataset($this->dataset);
        $document->setStatus(DocumentStatus::UPLOADING);
        $this->persistAndFlush($document);

        $exception = new \RuntimeException('Upload failed');

        $result = $this->handler->handleError($document, $exception);

        // 验证文档状态已更新
        self::getEntityManager()->refresh($document);
        $this->assertSame(DocumentStatus::SYNC_FAILED, $document->getStatus());

        // 验证返回的错误消息
        $this->assertStringContainsString('test.pdf', $result);
        $this->assertStringContainsString('Upload failed', $result);
    }
}
