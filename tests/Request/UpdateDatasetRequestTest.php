<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\RAGFlowApiBundle\Request\UpdateDatasetRequest;

/**
 * @internal
 */
#[CoversClass(UpdateDatasetRequest::class)]
class UpdateDatasetRequestTest extends RequestTestCase
{
    public function testRequestPath(): void
    {
        $config = ['name' => 'Updated Dataset'];
        $request = new UpdateDatasetRequest('dataset-123', $config);
        $this->assertEquals('/api/v1/datasets/dataset-123', $request->getRequestPath());
    }

    public function testRequestPathWithSpecialCharacters(): void
    {
        $config = ['name' => 'Updated Dataset'];
        $request = new UpdateDatasetRequest('dataset-test_123', $config);
        $this->assertEquals('/api/v1/datasets/dataset-test_123', $request->getRequestPath());
    }

    public function testRequestMethod(): void
    {
        $config = ['name' => 'Updated Dataset'];
        $request = new UpdateDatasetRequest('dataset-123', $config);
        $this->assertEquals('PUT', $request->getRequestMethod());
    }

    public function testRequestOptions(): void
    {
        $config = [
            'name' => 'Updated Dataset',
            'description' => 'Updated description',
            'language' => 'English',
        ];
        $request = new UpdateDatasetRequest('dataset-123', $config);

        $options = $request->getRequestOptions();
        $this->assertIsArray($options, 'Request options should be an array');
        $this->assertArrayHasKey('json', $options);
        $this->assertEquals($config, $options['json']);
    }

    public function testRequestOptionsWithSimpleConfig(): void
    {
        $config = ['name' => 'Simple Update'];
        $request = new UpdateDatasetRequest('dataset-123', $config);

        $options = $request->getRequestOptions();
        $this->assertNotNull($options);
        $this->assertEquals(['json' => $config], $options);
    }

    public function testRequestOptionsWithComplexConfig(): void
    {
        $config = [
            'name' => 'Complex Dataset',
            'description' => 'A complex dataset with multiple settings',
            'language' => 'Chinese',
            'chunk_method' => 'auto',
            'chunk_size' => 512,
            'parser_config' => [
                'layout_recognize' => true,
                'table_recognize' => true,
            ],
        ];
        $request = new UpdateDatasetRequest('dataset-123', $config);

        $options = $request->getRequestOptions();
        $this->assertIsArray($options, 'Request options should be an array');
        $this->assertArrayHasKey('json', $options);
        $this->assertEquals($config, $options['json']);
        $this->assertArrayHasKey('parser_config', $options['json']);
        $this->assertIsArray($options['json']['parser_config']);
    }

    public function testStringRepresentation(): void
    {
        $config = ['name' => 'Test Dataset'];
        $request = new UpdateDatasetRequest('dataset-123', $config);
        $stringRepresentation = (string) $request;
        $this->assertStringContainsString('UpdateDatasetRequest', $stringRepresentation);
        $this->assertStringContainsString('dataset-123', $stringRepresentation);
        $this->assertStringContainsString('Test Dataset', $stringRepresentation);
    }

    public function testDifferentDatasetIds(): void
    {
        $config = ['name' => 'Test Dataset'];
        $testCases = [
            'simple-id' => '/api/v1/datasets/simple-id',
            'complex_id-123' => '/api/v1/datasets/complex_id-123',
            'id-with-numbers-456' => '/api/v1/datasets/id-with-numbers-456',
        ];

        foreach ($testCases as $datasetId => $expectedPath) {
            $request = new UpdateDatasetRequest($datasetId, $config);
            $this->assertEquals($expectedPath, $request->getRequestPath());
            $this->assertEquals('PUT', $request->getRequestMethod());
            $options = $request->getRequestOptions();
            $this->assertNotNull($options);
            $this->assertEquals(['json' => $config], $options);
        }
    }
}
