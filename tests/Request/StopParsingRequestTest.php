<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\RAGFlowApiBundle\Request\StopParsingRequest;

/**
 * @internal
 */
#[CoversClass(StopParsingRequest::class)]
class StopParsingRequestTest extends RequestTestCase
{
    public function testRequestCreation(): void
    {
        $request = new StopParsingRequest('dataset-999', ['doc-1']);
        $this->assertInstanceOf(StopParsingRequest::class, $request);
    }
}
