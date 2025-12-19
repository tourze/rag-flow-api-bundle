<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Helper\Document;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\Request;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Exception\DocumentOperationException;
use Tourze\RAGFlowApiBundle\Helper\Document\DocumentUploadHandler;
use Tourze\RAGFlowApiBundle\Service\DatasetDocumentManagementService;
use Tourze\RAGFlowApiBundle\Service\DocumentService;

/**
 * @internal
 */
#[CoversClass(DocumentUploadHandler::class)]
#[RunTestsInSeparateProcesses]
class DocumentUploadHandlerTest extends AbstractIntegrationTestCase
{
    private DocumentUploadHandler $handler;

    protected function onSetUp(): void
    {
        $this->handler = self::getService(DocumentUploadHandler::class);
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
        // Create real entities
        $instance = new RAGFlowInstance();
        $instance->setName('Test Instance');
        $instance->setApiUrl('https://test.ragflow.io');
        $instance->setApiKey('test-key');
        $this->persistAndFlush($instance);

        $dataset = new Dataset();
        $dataset->setName('Test Dataset');
        $dataset->setRagFlowInstance($instance);
        $dataset->setRemoteId(null);
        $this->persistAndFlush($dataset);

        $this->expectException(DocumentOperationException::class);
        $this->handler->validateDatasetRemoteId($dataset);
    }

    public function testValidateDatasetRemoteIdReturnsRemoteId(): void
    {
        // Create real entities
        $instance = new RAGFlowInstance();
        $instance->setName('Test Instance');
        $instance->setApiUrl('https://test.ragflow.io');
        $instance->setApiKey('test-key');
        $this->persistAndFlush($instance);

        $dataset = new Dataset();
        $dataset->setName('Test Dataset');
        $dataset->setRagFlowInstance($instance);
        $dataset->setRemoteId('remote-123');
        $this->persistAndFlush($dataset);

        $result = $this->handler->validateDatasetRemoteId($dataset);
        $this->assertSame('remote-123', $result);
    }

    public function testProcessUploadsHandlesInvalidFile(): void
    {
        // Create real entities
        $instance = new RAGFlowInstance();
        $instance->setName('Test Instance');
        $instance->setApiUrl('https://test.ragflow.io');
        $instance->setApiKey('test-key');
        $this->persistAndFlush($instance);

        $dataset = new Dataset();
        $dataset->setName('Test Dataset');
        $dataset->setRagFlowInstance($instance);
        $this->persistAndFlush($dataset);

        // Mock the uploaded file
        $file = $this->createMock(UploadedFile::class);
        $file->expects($this->once())->method('isValid')->willReturn(false);
        $file->expects($this->once())->method('getClientOriginalName')->willReturn('test.pdf');
        $file->expects($this->once())->method('getErrorMessage')->willReturn('File too large');

        $result = $this->handler->processUploads($dataset, 'remote-123', [$file]);
        $this->assertSame(0, $result['uploaded_count']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('test.pdf', $result['errors'][0]);
        $this->assertStringContainsString('File too large', $result['errors'][0]);
    }

    public function testProcessUploadsHandlesUploadException(): void
    {
        // Create real entities
        $instance = new RAGFlowInstance();
        $instance->setName('Test Instance');
        $instance->setApiUrl('https://test.ragflow.io');
        $instance->setApiKey('test-key');
        $this->persistAndFlush($instance);

        $dataset = new Dataset();
        $dataset->setName('Test Dataset');
        $dataset->setRagFlowInstance($instance);
        $this->persistAndFlush($dataset);

        // Mock the uploaded file
        $file = $this->createMock(UploadedFile::class);
        $file->expects($this->once())->method('isValid')->willReturn(true);
        $file->expects($this->once())->method('getPathname')->willReturn('/tmp/upload123');
        $file->expects($this->exactly(2))->method('getClientOriginalName')->willReturn('test.pdf');

        // Get real DocumentService and expect it to throw exception
        $documentService = self::getService(DocumentService::class);

        $result = $this->handler->processUploads($dataset, 'remote-123', [$file]);
        // Note: The real service will handle the exception, so we expect 0 uploaded and errors
        $this->assertSame(0, $result['uploaded_count']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('test.pdf', $result['errors'][0]);
    }
}
