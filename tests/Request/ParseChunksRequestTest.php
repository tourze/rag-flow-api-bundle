<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Request\ParseChunksRequest;

/**
 * @internal
 */
#[CoversClass(ParseChunksRequest::class)]
class ParseChunksRequestTest extends TestCase
{
    public function testRequestCreation(): void
    {
        $request = new ParseChunksRequest('dataset-789', ['doc-1', 'doc-2']);
        $this->assertInstanceOf(ParseChunksRequest::class, $request);
    }
}
