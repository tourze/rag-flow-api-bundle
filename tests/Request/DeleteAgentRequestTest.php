<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\RAGFlowApiBundle\Request\DeleteAgentRequest;

/**
 * @internal
 */
#[CoversClass(DeleteAgentRequest::class)]
class DeleteAgentRequestTest extends RequestTestCase
{
    public function testRequestCreation(): void
    {
        $request = new DeleteAgentRequest('test-agent-123');
        $this->assertInstanceOf(DeleteAgentRequest::class, $request);
    }
}
