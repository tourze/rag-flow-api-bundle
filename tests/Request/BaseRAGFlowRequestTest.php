<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use HttpClientBundle\Request\ApiRequest;
use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\RAGFlowApiBundle\Request\BaseRAGFlowRequest;

/**
 * @internal
 */
#[CoversClass(BaseRAGFlowRequest::class)]
class BaseRAGFlowRequestTest extends RequestTestCase
{
    private BaseRAGFlowRequestTestConcrete $request;

    protected function onSetUp(): void
    {
        $this->request = new BaseRAGFlowRequestTestConcrete();
    }

    public function testExtendsApiRequest(): void
    {
        $this->assertInstanceOf(ApiRequest::class, $this->request);
    }

    public function testGetRequestMethodReturnsPost(): void
    {
        $this->assertEquals('POST', $this->request->getRequestMethod());
    }

    public function testIsAbstractClass(): void
    {
        $reflection = new \ReflectionClass(BaseRAGFlowRequest::class);
        $this->assertTrue($reflection->isAbstract());
    }

    public function testBaseClassCanBeExtended(): void
    {
        $this->assertInstanceOf(BaseRAGFlowRequest::class, $this->request);
    }
}
