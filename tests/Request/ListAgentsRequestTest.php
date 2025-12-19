<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\RAGFlowApiBundle\Request\ListAgentsRequest;

/**
 * @internal
 */
#[CoversClass(ListAgentsRequest::class)]
class ListAgentsRequestTest extends RequestTestCase
{
    public function testRequestCreation(): void
    {
        $request = new ListAgentsRequest(1, 10);
        $this->assertInstanceOf(ListAgentsRequest::class, $request);
    }
}
