<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\RAGFlowApiBundle\Request\ListDocumentsRequest;

/**
 * @internal
 */
#[CoversClass(ListDocumentsRequest::class)]
class ListDocumentsRequestTest extends RequestTestCase
{
    public function testRequestPath(): void
    {
        $request = new ListDocumentsRequest(
            datasetId: 'test-dataset-id'
        );

        $this->assertEquals('/api/v1/datasets/test-dataset-id/documents', $request->getRequestPath());
    }

    public function testRequestMethod(): void
    {
        $request = new ListDocumentsRequest(
            datasetId: 'test-dataset-id'
        );

        $this->assertEquals('GET', $request->getRequestMethod());
    }

    public function testRequestOptions(): void
    {
        $filters = ['limit' => 10, 'offset' => 0];
        $datasetId = 'test-dataset-id';

        $request = new ListDocumentsRequest(
            datasetId: $datasetId,
            filters: $filters
        );

        $expectedOptions = [
            'query' => $filters,
        ];

        $this->assertEquals($expectedOptions, $request->getRequestOptions());
    }

    public function testRequestOptionsWithoutFilters(): void
    {
        $request = new ListDocumentsRequest(
            datasetId: 'test-dataset-id'
        );

        $this->assertNull($request->getRequestOptions());
    }

    public function testMaxRetries(): void
    {
        $request = new ListDocumentsRequest(
            datasetId: 'test-dataset-id'
        );

        $this->assertEquals(3, $request->getMaxRetries());
    }

    public function testStringRepresentation(): void
    {
        $request = new ListDocumentsRequest(
            datasetId: 'test-dataset-id'
        );

        $stringRepresentation = (string) $request;
        $this->assertStringContainsString('ListDocumentsRequest', $stringRepresentation);
        $this->assertStringContainsString('/api/v1/datasets/test-dataset-id/documents', $stringRepresentation);
    }
}
