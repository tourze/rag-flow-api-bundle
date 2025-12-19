<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\RAGFlowApiBundle\Request\ParseDocumentRequest;

/**
 * @internal
 */
#[CoversClass(ParseDocumentRequest::class)]
class ParseDocumentRequestTest extends RequestTestCase
{
    public function testRequestPath(): void
    {
        $request = new ParseDocumentRequest(
            datasetId: 'test-dataset-id',
            documentId: 'test-document-id'
        );

        $this->assertEquals('/api/v1/datasets/test-dataset-id/documents/test-document-id/parse', $request->getRequestPath());
    }

    public function testRequestMethod(): void
    {
        $request = new ParseDocumentRequest(
            datasetId: 'test-dataset-id',
            documentId: 'test-document-id'
        );

        $this->assertEquals('POST', $request->getRequestMethod());
    }

    public function testRequestOptions(): void
    {
        $options = ['parse_method' => 'auto', 'language' => 'zh'];
        $datasetId = 'test-dataset-id';
        $documentId = 'test-document-id';

        $request = new ParseDocumentRequest(
            datasetId: $datasetId,
            documentId: $documentId,
            options: $options
        );

        $expectedOptions = [
            'json' => $options,
        ];

        $this->assertEquals($expectedOptions, $request->getRequestOptions());
    }

    public function testRequestOptionsWithoutExtraOptions(): void
    {
        $request = new ParseDocumentRequest(
            datasetId: 'test-dataset-id',
            documentId: 'test-document-id'
        );

        $this->assertNull($request->getRequestOptions());
    }

    public function testMaxRetries(): void
    {
        $request = new ParseDocumentRequest(
            datasetId: 'test-dataset-id',
            documentId: 'test-document-id'
        );

        $this->assertEquals(3, $request->getMaxRetries());
    }

    public function testStringRepresentation(): void
    {
        $request = new ParseDocumentRequest(
            datasetId: 'test-dataset-id',
            documentId: 'test-document-id'
        );

        $stringRepresentation = (string) $request;
        $this->assertStringContainsString('ParseDocumentRequest', $stringRepresentation);
        $this->assertStringContainsString('/api/v1/datasets/test-dataset-id/documents/test-document-id/parse', $stringRepresentation);
    }
}
