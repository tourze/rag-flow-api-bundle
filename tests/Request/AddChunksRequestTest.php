<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Request\AddChunksRequest;

/**
 * @internal
 */
#[CoversClass(AddChunksRequest::class)]
class AddChunksRequestTest extends TestCase
{
    public function testRequestPath(): void
    {
        $request = new AddChunksRequest(
            datasetId: 'test-dataset-id',
            chunks: [['content' => 'test chunk']]
        );

        $this->assertEquals('/api/v1/datasets/test-dataset-id/chunks', $request->getRequestPath());
    }

    public function testRequestMethod(): void
    {
        $request = new AddChunksRequest(
            datasetId: 'test-dataset-id',
            chunks: [['content' => 'test chunk']]
        );

        $this->assertEquals('POST', $request->getRequestMethod());
    }

    public function testRequestOptions(): void
    {
        $chunks = [['content' => 'test chunk']];
        $options = ['metadata' => ['source' => 'test']];
        $datasetId = 'test-dataset-id';

        $request = new AddChunksRequest(
            datasetId: $datasetId,
            chunks: $chunks,
            options: $options
        );

        $expectedOptions = [
            'json' => [
                'chunks' => $chunks,
                'metadata' => ['source' => 'test'],
            ],
        ];

        $this->assertEquals($expectedOptions, $request->getRequestOptions());
    }

    public function testRequestOptionsWithoutExtraOptions(): void
    {
        $chunks = [['content' => 'test chunk']];
        $datasetId = 'test-dataset-id';

        $request = new AddChunksRequest(
            datasetId: $datasetId,
            chunks: $chunks
        );

        $expectedOptions = [
            'json' => [
                'chunks' => $chunks,
            ],
        ];

        $this->assertEquals($expectedOptions, $request->getRequestOptions());
    }

    public function testMaxRetries(): void
    {
        $request = new AddChunksRequest(
            datasetId: 'test-dataset-id',
            chunks: [['content' => 'test chunk']]
        );

        $this->assertEquals(3, $request->getMaxRetries());
    }

    public function testStringRepresentation(): void
    {
        $request = new AddChunksRequest(
            datasetId: 'test-dataset-id',
            chunks: [['content' => 'test chunk']]
        );

        $stringRepresentation = (string) $request;
        $this->assertStringContainsString('AddChunksRequest', $stringRepresentation);
        $this->assertStringContainsString('/api/v1/datasets/test-dataset-id/chunks', $stringRepresentation);
    }
}
