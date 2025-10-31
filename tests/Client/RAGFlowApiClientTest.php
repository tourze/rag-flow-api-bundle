<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Client;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Client\RAGFlowApiClient;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Service\ChunkService;
use Tourze\RAGFlowApiBundle\Service\ConversationService;
use Tourze\RAGFlowApiBundle\Service\DatasetService;
use Tourze\RAGFlowApiBundle\Service\DocumentService;

/**
 * @internal
 */
#[CoversClass(RAGFlowApiClient::class)]
#[RunTestsInSeparateProcesses]
class RAGFlowApiClientTest extends AbstractIntegrationTestCase
{
    private RAGFlowApiClient $client;

    private RAGFlowInstance $instance;

    protected function onSetUp(): void
    {
        $this->instance = new RAGFlowInstance();
        $this->instance->setName('test-instance');
        $this->instance->setApiUrl('https://test.com/api');
        $this->instance->setApiKey('test-key');
        $this->instance->setEnabled(true);

        // 通过工厂服务创建客户端
        $factory = self::getService('Tourze\RAGFlowApiBundle\Service\RAGFlowApiClientFactory');
        $this->client = $factory->createClient($this->instance);
    }

    public function testGetInstance(): void
    {
        $result = $this->client->getInstance();
        $this->assertSame($this->instance, $result);
    }

    public function testDatasetsService(): void
    {
        $service = $this->client->datasets();
        $this->assertInstanceOf(DatasetService::class, $service);
    }

    public function testDocumentsService(): void
    {
        $service = $this->client->documents();
        $this->assertInstanceOf(DocumentService::class, $service);
    }

    public function testChunksService(): void
    {
        $service = $this->client->chunks();
        $this->assertInstanceOf(ChunkService::class, $service);
    }

    public function testConversationsService(): void
    {
        $service = $this->client->conversations();
        $this->assertInstanceOf(ConversationService::class, $service);
    }

    public function testCheckHealth(): void
    {
        // This method returns true for basic health check
        $instanceName = $this->instance->getName();

        // Since we're using a test instance, the method should return true
        $result = $this->client->checkHealth($instanceName);
        $this->assertTrue($result);
    }

    public function testCreateAgent(): void
    {
        $agentData = [
            'name' => 'Test Agent',
            'description' => 'Test agent description',
            'llm_model' => 'gpt-3.5-turbo',
        ];

        // This method requires actual API calls
        $this->expectException(\Exception::class);
        $this->client->createAgent($agentData);
    }

    public function testUpdateAgent(): void
    {
        $agentId = 'test-agent-id';
        $updateData = [
            'name' => 'Updated Agent Name',
            'description' => 'Updated description',
        ];

        // This method requires actual API calls
        $this->expectException(\Exception::class);
        $this->client->updateAgent($agentId, $updateData);
    }

    public function testDeleteAgent(): void
    {
        $agentId = 'test-agent-id';

        // This method requires actual API calls
        $this->expectException(\Exception::class);
        $this->client->deleteAgent($agentId);
    }

    public function testCreateInstance(): void
    {
        $config = [
            'name' => 'New Test Instance',
            'api_url' => 'https://newtest.com/api',
            'api_key' => 'new-test-key',
        ];

        // This method returns the current instance (RAGFlowApiClient is single-instance)
        $instance = $this->client->createInstance($config);

        $this->assertInstanceOf(RAGFlowInstance::class, $instance);
        // The returned instance should be the same as the one used to create the client
        $this->assertSame($this->instance, $instance);
    }
}
