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
use Tourze\RAGFlowApiBundle\Helper\Document\DocumentRetryHandler;
use Tourze\RAGFlowApiBundle\Service\DocumentService;

/**
 * @internal
 */
#[CoversClass(DocumentRetryHandler::class)]
final class DocumentRetryHandlerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;

    /** @var DocumentService&MockObject */
    private DocumentService $documentService;

    private DocumentRetryHandler $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->documentService = $this->createMock(DocumentService::class);
        $this->handler = new DocumentRetryHandler($this->entityManager, $this->documentService);
    }

    public function testShouldRetryReturnsFalseWhenUploadNotRequired(): void
    {
        $document = $this->createMock(Document::class);
        $document->expects($this->once())->method('isUploadRequired')->willReturn(false);
        $document->expects($this->once())->method('getFilePath')->willReturn('/tmp/test.txt');
        $result = $this->handler->shouldRetry($document);
        $this->assertFalse($result);
    }

    public function testShouldRetryReturnsFalseWhenFilePathIsNull(): void
    {
        $document = $this->createMock(Document::class);
        $document->expects($this->once())->method('isUploadRequired')->willReturn(true);
        $document->expects($this->once())->method('getFilePath')->willReturn(null);
        $result = $this->handler->shouldRetry($document);
        $this->assertFalse($result);
    }

    public function testShouldRetryReturnsFalseWhenFilePathIsEmpty(): void
    {
        $document = $this->createMock(Document::class);
        $document->expects($this->once())->method('isUploadRequired')->willReturn(true);
        $document->expects($this->once())->method('getFilePath')->willReturn('');
        $result = $this->handler->shouldRetry($document);
        $this->assertFalse($result);
    }

    public function testUpdateAfterRetryUpdatesDocumentWithRemoteId(): void
    {
        $document = $this->createMock(Document::class);
        $result = ['data' => [['id' => 'remote123', 'name' => 'test.pdf']]];
        $document->expects($this->once())->method('setRemoteId')->with('remote123');
        $document->expects($this->once())->method('setStatus')->with(DocumentStatus::UPLOADED);
        $document->expects($this->once())->method('setLastSyncTime')->with($this->isInstanceOf(\DateTimeImmutable::class));
        $this->entityManager->expects($this->once())->method('flush');
        $this->handler->updateAfterRetry($document, $result);
    }

    public function testUpdateAfterRetryHandlesEmptyData(): void
    {
        $document = $this->createMock(Document::class);
        $result = ['data' => []];
        $document->expects($this->never())->method('setRemoteId');
        $document->expects($this->once())->method('setStatus')->with(DocumentStatus::UPLOADED);
        $this->handler->updateAfterRetry($document, $result);
    }

    public function testUpdateAfterRetryHandlesInvalidData(): void
    {
        $document = $this->createMock(Document::class);
        $result = ['data' => [['id' => 123]]];
        $document->expects($this->never())->method('setRemoteId');
        $document->expects($this->once())->method('setStatus')->with(DocumentStatus::UPLOADED);
        $this->handler->updateAfterRetry($document, $result);
    }

    public function testHandleErrorSetsFailedStatusAndReturnsMessage(): void
    {
        $document = $this->createMock(Document::class);
        $document->expects($this->once())->method('getName')->willReturn('test.pdf');
        $exception = new \RuntimeException('Upload failed');
        $document->expects($this->once())->method('setStatus')->with(DocumentStatus::SYNC_FAILED);
        $this->entityManager->expects($this->once())->method('flush');
        $result = $this->handler->handleError($document, $exception);
        $this->assertStringContainsString('test.pdf', $result);
        $this->assertStringContainsString('Upload failed', $result);
    }
}
