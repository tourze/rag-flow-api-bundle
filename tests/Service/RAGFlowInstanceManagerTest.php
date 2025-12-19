<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Exception\InstanceNotFoundException;
use Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManager;

/**
 * @internal
 */
#[CoversClass(RAGFlowInstanceManager::class)]
#[RunTestsInSeparateProcesses]
class RAGFlowInstanceManagerTest extends AbstractIntegrationTestCase
{
    private RAGFlowInstanceManager $instanceManager;

    protected function onSetUp(): void
    {
        // 通过容器获取服务而不是直接实例化
        $this->instanceManager = self::getService('Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManager');
    }

    public function testCreateInstance(): void
    {
        $config = [
            'name' => 'test-instance-create-' . uniqid(),
            'api_url' => 'https://test.com/api',
            'api_key' => 'test-key',
            'description' => 'Test instance',
            'timeout' => 30,
            'enabled' => true,
        ];

        $instance = $this->instanceManager->createInstance($config);

        $this->assertInstanceOf(RAGFlowInstance::class, $instance);
        $this->assertEquals($config['name'], $instance->getName());
        $this->assertEquals('https://test.com/api', $instance->getApiUrl());
        $this->assertEquals('Test instance', $instance->getDescription());
        $this->assertEquals(30, $instance->getTimeout());
        $this->assertTrue($instance->isEnabled());
    }

    public function testGetClient(): void
    {
        // 先创建一个实例
        $config = [
            'name' => 'test-instance-client-' . uniqid(),
            'api_url' => 'https://test.com/api',
            'api_key' => 'test-key',
            'enabled' => true,
        ];

        $instance = $this->instanceManager->createInstance($config);

        $client = $this->instanceManager->getClient($instance->getName());
        $this->assertEquals($instance->getName(), $client->getInstance()->getName());
    }

    public function testGetDefaultClient(): void
    {
        // 先创建一个默认实例
        $config = [
            'name' => 'default',
            'api_url' => 'https://test.com/api',
            'api_key' => 'test-key',
            'enabled' => true,
        ];

        try {
            $this->instanceManager->createInstance($config);
        } catch (\Exception $e) {
            // 如果实例已存在则跳过
        }

        $client = $this->instanceManager->getDefaultClient();
        // 验证能获取到默认客户端，实例名称可能因测试环境不同而异
        $this->assertNotNull($client);
        $this->assertNotEmpty($client->getInstance()->getName());
    }

    public function testCheckHealthFailure(): void
    {
        // 创建一个无效URL的实例来测试健康检查失败
        $config = [
            'name' => 'test-instance-health-fail-' . uniqid(),
            'api_url' => 'https://invalid-url-that-does-not-exist.com/api',
            'api_key' => 'test-key',
            'enabled' => true,
        ];

        $instance = $this->instanceManager->createInstance($config);

        $result = $this->instanceManager->checkHealth($instance->getName());
        $this->assertFalse($result);
    }

    public function testCheckHealthInstanceNotFound(): void
    {
        $this->expectException(InstanceNotFoundException::class);

        $this->instanceManager->checkHealth('non-existent-instance-' . uniqid());
    }
}
