<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\DTO\ResponseFactory;

use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\DTO\ResponseFactory\AbstractResponseFactory;
use Tourze\RAGFlowApiBundle\DTO\ResponseFactory\AgentResponseFactory;
use Tourze\RAGFlowApiBundle\DTO\ResponseFactory\ResponseFactoryResolver;
use Tourze\RAGFlowApiBundle\Request\CreateAgentRequest;
use Tourze\RAGFlowApiBundle\Request\DeleteAgentRequest;
use Tourze\RAGFlowApiBundle\Request\GetAgentRequest;
use Tourze\RAGFlowApiBundle\Request\ListAgentsRequest;
use Tourze\RAGFlowApiBundle\Request\UpdateAgentRequest;

/**
 * 测试响应工厂解析器
 */
class ResponseFactoryResolverTest extends TestCase
{
    private ResponseFactoryResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ResponseFactoryResolver();
    }

    public function testResolveCreateAgentRequest(): void
    {
        $request = $this->createMock(CreateAgentRequest::class);
        $factory = $this->resolver->resolve($request);

        $this->assertInstanceOf(AgentResponseFactory::class, $factory);
        $this->assertTrue($factory->supports($request));
    }

    public function testResolveUpdateAgentRequest(): void
    {
        $request = $this->createMock(UpdateAgentRequest::class);
        $factory = $this->resolver->resolve($request);

        $this->assertInstanceOf(AgentResponseFactory::class, $factory);
        $this->assertTrue($factory->supports($request));
    }

    public function testResolveDeleteAgentRequest(): void
    {
        $request = $this->createMock(DeleteAgentRequest::class);
        $factory = $this->resolver->resolve($request);

        $this->assertInstanceOf(AgentResponseFactory::class, $factory);
        $this->assertTrue($factory->supports($request));
    }

    public function testResolveGetAgentRequest(): void
    {
        $request = $this->createMock(GetAgentRequest::class);
        $factory = $this->resolver->resolve($request);

        $this->assertInstanceOf(AgentResponseFactory::class, $factory);
        $this->assertTrue($factory->supports($request));
    }

    public function testResolveListAgentsRequest(): void
    {
        $request = $this->createMock(ListAgentsRequest::class);
        $factory = $this->resolver->resolve($request);

        $this->assertInstanceOf(AgentResponseFactory::class, $factory);
        $this->assertTrue($factory->supports($request));
    }

    public function testResolveOtherRequestReturnsDefaultFactory(): void
    {
        $request = $this->createMock(\HttpClientBundle\Request\RequestInterface::class);
        $factory = $this->resolver->resolve($request);

        $this->assertInstanceOf(AbstractResponseFactory::class, $factory);
        $this->assertNotInstanceOf(AgentResponseFactory::class, $factory);
        $this->assertTrue($factory->supports($request));
    }

    public function testDefaultFactoryHydration(): void
    {
        $request = $this->createMock(\HttpClientBundle\Request\RequestInterface::class);
        $factory = $this->resolver->resolve($request);

        $testData = ['key' => 'value', 'number' => 123];
        $result = $this->invokePrivateMethod($factory, 'hydrate', [$testData]);

        $this->assertEquals($testData, $result);
    }

    public function testAgentFactoryCurrentRequestSet(): void
    {
        $request = $this->createMock(CreateAgentRequest::class);
        $factory = $this->resolver->resolve($request);

        $this->assertInstanceOf(AgentResponseFactory::class, $factory);
        // AgentResponseFactory应该已经设置了当前请求
        $this->assertTrue($factory->supports($request));
    }

    /**
     * 调用私有方法的辅助函数
     */
    private function invokePrivateMethod(object $object, string $methodName, array $parameters = []): mixed
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}