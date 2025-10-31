<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Request\StopParsingRequest;

/**
 * @internal
 */
#[CoversClass(StopParsingRequest::class)]
class StopParsingRequestTest extends TestCase
{
    public function testRequestCreation(): void
    {
        $request = new StopParsingRequest('dataset-999', ['doc-1']);
        $this->assertInstanceOf(StopParsingRequest::class, $request);
    }
}
