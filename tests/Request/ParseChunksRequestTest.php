<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\RAGFlowApiBundle\Request\ParseChunksRequest;

/**
 * @internal
 */
#[CoversClass(ParseChunksRequest::class)]
class ParseChunksRequestTest extends RequestTestCase
{
    public function testRequestCreation(): void
    {
        $request = new ParseChunksRequest('dataset-789', ['doc-1', 'doc-2']);
        $this->assertInstanceOf(ParseChunksRequest::class, $request);
    }
}
