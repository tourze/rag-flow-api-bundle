<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowAgent;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Service\AgentService;

/**
 * @internal
 */
#[CoversClass(AgentService::class)]
#[RunTestsInSeparateProcesses]
class AgentServiceTest extends AbstractIntegrationTestCase
{
    private AgentService $agentService;

    protected function onSetUp(): void
    {
        $this->agentService = self::getService(AgentService::class);
    }

    public function testServiceCreation(): void
    {
        $this->assertInstanceOf(AgentService::class, $this->agentService);
    }

    public function testFindAgentById(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Test Instance');
        $instance->setApiUrl('https://test.example.com/api');
        $instance->setApiKey('test-key');

        $agent = new RAGFlowAgent();
        $agent->setTitle('Test Agent');
        $agent->setDescription('Test Description');
        $agent->setRagFlowInstance($instance);
        $agent->setStatus('draft');
        $agent->setDsl([]);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($agent);

        $agentId = $agent->getId();
        $this->assertNotNull($agentId);

        $foundAgent = $this->agentService->findAgentById($agentId);
        $this->assertInstanceOf(RAGFlowAgent::class, $foundAgent);
        $this->assertEquals('Test Agent', $foundAgent->getTitle());
    }

    public function testFindAgentByIdNotFound(): void
    {
        $result = $this->agentService->findAgentById(999999);
        $this->assertNull($result);
    }

    public function testFindInstance(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Test Instance 2');
        $instance->setApiUrl('https://test2.example.com/api');
        $instance->setApiKey('test-key-2');

        $this->persistAndFlush($instance);

        $instanceId = $instance->getId();
        $this->assertNotNull($instanceId);

        $foundInstance = $this->agentService->findInstance($instanceId);
        $this->assertInstanceOf(RAGFlowInstance::class, $foundInstance);
        $this->assertEquals('Test Instance 2', $foundInstance->getName());
    }

    public function testCreateNotFoundError(): void
    {
        $response = $this->agentService->createNotFoundError('自定义错误消息');

        $this->assertEquals(404, $response->getStatusCode());
        $responseContent = $response->getContent();
        $content = json_decode(false !== $responseContent ? $responseContent : '', true);
        $this->assertIsArray($content);
        $this->assertEquals(404, $content['code']);
        $this->assertEquals('自定义错误消息', $content['message']);
    }

    public function testFindAgentsByFilters(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Test Instance 3');
        $instance->setApiUrl('https://test3.example.com/api');
        $instance->setApiKey('test-key-3');
        $this->persistAndFlush($instance);

        $agent1 = new RAGFlowAgent();
        $agent1->setTitle('Agent 1');
        $agent1->setRagFlowInstance($instance);
        $agent1->setStatus('draft');
        $agent1->setDsl([]);
        $this->persistAndFlush($agent1);

        $agent2 = new RAGFlowAgent();
        $agent2->setTitle('Agent 2');
        $agent2->setRagFlowInstance($instance);
        $agent2->setStatus('published');
        $agent2->setDsl([]);
        $this->persistAndFlush($agent2);

        $instanceId = $instance->getId();
        $filters = [
            'page' => 1,
            'limit' => 10,
            'instance_id' => $instanceId ?? 0,
            'status' => 'draft',
        ];

        $results = $this->agentService->findAgentsByFilters($filters);
        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(1, count($results));
    }

    public function testCountAgentsByFilters(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Test Instance 4');
        $instance->setApiUrl('https://test4.example.com/api');
        $instance->setApiKey('test-key-4');
        $this->persistAndFlush($instance);

        $agent = new RAGFlowAgent();
        $agent->setTitle('Agent for Count');
        $agent->setRagFlowInstance($instance);
        $agent->setStatus('draft');
        $agent->setDsl([]);
        $this->persistAndFlush($agent);

        $instanceId = $instance->getId();
        $filters = [
            'page' => 1,
            'limit' => 10,
            'instance_id' => $instanceId ?? 0,
            'status' => null,
        ];

        $count = $this->agentService->countAgentsByFilters($filters);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testGetAgentStats(): void
    {
        $stats = $this->agentService->getAgentStats();
        $this->assertIsArray($stats);
    }
}
