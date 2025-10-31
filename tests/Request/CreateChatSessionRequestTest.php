<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Request\CreateChatSessionRequest;

/**
 * @internal
 */
#[CoversClass(CreateChatSessionRequest::class)]
class CreateChatSessionRequestTest extends TestCase
{
    public function testRequestCreation(): void
    {
        $request = new CreateChatSessionRequest('test-assistant-123');
        $this->assertInstanceOf(CreateChatSessionRequest::class, $request);
    }
}
