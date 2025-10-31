<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Request\UpdateAgentRequest;

/**
 * @internal
 */
#[CoversClass(UpdateAgentRequest::class)]
class UpdateAgentRequestTest extends TestCase
{
    public function testRequestCreation(): void
    {
        $request = new UpdateAgentRequest('agent-111', ['title' => 'Updated']);
        $this->assertInstanceOf(UpdateAgentRequest::class, $request);
    }
}
