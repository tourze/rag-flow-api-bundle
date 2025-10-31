<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowAgent;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Service\AgentApiService;

/**
 * @internal
 */
#[CoversClass(AgentApiService::class)]
#[RunTestsInSeparateProcesses]
class AgentApiServiceTest extends AbstractIntegrationTestCase
{
    private AgentApiService $agentApiService;

    protected function onSetUp(): void
    {
        $this->agentApiService = self::getService(AgentApiService::class);
    }

    public function testServiceCreation(): void
    {
        $this->assertInstanceOf(AgentApiService::class, $this->agentApiService);
    }

    public function testCreateAgentFailsWithoutValidInstance(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Invalid Test Instance');
        $instance->setApiUrl('https://invalid-test.example.com/api');
        $instance->setApiKey('invalid-test-key');

        $agent = new RAGFlowAgent();
        $agent->setTitle('Test Agent for API');
        $agent->setDescription('Test Description');
        $agent->setRagFlowInstance($instance);
        $agent->setStatus('draft');
        $agent->setDsl(['graph' => 'test']);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($agent);

        // 由于没有真实的API，预期会失败
        $result = $this->agentApiService->createAgent($agent);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
    }

    public function testUpdateAgentWithoutRemoteIdCallsCreate(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Update Test Instance');
        $instance->setApiUrl('https://update-test.example.com/api');
        $instance->setApiKey('update-test-key');

        $agent = new RAGFlowAgent();
        $agent->setTitle('Agent Without Remote ID');
        $agent->setRagFlowInstance($instance);
        $agent->setStatus('draft');
        $agent->setDsl([]);
        $agent->setRemoteId(null);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($agent);

        // 预期会调用createAgent
        $result = $this->agentApiService->updateAgent($agent);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function testDeleteAgentWithoutRemoteId(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Delete Test Instance');
        $instance->setApiUrl('https://delete-test.example.com/api');
        $instance->setApiKey('delete-test-key');

        $agent = new RAGFlowAgent();
        $agent->setTitle('Agent to Delete');
        $agent->setRagFlowInstance($instance);
        $agent->setStatus('draft');
        $agent->setDsl([]);
        $agent->setRemoteId(null);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($agent);

        $result = $this->agentApiService->deleteAgent($agent);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('not synced', $result['message']);
    }

    public function testSyncAllAgents(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Sync Test Instance');
        $instance->setApiUrl('https://sync-test.example.com/api');
        $instance->setApiKey('sync-test-key');

        $this->persistAndFlush($instance);

        $result = $this->agentApiService->syncAllAgents($instance);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('data', $result);
    }

    public function testGetAgentList(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('List Test Instance');
        $instance->setApiUrl('https://list-test.example.com/api');
        $instance->setApiKey('list-test-key');

        $this->persistAndFlush($instance);

        $result = $this->agentApiService->getAgentList($instance, 1, 20);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }
}
