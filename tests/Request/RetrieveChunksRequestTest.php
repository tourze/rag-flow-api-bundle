<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\RAGFlowApiBundle\Request\RetrieveChunksRequest;

/**
 * @internal
 */
#[CoversClass(RetrieveChunksRequest::class)]
class RetrieveChunksRequestTest extends RequestTestCase
{
    public function testRequestPath(): void
    {
        $request = new RetrieveChunksRequest(
            docId: 'test-doc-id'
        );

        $this->assertEquals('/api/v1/list_chunks', $request->getRequestPath());
    }

    public function testRequestMethod(): void
    {
        $request = new RetrieveChunksRequest(
            docId: 'test-doc-id'
        );

        $this->assertEquals('GET', $request->getRequestMethod());
    }

    public function testRequestOptions(): void
    {
        $docId = 'test-doc-id';
        $options = ['top_k' => 5, 'similarity_threshold' => 0.7];

        $request = new RetrieveChunksRequest(
            docId: $docId,
            options: $options
        );

        $expectedOptions = [
            'query' => [
                'doc_id' => $docId,
                'top_k' => 5,
                'similarity_threshold' => 0.7,
            ],
        ];

        $this->assertEquals($expectedOptions, $request->getRequestOptions());
    }

    public function testRequestOptionsWithoutExtraOptions(): void
    {
        $docId = 'test-doc-id';

        $request = new RetrieveChunksRequest(
            docId: $docId
        );

        $expectedOptions = [
            'query' => [
                'doc_id' => $docId,
            ],
        ];

        $this->assertEquals($expectedOptions, $request->getRequestOptions());
    }

    public function testMaxRetries(): void
    {
        $request = new RetrieveChunksRequest(
            docId: 'test-doc-id'
        );

        $this->assertEquals(3, $request->getMaxRetries());
    }

    public function testStringRepresentation(): void
    {
        $request = new RetrieveChunksRequest(
            docId: 'test-doc-id'
        );

        $stringRepresentation = (string) $request;
        $this->assertStringContainsString('RetrieveChunksRequest', $stringRepresentation);
        $this->assertStringContainsString('/api/v1/list_chunks', $stringRepresentation);
    }
}
