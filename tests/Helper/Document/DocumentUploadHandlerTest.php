<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Helper\Document;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\Request;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Exception\DocumentOperationException;
use Tourze\RAGFlowApiBundle\Helper\Document\DocumentUploadHandler;
use Tourze\RAGFlowApiBundle\Service\DatasetDocumentManagementService;
use Tourze\RAGFlowApiBundle\Service\DocumentService;

/**
 * @internal
 */
#[CoversClass(DocumentUploadHandler::class)]
final class DocumentUploadHandlerTest extends TestCase
{
    /** @var DocumentService&MockObject */
    private DocumentService $documentService;

    /** @var DatasetDocumentManagementService&MockObject */
    private DatasetDocumentManagementService $managementService;

    private DocumentUploadHandler $handler;

    protected function setUp(): void
    {
        $this->documentService = $this->createMock(DocumentService::class);
        $this->managementService = $this->createMock(DatasetDocumentManagementService::class);
        $this->handler = new DocumentUploadHandler($this->documentService, $this->managementService);
    }

    public function testExtractFilesReturnsEmptyWhenNoFiles(): void
    {
        $request = new Request();
        $result = $this->handler->extractFiles($request);
        $this->assertSame([], $result);
    }

    public function testExtractFilesFiltersOutNonUploadedFileInstances(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $request = new Request();

        // Create a mock FileBag that returns mixed content to test filtering logic
        $request->files = $this->createMock(FileBag::class);
        $request->files->expects($this->once())
            ->method('get')
            ->with('files', [])
            ->willReturn([$file, 'not-a-file'])
        ;

        $result = $this->handler->extractFiles($request);
        $this->assertCount(1, $result);
        $this->assertSame($file, $result[0]);
    }

    public function testExtractFilesIgnoresNullEntries(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $request = new Request();

        // Create a mock FileBag that returns array with null entries to test filtering logic
        $request->files = $this->createMock(FileBag::class);
        $request->files->expects($this->once())
            ->method('get')
            ->with('files', [])
            ->willReturn([$file, null, $file])
        ;

        $result = $this->handler->extractFiles($request);
        $this->assertCount(2, $result);
    }

    public function testValidateDatasetRemoteIdThrowsWhenRemoteIdIsNull(): void
    {
        $dataset = $this->createMock(Dataset::class);
        $dataset->expects($this->once())->method('getRemoteId')->willReturn(null);
        $dataset->expects($this->once())->method('getId')->willReturn(123);
        $this->expectException(DocumentOperationException::class);
        $this->handler->validateDatasetRemoteId($dataset);
    }

    public function testValidateDatasetRemoteIdReturnsRemoteId(): void
    {
        $dataset = $this->createMock(Dataset::class);
        $dataset->expects($this->once())->method('getRemoteId')->willReturn('remote-123');
        $result = $this->handler->validateDatasetRemoteId($dataset);
        $this->assertSame('remote-123', $result);
    }

    public function testProcessUploadsHandlesInvalidFile(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->expects($this->once())->method('isValid')->willReturn(false);
        $file->expects($this->once())->method('getClientOriginalName')->willReturn('test.pdf');
        $file->expects($this->once())->method('getErrorMessage')->willReturn('File too large');
        $dataset = $this->createMock(Dataset::class);
        $result = $this->handler->processUploads($dataset, 'remote-123', [$file]);
        $this->assertSame(0, $result['uploaded_count']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('test.pdf', $result['errors'][0]);
        $this->assertStringContainsString('File too large', $result['errors'][0]);
    }

    public function testProcessUploadsHandlesUploadException(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->expects($this->once())->method('isValid')->willReturn(true);
        $file->expects($this->once())->method('getPathname')->willReturn('/tmp/upload123');
        $file->expects($this->exactly(2))->method('getClientOriginalName')->willReturn('test.pdf');
        $dataset = $this->createMock(Dataset::class);
        $this->documentService->expects($this->once())->method('upload')->willThrowException(new \RuntimeException('Network error'));
        $result = $this->handler->processUploads($dataset, 'remote-123', [$file]);
        $this->assertSame(0, $result['uploaded_count']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('test.pdf', $result['errors'][0]);
        $this->assertStringContainsString('Network error', $result['errors'][0]);
    }
}
