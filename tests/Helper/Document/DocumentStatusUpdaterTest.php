<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Helper\Document;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Enum\DocumentStatus;
use Tourze\RAGFlowApiBundle\Helper\Document\DocumentStatusUpdater;
use Tourze\RAGFlowApiBundle\Service\DatasetDocumentManagementService;
use Tourze\RAGFlowApiBundle\Service\DocumentService;

/**
 * @internal
 */
#[CoversClass(DocumentStatusUpdater::class)]
final class DocumentStatusUpdaterTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;

    /** @var DocumentService&MockObject */
    private DocumentService $documentService;

    /** @var DatasetDocumentManagementService&MockObject */
    private DatasetDocumentManagementService $managementService;

    private DocumentStatusUpdater $updater;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->documentService = $this->createMock(DocumentService::class);
        $this->managementService = $this->createMock(DatasetDocumentManagementService::class);
        $this->updater = new DocumentStatusUpdater($this->entityManager, $this->documentService, $this->managementService);
    }

    public function testReparseReturnsErrorWhenRemoteIdIsNull(): void
    {
        $document = $this->createMock(Document::class);
        $document->expects($this->once())->method('getRemoteId')->willReturn(null);
        $dataset = $this->createMock(Dataset::class);
        $result = $this->updater->reparse($document, $dataset);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('尚未上传', $result['message']);
    }

    public function testReparseReturnsErrorWhenRemoteIdIsEmpty(): void
    {
        $document = $this->createMock(Document::class);
        $document->expects($this->once())->method('getRemoteId')->willReturn('');
        $dataset = $this->createMock(Dataset::class);
        $result = $this->updater->reparse($document, $dataset);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('尚未上传', $result['message']);
    }

    public function testReparseSuccessfullyStartsParsing(): void
    {
        $document = $this->createMock(Document::class);
        $document->expects($this->once())->method('getRemoteId')->willReturn('doc123');
        $document->expects($this->once())->method('getName')->willReturn('test.pdf');
        $dataset = $this->createMock(Dataset::class);
        $dataset->expects($this->once())->method('getRemoteId')->willReturn('dataset456');
        $this->documentService->expects($this->once())->method('parseChunks')->with('dataset456', ['doc123'])->willReturn(['task_id' => 'task789']);
        $document->expects($this->once())->method('setStatus')->with(DocumentStatus::PROCESSING);
        $document->expects($this->once())->method('setProgress')->with(0.0);
        $document->expects($this->once())->method('setProgressMsg')->with('重新解析中...');
        $this->entityManager->expects($this->once())->method('flush');
        $result = $this->updater->reparse($document, $dataset);
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('test.pdf', $result['message']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testStopParsingReturnsErrorWhenRemoteIdIsNull(): void
    {
        $document = $this->createMock(Document::class);
        $document->expects($this->once())->method('getRemoteId')->willReturn(null);
        $dataset = $this->createMock(Dataset::class);
        $result = $this->updater->stopParsing($document, $dataset);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('尚未上传', $result['message']);
    }

    public function testStopParsingSuccessfullyStopsParsing(): void
    {
        $document = $this->createMock(Document::class);
        $document->expects($this->once())->method('getRemoteId')->willReturn('doc123');
        $document->expects($this->once())->method('getName')->willReturn('test.pdf');
        $dataset = $this->createMock(Dataset::class);
        $dataset->expects($this->once())->method('getRemoteId')->willReturn('dataset456');
        $this->documentService->expects($this->once())->method('stopParsing')->with('dataset456', ['doc123'])->willReturn(['status' => 'stopped']);
        $document->expects($this->once())->method('setStatus')->with(DocumentStatus::PENDING);
        $document->expects($this->once())->method('setProgress')->with(null);
        $document->expects($this->once())->method('setProgressMsg')->with('解析已停止');
        $this->entityManager->expects($this->once())->method('flush');
        $result = $this->updater->stopParsing($document, $dataset);
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('test.pdf', $result['message']);
    }

    public function testUpdateFromApiUpdatesProgressFields(): void
    {
        $document = $this->createMock(Document::class);
        $document->expects($this->once())->method('getRemoteId')->willReturn('doc123');
        $dataset = $this->createMock(Dataset::class);
        $dataset->expects($this->once())->method('getRemoteId')->willReturn('dataset456');
        $this->managementService->expects($this->once())->method('getParseStatus')->with($dataset, 'doc123')->willReturn(['progress' => 0.75, 'progress_msg' => 'Processing...', 'chunk_num' => 42]);
        $document->expects($this->once())->method('setProgress')->with(75.0);
        $document->expects($this->once())->method('setProgressMsg')->with('Processing...');
        $document->expects($this->once())->method('setChunkCount')->with(42);
        $this->entityManager->expects($this->once())->method('flush');
        $this->updater->updateFromApi($document, $dataset);
    }

    public function testUpdateFromApiHandlesNullRemoteIds(): void
    {
        $document = $this->createMock(Document::class);
        $document->expects($this->once())->method('getRemoteId')->willReturn(null);
        $dataset = $this->createMock(Dataset::class);
        $this->managementService->expects($this->never())->method('getParseStatus');
        $this->updater->updateFromApi($document, $dataset);
    }

    public function testUpdateFromApiSilentlyHandlesException(): void
    {
        $document = $this->createMock(Document::class);
        $document->expects($this->once())->method('getRemoteId')->willReturn('doc123');
        $dataset = $this->createMock(Dataset::class);
        $dataset->expects($this->once())->method('getRemoteId')->willReturn('dataset456');
        $this->managementService->expects($this->once())->method('getParseStatus')->willThrowException(new \RuntimeException('API error'));
        $this->updater->updateFromApi($document, $dataset);
        // 无意义的断言已移除
    }
}
