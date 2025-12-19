<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\RAGFlowApiBundle\Request\ListChunksRequest;

/**
 * @internal
 */
#[CoversClass(ListChunksRequest::class)]
class ListChunksRequestTest extends RequestTestCase
{
    public function testRequestCreation(): void
    {
        $request = new ListChunksRequest('dataset-123', 'document-456');
        $this->assertInstanceOf(ListChunksRequest::class, $request);
    }
}
