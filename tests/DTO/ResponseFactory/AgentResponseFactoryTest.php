<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\DTO\ResponseFactory;

use HttpClientBundle\Request\RequestInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\DTO\AgentDataDto;
use Tourze\RAGFlowApiBundle\DTO\ResponseFactory\AgentResponseFactory;
use Tourze\RAGFlowApiBundle\Request\CreateAgentRequest;
use Tourze\RAGFlowApiBundle\Request\DeleteAgentRequest;
use Tourze\RAGFlowApiBundle\Request\GetAgentRequest;
use Tourze\RAGFlowApiBundle\Request\ListAgentsRequest;
use Tourze\RAGFlowApiBundle\Request\UpdateAgentRequest;

/**
 * 测试Agent响应工厂
 * @internal
 */
#[CoversClass(AgentResponseFactory::class)]
class AgentResponseFactoryTest extends TestCase
{
    private AgentResponseFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new AgentResponseFactory();
    }

    public function testSupportsCreateAgentRequest(): void
    {
        $request = $this->createMock(CreateAgentRequest::class);
        $this->assertTrue($this->factory->supports($request));
    }

    public function testSupportsUpdateAgentRequest(): void
    {
        $request = $this->createMock(UpdateAgentRequest::class);
        $this->assertTrue($this->factory->supports($request));
    }

    public function testSupportsDeleteAgentRequest(): void
    {
        $request = $this->createMock(DeleteAgentRequest::class);
        $this->assertTrue($this->factory->supports($request));
    }

    public function testSupportsGetAgentRequest(): void
    {
        $request = $this->createMock(GetAgentRequest::class);
        $this->assertTrue($this->factory->supports($request));
    }

    public function testSupportsListAgentsRequest(): void
    {
        $request = $this->createMock(ListAgentsRequest::class);
        $this->assertTrue($this->factory->supports($request));
    }

    public function testDoesNotSupportOtherRequest(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $this->assertFalse($this->factory->supports($request));
    }

    public function testHydrateForListAgentsRequest(): void
    {
        $request = $this->createMock(ListAgentsRequest::class);
        $this->factory->setCurrentRequest($request);

        $testData = [
            ['id' => '1', 'title' => 'Agent 1'],
            ['id' => '2', 'title' => 'Agent 2'],
        ];

        $result = $this->invokePrivateMethod($this->factory, 'hydrate', [$testData]);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(AgentDataDto::class, $result[0]);
        $this->assertInstanceOf(AgentDataDto::class, $result[1]);
        $this->assertEquals('Agent 1', $result[0]->title);
        $this->assertEquals('Agent 2', $result[1]->title);
    }

    public function testHydrateForSingleAgentRequest(): void
    {
        $request = $this->createMock(CreateAgentRequest::class);
        $this->factory->setCurrentRequest($request);

        $testData = ['id' => '1', 'title' => 'Test Agent'];

        $result = $this->invokePrivateMethod($this->factory, 'hydrate', [$testData]);

        $this->assertInstanceOf(AgentDataDto::class, $result);
        $this->assertEquals('Test Agent', $result->title);
        $this->assertEquals('1', $result->id);
    }

    public function testHydrateForEmptyData(): void
    {
        $request = $this->createMock(DeleteAgentRequest::class);
        $this->factory->setCurrentRequest($request);

        $testData = [];

        $result = $this->invokePrivateMethod($this->factory, 'hydrate', [$testData]);

        $this->assertEquals([], $result);
    }

    public function testSetCurrentRequest(): void
    {
        $request = $this->createMock(CreateAgentRequest::class);

        $this->factory->setCurrentRequest($request);

        // 验证请求已设置（通过supports方法验证）
        $this->assertTrue($this->factory->supports($request));
    }

    public function testCreateApiResponse(): void
    {
        $request = $this->createMock(CreateAgentRequest::class);
        $this->factory->setCurrentRequest($request);

        $payload = [
            'code' => 200,
            'message' => 'success',
            'data' => ['id' => '1', 'title' => 'Test Agent'],
        ];

        $apiResponse = $this->factory->create($payload);

        $this->assertTrue($apiResponse->isSuccess());
        $this->assertEquals('success', $apiResponse->getMessage());
        $this->assertInstanceOf(AgentDataDto::class, $apiResponse->getData());
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
