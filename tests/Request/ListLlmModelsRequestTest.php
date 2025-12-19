<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\RAGFlowApiBundle\Request\ListLlmModelsRequest;

/**
 * @internal
 */
#[CoversClass(ListLlmModelsRequest::class)]
class ListLlmModelsRequestTest extends RequestTestCase
{
    public function testRequestCreation(): void
    {
        $request = new ListLlmModelsRequest();
        $this->assertInstanceOf(ListLlmModelsRequest::class, $request);
    }
}
