<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Request\GetParseStatusRequest;

/**
 * @internal
 */
#[CoversClass(GetParseStatusRequest::class)]
class GetParseStatusRequestTest extends TestCase
{
    public function testRequestPath(): void
    {
        $request = new GetParseStatusRequest(
            datasetId: 'test-dataset-id',
            documentId: 'test-document-id'
        );

        $this->assertEquals('/api/v1/datasets/test-dataset-id/documents/test-document-id/parse_status', $request->getRequestPath());
    }

    public function testRequestMethod(): void
    {
        $request = new GetParseStatusRequest(
            datasetId: 'test-dataset-id',
            documentId: 'test-document-id'
        );

        $this->assertEquals('GET', $request->getRequestMethod());
    }

    public function testRequestOptions(): void
    {
        $request = new GetParseStatusRequest(
            datasetId: 'test-dataset-id',
            documentId: 'test-document-id'
        );

        $this->assertNull($request->getRequestOptions());
    }

    public function testMaxRetries(): void
    {
        $request = new GetParseStatusRequest(
            datasetId: 'test-dataset-id',
            documentId: 'test-document-id'
        );

        $this->assertEquals(3, $request->getMaxRetries());
    }

    public function testStringRepresentation(): void
    {
        $request = new GetParseStatusRequest(
            datasetId: 'test-dataset-id',
            documentId: 'test-document-id'
        );

        $stringRepresentation = (string) $request;
        $this->assertStringContainsString('GetParseStatusRequest', $stringRepresentation);
        $this->assertStringContainsString('/api/v1/datasets/test-dataset-id/documents/test-document-id/parse_status', $stringRepresentation);
    }
}
