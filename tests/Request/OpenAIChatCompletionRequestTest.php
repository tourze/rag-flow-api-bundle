<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\RAGFlowApiBundle\Request\OpenAIChatCompletionRequest;

/**
 * @internal
 */
#[CoversClass(OpenAIChatCompletionRequest::class)]
class OpenAIChatCompletionRequestTest extends RequestTestCase
{
    public function testRequestCreation(): void
    {
        $request = new OpenAIChatCompletionRequest('chat-222', 'assistant-333', [['role' => 'user', 'content' => 'Hello']]);
        $this->assertInstanceOf(OpenAIChatCompletionRequest::class, $request);
    }
}
