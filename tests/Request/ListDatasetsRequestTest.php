<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Request\ListDatasetsRequest;

/**
 * @internal
 */
#[CoversClass(ListDatasetsRequest::class)]
class ListDatasetsRequestTest extends TestCase
{
    public function testRequestPath(): void
    {
        $request = new ListDatasetsRequest();
        $this->assertEquals('/api/v1/datasets', $request->getRequestPath());
    }

    public function testRequestMethod(): void
    {
        $request = new ListDatasetsRequest();
        $this->assertEquals('GET', $request->getRequestMethod());
    }

    public function testRequestOptionsWithFilters(): void
    {
        $filters = ['page' => 1, 'page_size' => 10];
        $request = new ListDatasetsRequest($filters);

        $options = $request->getRequestOptions();
        $this->assertIsArray($options, 'Request options should be an array');
        $this->assertArrayHasKey('query', $options);
        $this->assertEquals($filters, $options['query']);
    }

    public function testRequestOptionsWithoutFilters(): void
    {
        $request = new ListDatasetsRequest();

        $options = $request->getRequestOptions();
        $this->assertIsArray($options, 'Request options should be an array');
        $this->assertEquals(['query' => []], $options);
    }

    public function testCacheKey(): void
    {
        $request = new ListDatasetsRequest(['page' => 1]);
        $this->assertStringContainsString('ragflow-list-datasets', $request->getCacheKey());
    }

    public function testStringRepresentation(): void
    {
        $request = new ListDatasetsRequest(['page' => 1]);
        $this->assertStringContainsString('ListDatasetsRequest', (string) $request);
    }
}
