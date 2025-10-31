<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Request\ListLlmModelsRequest;

/**
 * @internal
 */
#[CoversClass(ListLlmModelsRequest::class)]
class ListLlmModelsRequestTest extends TestCase
{
    public function testRequestCreation(): void
    {
        $request = new ListLlmModelsRequest();
        $this->assertInstanceOf(ListLlmModelsRequest::class, $request);
    }
}
