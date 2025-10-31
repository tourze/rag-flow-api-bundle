<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Request\GetAgentRequest;

/**
 * @internal
 */
#[CoversClass(GetAgentRequest::class)]
class GetAgentRequestTest extends TestCase
{
    public function testRequestCreation(): void
    {
        $request = new GetAgentRequest('test-agent-456');
        $this->assertInstanceOf(GetAgentRequest::class, $request);
    }
}
