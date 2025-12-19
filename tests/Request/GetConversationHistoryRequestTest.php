<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\RAGFlowApiBundle\Request\GetConversationHistoryRequest;

/**
 * @internal
 */
#[CoversClass(GetConversationHistoryRequest::class)]
class GetConversationHistoryRequestTest extends RequestTestCase
{
    public function testRequestPath(): void
    {
        $request = new GetConversationHistoryRequest(
            chatId: 'test-conversation-id'
        );

        $this->assertEquals('/api/v1/chats/test-conversation-id/messages', $request->getRequestPath());
    }

    public function testRequestMethod(): void
    {
        $request = new GetConversationHistoryRequest(
            chatId: 'test-conversation-id'
        );

        $this->assertEquals('GET', $request->getRequestMethod());
    }

    public function testRequestOptions(): void
    {
        $filters = ['limit' => 10, 'offset' => 0];
        $chatId = 'test-conversation-id';

        $request = new GetConversationHistoryRequest(
            chatId: $chatId,
            filters: $filters
        );

        $expectedOptions = [
            'query' => $filters,
        ];

        $this->assertEquals($expectedOptions, $request->getRequestOptions());
    }

    public function testRequestOptionsWithoutFilters(): void
    {
        $request = new GetConversationHistoryRequest(
            chatId: 'test-conversation-id'
        );

        $this->assertNull($request->getRequestOptions());
    }

    public function testMaxRetries(): void
    {
        $request = new GetConversationHistoryRequest(
            chatId: 'test-conversation-id'
        );

        $this->assertEquals(3, $request->getMaxRetries());
    }

    public function testStringRepresentation(): void
    {
        $request = new GetConversationHistoryRequest(
            chatId: 'test-conversation-id'
        );

        $stringRepresentation = (string) $request;
        $this->assertStringContainsString('GetConversationHistoryRequest', $stringRepresentation);
        $this->assertStringContainsString('/api/v1/chats/test-conversation-id/messages', $stringRepresentation);
    }
}
