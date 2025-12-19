<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\RAGFlowApiBundle\Request\UpdateChunkRequest;

/**
 * @internal
 */
#[CoversClass(UpdateChunkRequest::class)]
class UpdateChunkRequestTest extends RequestTestCase
{
    public function testRequestPath(): void
    {
        $request = new UpdateChunkRequest(
            datasetId: 'test-dataset-id',
            chunkId: 'test-chunk-id',
            content: ['content' => 'updated content']
        );

        $this->assertEquals('/api/v1/datasets/test-dataset-id/chunks/test-chunk-id', $request->getRequestPath());
    }

    public function testRequestMethod(): void
    {
        $request = new UpdateChunkRequest(
            datasetId: 'test-dataset-id',
            chunkId: 'test-chunk-id',
            content: ['content' => 'updated content']
        );

        $this->assertEquals('PUT', $request->getRequestMethod());
    }

    public function testRequestOptions(): void
    {
        $content = ['content' => 'updated content', 'metadata' => ['source' => 'updated']];
        $datasetId = 'test-dataset-id';
        $chunkId = 'test-chunk-id';

        $request = new UpdateChunkRequest(
            datasetId: $datasetId,
            chunkId: $chunkId,
            content: $content
        );

        $expectedOptions = [
            'json' => $content,
        ];

        $this->assertEquals($expectedOptions, $request->getRequestOptions());
    }

    public function testMaxRetries(): void
    {
        $request = new UpdateChunkRequest(
            datasetId: 'test-dataset-id',
            chunkId: 'test-chunk-id',
            content: ['content' => 'updated content']
        );

        $this->assertEquals(3, $request->getMaxRetries());
    }

    public function testStringRepresentation(): void
    {
        $request = new UpdateChunkRequest(
            datasetId: 'test-dataset-id',
            chunkId: 'test-chunk-id',
            content: ['content' => 'updated content']
        );

        $stringRepresentation = (string) $request;
        $this->assertStringContainsString('UpdateChunkRequest', $stringRepresentation);
        $this->assertStringContainsString('/api/v1/datasets/test-dataset-id/chunks/test-chunk-id', $stringRepresentation);
    }
}
