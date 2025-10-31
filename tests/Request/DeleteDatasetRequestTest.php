<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Request\DeleteDatasetRequest;

/**
 * @internal
 */
#[CoversClass(DeleteDatasetRequest::class)]
class DeleteDatasetRequestTest extends TestCase
{
    public function testRequestPath(): void
    {
        $request = new DeleteDatasetRequest('dataset-123');
        $this->assertEquals('/api/v1/datasets/dataset-123', $request->getRequestPath());
    }

    public function testRequestPathWithSpecialCharacters(): void
    {
        $request = new DeleteDatasetRequest('dataset-test_123');
        $this->assertEquals('/api/v1/datasets/dataset-test_123', $request->getRequestPath());
    }

    public function testRequestMethod(): void
    {
        $request = new DeleteDatasetRequest('dataset-123');
        $this->assertEquals('DELETE', $request->getRequestMethod());
    }

    public function testRequestOptions(): void
    {
        $request = new DeleteDatasetRequest('dataset-123');
        $this->assertNull($request->getRequestOptions());
    }

    public function testStringRepresentation(): void
    {
        $request = new DeleteDatasetRequest('dataset-123');
        $stringRepresentation = (string) $request;
        $this->assertStringContainsString('DeleteDatasetRequest', $stringRepresentation);
        $this->assertStringContainsString('dataset-123', $stringRepresentation);
    }

    public function testDifferentDatasetIds(): void
    {
        $testCases = [
            'simple-id' => '/api/v1/datasets/simple-id',
            'complex_id-123' => '/api/v1/datasets/complex_id-123',
            'id-with-numbers-456' => '/api/v1/datasets/id-with-numbers-456',
        ];

        foreach ($testCases as $datasetId => $expectedPath) {
            $request = new DeleteDatasetRequest($datasetId);
            $this->assertEquals($expectedPath, $request->getRequestPath());
            $this->assertEquals('DELETE', $request->getRequestMethod());
        }
    }
}
