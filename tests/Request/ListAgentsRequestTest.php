<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Request\ListAgentsRequest;

/**
 * @internal
 */
#[CoversClass(ListAgentsRequest::class)]
class ListAgentsRequestTest extends TestCase
{
    public function testRequestCreation(): void
    {
        $request = new ListAgentsRequest(1, 10);
        $this->assertInstanceOf(ListAgentsRequest::class, $request);
    }
}
