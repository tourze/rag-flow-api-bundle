<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Request\DeleteDocumentRequest;

/**
 * @internal
 */
#[CoversClass(DeleteDocumentRequest::class)]
class DeleteDocumentRequestTest extends TestCase
{
    public function testRequestPath(): void
    {
        $request = new DeleteDocumentRequest(
            datasetId: 'test-dataset-id',
            documentId: 'test-document-id'
        );

        $this->assertEquals('/api/v1/datasets/test-dataset-id/documents/test-document-id', $request->getRequestPath());
    }

    public function testRequestMethod(): void
    {
        $request = new DeleteDocumentRequest(
            datasetId: 'test-dataset-id',
            documentId: 'test-document-id'
        );

        $this->assertEquals('DELETE', $request->getRequestMethod());
    }

    public function testRequestOptions(): void
    {
        $request = new DeleteDocumentRequest(
            datasetId: 'test-dataset-id',
            documentId: 'test-document-id'
        );

        $this->assertNull($request->getRequestOptions());
    }

    public function testMaxRetries(): void
    {
        $request = new DeleteDocumentRequest(
            datasetId: 'test-dataset-id',
            documentId: 'test-document-id'
        );

        $this->assertEquals(3, $request->getMaxRetries());
    }

    public function testStringRepresentation(): void
    {
        $request = new DeleteDocumentRequest(
            datasetId: 'test-dataset-id',
            documentId: 'test-document-id'
        );

        $stringRepresentation = (string) $request;
        $this->assertStringContainsString('DeleteDocumentRequest', $stringRepresentation);
        $this->assertStringContainsString('/api/v1/datasets/test-dataset-id/documents/test-document-id', $stringRepresentation);
    }
}
