<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\RAGFlowApiBundle\Request\ChatCompletionRequest;

/**
 * @internal
 */
#[CoversClass(ChatCompletionRequest::class)]
class ChatCompletionRequestTest extends RequestTestCase
{
    public function testRequestPath(): void
    {
        $request = new ChatCompletionRequest(
            chatId: 'test-conversation-id',
            messages: [['role' => 'user', 'content' => 'Hello']]
        );

        $this->assertEquals('/api/v1/chats/test-conversation-id/completions', $request->getRequestPath());
    }

    public function testRequestMethod(): void
    {
        $request = new ChatCompletionRequest(
            chatId: 'test-conversation-id',
            messages: [['role' => 'user', 'content' => 'Hello']]
        );

        $this->assertEquals('POST', $request->getRequestMethod());
    }

    public function testRequestOptions(): void
    {
        $messages = [['role' => 'user', 'content' => 'Hello']];
        $chatId = 'test-conversation-id';
        $options = ['temperature' => 0.7];

        $request = new ChatCompletionRequest(
            chatId: $chatId,
            messages: $messages,
            options: $options
        );

        $expectedOptions = [
            'json' => [
                'question' => 'Hello',
                'temperature' => 0.7,
            ],
        ];

        $this->assertEquals($expectedOptions, $request->getRequestOptions());
    }

    public function testRequestOptionsWithoutExtraOptions(): void
    {
        $messages = [['role' => 'user', 'content' => 'Hello']];
        $chatId = 'test-conversation-id';

        $request = new ChatCompletionRequest(
            chatId: $chatId,
            messages: $messages
        );

        $expectedOptions = [
            'json' => [
                'question' => 'Hello',
            ],
        ];

        $this->assertEquals($expectedOptions, $request->getRequestOptions());
    }

    public function testMaxRetries(): void
    {
        $request = new ChatCompletionRequest(
            chatId: 'test-conversation-id',
            messages: [['role' => 'user', 'content' => 'Hello']]
        );

        $this->assertEquals(3, $request->getMaxRetries());
    }

    public function testStringRepresentation(): void
    {
        $request = new ChatCompletionRequest(
            chatId: 'test-conversation-id',
            messages: [['role' => 'user', 'content' => 'Hello']]
        );

        $stringRepresentation = (string) $request;
        $this->assertStringContainsString('ChatCompletionRequest', $stringRepresentation);
        $this->assertStringContainsString('/api/v1/chats/test-conversation-id/completions', $stringRepresentation);
    }
}
