<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use HttpClientBundle\Request\ApiRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Request\BaseRAGFlowRequest;

/**
 * @internal
 */
#[CoversClass(BaseRAGFlowRequest::class)]
class BaseRAGFlowRequestTest extends TestCase
{
    private BaseRAGFlowRequestTestConcrete $request;

    protected function setUp(): void
    {
        parent::setUp();
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
