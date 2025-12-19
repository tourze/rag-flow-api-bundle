<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\RAGFlowApiBundle\Request\CreateConversationRequest;

/**
 * @internal
 */
#[CoversClass(CreateConversationRequest::class)]
class CreateConversationRequestTest extends RequestTestCase
{
    public function testRequestPath(): void
    {
        $request = new CreateConversationRequest(
            name: 'Test Conversation'
        );

        $this->assertEquals('/api/v1/chats', $request->getRequestPath());
    }

    public function testRequestMethod(): void
    {
        $request = new CreateConversationRequest(
            name: 'Test Conversation'
        );

        $this->assertEquals('POST', $request->getRequestMethod());
    }

    public function testRequestOptions(): void
    {
        $name = 'Test Conversation';
        $datasetIds = ['test-dataset-id'];
        $options = ['metadata' => ['source' => 'test']];

        $request = new CreateConversationRequest(
            name: $name,
            datasetIds: $datasetIds,
            options: $options
        );

        $expectedOptions = [
            'json' => [
                'name' => $name,
                'dataset_ids' => $datasetIds,
                'metadata' => ['source' => 'test'],
            ],
        ];

        $this->assertEquals($expectedOptions, $request->getRequestOptions());
    }

    public function testRequestOptionsWithoutDatasetId(): void
    {
        $name = 'Test Conversation';
        $options = ['metadata' => ['source' => 'test']];

        $request = new CreateConversationRequest(
            name: $name,
            options: $options
        );

        $expectedOptions = [
            'json' => [
                'name' => $name,
                'metadata' => ['source' => 'test'],
            ],
        ];

        $this->assertEquals($expectedOptions, $request->getRequestOptions());
    }

    public function testRequestOptionsWithoutExtraOptions(): void
    {
        $name = 'Test Conversation';
        $datasetIds = ['test-dataset-id'];

        $request = new CreateConversationRequest(
            name: $name,
            datasetIds: $datasetIds
        );

        $expectedOptions = [
            'json' => [
                'name' => $name,
                'dataset_ids' => $datasetIds,
            ],
        ];

        $this->assertEquals($expectedOptions, $request->getRequestOptions());
    }

    public function testMaxRetries(): void
    {
        $request = new CreateConversationRequest(
            name: 'Test Conversation'
        );

        $this->assertEquals(3, $request->getMaxRetries());
    }

    public function testStringRepresentation(): void
    {
        $request = new CreateConversationRequest(
            name: 'Test Conversation'
        );

        $stringRepresentation = (string) $request;
        $this->assertStringContainsString('CreateConversationRequest', $stringRepresentation);
        $this->assertStringContainsString('/api/v1/chats', $stringRepresentation);
    }
}
