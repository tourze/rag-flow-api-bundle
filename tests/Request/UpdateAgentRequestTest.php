<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\RAGFlowApiBundle\Request\UpdateAgentRequest;

/**
 * @internal
 */
#[CoversClass(UpdateAgentRequest::class)]
class UpdateAgentRequestTest extends RequestTestCase
{
    public function testRequestCreation(): void
    {
        $request = new UpdateAgentRequest('agent-111', ['title' => 'Updated']);
        $this->assertInstanceOf(UpdateAgentRequest::class, $request);
    }
}
