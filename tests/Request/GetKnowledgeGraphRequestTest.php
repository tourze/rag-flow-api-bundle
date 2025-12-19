<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\RAGFlowApiBundle\Request\GetKnowledgeGraphRequest;

/**
 * @internal
 */
#[CoversClass(GetKnowledgeGraphRequest::class)]
class GetKnowledgeGraphRequestTest extends RequestTestCase
{
    public function testRequestPath(): void
    {
        $request = new GetKnowledgeGraphRequest('dataset-123');
        $this->assertEquals('/api/v1/datasets/dataset-123/knowledge_graph', $request->getRequestPath());
    }

    public function testRequestPathWithSpecialCharacters(): void
    {
        $request = new GetKnowledgeGraphRequest('dataset-test_123');
        $this->assertEquals('/api/v1/datasets/dataset-test_123/knowledge_graph', $request->getRequestPath());
    }

    public function testRequestMethod(): void
    {
        $request = new GetKnowledgeGraphRequest('dataset-123');
        $this->assertEquals('GET', $request->getRequestMethod());
    }

    public function testRequestOptions(): void
    {
        $request = new GetKnowledgeGraphRequest('dataset-123');
        $this->assertNull($request->getRequestOptions());
    }

    public function testStringRepresentation(): void
    {
        $request = new GetKnowledgeGraphRequest('dataset-123');
        $stringRepresentation = (string) $request;
        $this->assertStringContainsString('GetKnowledgeGraphRequest', $stringRepresentation);
        $this->assertStringContainsString('dataset-123', $stringRepresentation);
    }

    public function testDifferentDatasetIds(): void
    {
        $testCases = [
            'simple-id' => '/api/v1/datasets/simple-id/knowledge_graph',
            'complex_id-123' => '/api/v1/datasets/complex_id-123/knowledge_graph',
            'id-with-numbers-456' => '/api/v1/datasets/id-with-numbers-456/knowledge_graph',
        ];

        foreach ($testCases as $datasetId => $expectedPath) {
            $request = new GetKnowledgeGraphRequest($datasetId);
            $this->assertEquals($expectedPath, $request->getRequestPath());
            $this->assertEquals('GET', $request->getRequestMethod());
        }
    }
}
