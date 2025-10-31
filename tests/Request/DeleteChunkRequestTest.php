<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Request\DeleteChunkRequest;

/**
 * @internal
 */
#[CoversClass(DeleteChunkRequest::class)]
class DeleteChunkRequestTest extends TestCase
{
    public function testRequestPath(): void
    {
        $request = new DeleteChunkRequest(
            datasetId: 'test-dataset-id',
            chunkId: 'test-chunk-id'
        );

        $this->assertEquals('/api/v1/datasets/test-dataset-id/chunks/test-chunk-id', $request->getRequestPath());
    }

    public function testRequestMethod(): void
    {
        $request = new DeleteChunkRequest(
            datasetId: 'test-dataset-id',
            chunkId: 'test-chunk-id'
        );

        $this->assertEquals('DELETE', $request->getRequestMethod());
    }

    public function testRequestOptions(): void
    {
        $request = new DeleteChunkRequest(
            datasetId: 'test-dataset-id',
            chunkId: 'test-chunk-id'
        );

        $this->assertNull($request->getRequestOptions());
    }

    public function testMaxRetries(): void
    {
        $request = new DeleteChunkRequest(
            datasetId: 'test-dataset-id',
            chunkId: 'test-chunk-id'
        );

        $this->assertEquals(3, $request->getMaxRetries());
    }

    public function testStringRepresentation(): void
    {
        $request = new DeleteChunkRequest(
            datasetId: 'test-dataset-id',
            chunkId: 'test-chunk-id'
        );

        $stringRepresentation = (string) $request;
        $this->assertStringContainsString('DeleteChunkRequest', $stringRepresentation);
        $this->assertStringContainsString('/api/v1/datasets/test-dataset-id/chunks/test-chunk-id', $stringRepresentation);
    }
}
