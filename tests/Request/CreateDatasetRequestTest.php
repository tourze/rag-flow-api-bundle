<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Request\CreateDatasetRequest;

/**
 * @internal
 */
#[CoversClass(CreateDatasetRequest::class)]
class CreateDatasetRequestTest extends TestCase
{
    public function testRequestPath(): void
    {
        $request = new CreateDatasetRequest(['name' => 'test']);
        $this->assertEquals('/api/v1/datasets', $request->getRequestPath());
    }

    public function testRequestMethod(): void
    {
        $request = new CreateDatasetRequest(['name' => 'test']);
        $this->assertEquals('POST', $request->getRequestMethod());
    }

    public function testRequestOptions(): void
    {
        $config = [
            'name' => 'test-dataset',
            'language' => 'Chinese',
            'chunk_method' => 'manual',
        ];
        $request = new CreateDatasetRequest($config);

        $options = $request->getRequestOptions();
        $this->assertIsArray($options, 'Request options should be an array');
        $this->assertArrayHasKey('json', $options);
        $this->assertEquals($config, $options['json']);
    }

    public function testRequestOptionsWithoutExtraOptions(): void
    {
        $config = ['name' => 'test'];
        $request = new CreateDatasetRequest($config);

        $options = $request->getRequestOptions();
        $this->assertIsArray($options, 'Request options should be an array');
        $this->assertEquals(['json' => $config], $options);
    }

    public function testCacheDuration(): void
    {
        $request = new CreateDatasetRequest(['name' => 'test']);
        $this->assertEquals(0, $request->getCacheDuration());
    }

    public function testStringRepresentation(): void
    {
        $request = new CreateDatasetRequest(['name' => 'test-dataset']);
        $this->assertStringContainsString('CreateDatasetRequest', (string) $request);
        $this->assertStringContainsString('test-dataset', (string) $request);
    }
}
