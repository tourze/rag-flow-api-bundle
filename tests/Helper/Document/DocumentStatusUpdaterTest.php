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
use Tourze\RAGFlowApiBundle\Helper\Document\DocumentStatusUpdater;
use Tourze\RAGFlowApiBundle\Service\DatasetDocumentManagementService;
use Tourze\RAGFlowApiBundle\Service\DocumentService;

/**
 * @internal
 */
#[CoversClass(DocumentStatusUpdater::class)]
#[RunTestsInSeparateProcesses]
final class DocumentStatusUpdaterTest extends AbstractIntegrationTestCase
{
    private DocumentService $documentService;
    private DatasetDocumentManagementService $managementService;
    private DocumentStatusUpdater $updater;
    private RAGFlowInstance $instance;
    private Dataset $dataset;

    protected function onSetUp(): void
    {
        // 创建真实的 RAGFlowInstance（使用唯一名称避免冲突）
        $this->instance = new RAGFlowInstance();
        $this->instance->setName('Test Instance ' . uniqid());
        $this->instance->setApiUrl('https://test.example.com/api');
        $this->instance->setApiKey('test-key');
        $this->persistAndFlush($this->instance);

        // 创建真实的 Dataset（使用唯一名称避免冲突）
        $this->dataset = new Dataset();
        $this->dataset->setName('Test Dataset ' . uniqid());
        $this->dataset->setRagFlowInstance($this->instance);
        $this->dataset->setRemoteId('dataset456');
        $this->persistAndFlush($this->dataset);

        // Mock 网络相关服务
        $this->documentService = $this->createMock(DocumentService::class);
        $this->managementService = $this->createMock(DatasetDocumentManagementService::class);

        // 将 Mock 服务注入到容器中
        self::getContainer()->set(DocumentService::class, $this->documentService);
        self::getContainer()->set(DatasetDocumentManagementService::class, $this->managementService);

        // 从服务容器获取 Updater，这会使用我们注入的 Mock 服务
        $this->updater = self::getService(DocumentStatusUpdater::class);
    }

    public function testReparseReturnsErrorWhenRemoteIdIsNull(): void
    {
        $document = new Document();
        $document->setName('test-doc.pdf');
        $document->setDataset($this->dataset);
        $document->setRemoteId(null);
        $this->persistAndFlush($document);

        $result = $this->updater->reparse($document, $this->dataset);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('尚未上传', $result['message']);
    }

    public function testReparseReturnsErrorWhenRemoteIdIsEmpty(): void
    {
        $document = new Document();
        $document->setName('test-doc.pdf');
        $document->setDataset($this->dataset);
        $document->setRemoteId('');
        $this->persistAndFlush($document);

        $result = $this->updater->reparse($document, $this->dataset);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('尚未上传', $result['message']);
    }

    public function testReparseSuccessfullyStartsParsing(): void
    {
        $document = new Document();
        $document->setName('test.pdf');
        $document->setDataset($this->dataset);
        $document->setRemoteId('doc123');
        $this->persistAndFlush($document);

        $this->documentService->expects($this->once())
            ->method('parseChunks')
            ->with('dataset456', ['doc123'])
            ->willReturn(['task_id' => 'task789']);

        $result = $this->updater->reparse($document, $this->dataset);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('test.pdf', $result['message']);
        $this->assertArrayHasKey('data', $result);

        // 验证数据库中的状态更新
        self::getEntityManager()->clear();
        $refreshedDocument = self::getEntityManager()->find(Document::class, $document->getId());
        $this->assertSame(DocumentStatus::PROCESSING, $refreshedDocument->getStatus());
        $this->assertSame(0.0, $refreshedDocument->getProgress());
        $this->assertSame('重新解析中...', $refreshedDocument->getProgressMsg());
    }

    public function testStopParsingReturnsErrorWhenRemoteIdIsNull(): void
    {
        $document = new Document();
        $document->setName('test-doc.pdf');
        $document->setDataset($this->dataset);
        $document->setRemoteId(null);
        $this->persistAndFlush($document);

        $result = $this->updater->stopParsing($document, $this->dataset);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('尚未上传', $result['message']);
    }

    public function testStopParsingSuccessfullyStopsParsing(): void
    {
        $document = new Document();
        $document->setName('test.pdf');
        $document->setDataset($this->dataset);
        $document->setRemoteId('doc123');
        $this->persistAndFlush($document);

        $this->documentService->expects($this->once())
            ->method('stopParsing')
            ->with('dataset456', ['doc123'])
            ->willReturn(['status' => 'stopped']);

        $result = $this->updater->stopParsing($document, $this->dataset);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('test.pdf', $result['message']);

        // 验证数据库中的状态更新
        self::getEntityManager()->clear();
        $refreshedDocument = self::getEntityManager()->find(Document::class, $document->getId());
        $this->assertSame(DocumentStatus::PENDING, $refreshedDocument->getStatus());
        $this->assertNull($refreshedDocument->getProgress());
        $this->assertSame('解析已停止', $refreshedDocument->getProgressMsg());
    }

    public function testUpdateFromApiUpdatesProgressFields(): void
    {
        $document = new Document();
        $document->setName('test.pdf');
        $document->setDataset($this->dataset);
        $document->setRemoteId('doc123');
        $this->persistAndFlush($document);

        $this->managementService->expects($this->once())
            ->method('getParseStatus')
            ->with($this->dataset, 'doc123')
            ->willReturn(['progress' => 0.75, 'progress_msg' => 'Processing...', 'chunk_num' => 42]);

        $this->updater->updateFromApi($document, $this->dataset);

        // 验证数据库中的更新
        self::getEntityManager()->clear();
        $refreshedDocument = self::getEntityManager()->find(Document::class, $document->getId());
        $this->assertSame(75.0, $refreshedDocument->getProgress());
        $this->assertSame('Processing...', $refreshedDocument->getProgressMsg());
        $this->assertSame(42, $refreshedDocument->getChunkCount());
    }

    public function testUpdateFromApiHandlesNullRemoteIds(): void
    {
        $document = new Document();
        $document->setName('test.pdf');
        $document->setDataset($this->dataset);
        $document->setRemoteId(null);
        $this->persistAndFlush($document);

        $this->managementService->expects($this->never())->method('getParseStatus');
        $this->updater->updateFromApi($document, $this->dataset);

        // 验证没有异常抛出，方法正常返回
        $this->assertTrue(true);
    }

    public function testUpdateFromApiSilentlyHandlesException(): void
    {
        $document = new Document();
        $document->setName('test.pdf');
        $document->setDataset($this->dataset);
        $document->setRemoteId('doc123');
        $this->persistAndFlush($document);

        $this->managementService->expects($this->once())
            ->method('getParseStatus')
            ->willThrowException(new \RuntimeException('API error'));

        $this->updater->updateFromApi($document, $this->dataset);

        // 验证异常被静默处理，没有抛出
        $this->assertTrue(true);
    }
}
