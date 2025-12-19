<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\RAGFlowApiBundle\Request\CreateChatSessionRequest;

/**
 * @internal
 */
#[CoversClass(CreateChatSessionRequest::class)]
class CreateChatSessionRequestTest extends RequestTestCase
{
    public function testRequestCreation(): void
    {
        $request = new CreateChatSessionRequest('test-assistant-123');
        $this->assertInstanceOf(CreateChatSessionRequest::class, $request);
    }
}
