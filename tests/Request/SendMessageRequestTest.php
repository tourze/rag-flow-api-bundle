<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\RAGFlowApiBundle\Request\SendMessageRequest;

/**
 * @internal
 */
#[CoversClass(SendMessageRequest::class)]
class SendMessageRequestTest extends RequestTestCase
{
    public function testRequestPath(): void
    {
        $request = new SendMessageRequest(
            chatId: 'test-conversation-id',
            question: 'Hello, world!'
        );

        $this->assertEquals('/api/v1/chats/test-conversation-id/completions', $request->getRequestPath());
    }

    public function testRequestMethod(): void
    {
        $request = new SendMessageRequest(
            chatId: 'test-conversation-id',
            question: 'Hello, world!'
        );

        $this->assertEquals('POST', $request->getRequestMethod());
    }

    public function testRequestOptions(): void
    {
        $chatId = 'test-conversation-id';
        $question = 'Hello, world!';
        $options = ['temperature' => 0.7, 'max_tokens' => 1000];

        $request = new SendMessageRequest(
            chatId: $chatId,
            question: $question,
            options: $options
        );

        $expectedOptions = [
            'json' => [
                'question' => $question,
                'temperature' => 0.7,
                'max_tokens' => 1000,
            ],
        ];

        $this->assertEquals($expectedOptions, $request->getRequestOptions());
    }

    public function testRequestOptionsWithoutExtraOptions(): void
    {
        $chatId = 'test-conversation-id';
        $question = 'Hello, world!';

        $request = new SendMessageRequest(
            chatId: $chatId,
            question: $question
        );

        $expectedOptions = [
            'json' => [
                'question' => $question,
            ],
        ];

        $this->assertEquals($expectedOptions, $request->getRequestOptions());
    }

    public function testMaxRetries(): void
    {
        $request = new SendMessageRequest(
            chatId: 'test-conversation-id',
            question: 'Hello, world!'
        );

        $this->assertEquals(3, $request->getMaxRetries());
    }

    public function testStringRepresentation(): void
    {
        $request = new SendMessageRequest(
            chatId: 'test-conversation-id',
            question: 'Hello, world!'
        );

        $stringRepresentation = (string) $request;
        $this->assertStringContainsString('SendMessageRequest', $stringRepresentation);
        $this->assertStringContainsString('/api/v1/chats/test-conversation-id/completions', $stringRepresentation);
    }
}
