<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowAgent;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Service\AgentDataFormatter;

/**
 * @internal
 */
#[CoversClass(AgentDataFormatter::class)]
#[RunTestsInSeparateProcesses]
class AgentDataFormatterTest extends AbstractIntegrationTestCase
{
    private AgentDataFormatter $formatter;

    protected function onSetUp(): void
    {
        $this->formatter = self::getService(AgentDataFormatter::class);
    }

    public function testServiceCreation(): void
    {
        $this->assertInstanceOf(AgentDataFormatter::class, $this->formatter);
    }

    public function testFormatSingleAgentForList(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Formatter Test Instance');
        $instance->setApiUrl('https://formatter-test.example.com/api');
        $instance->setApiKey('formatter-test-key');

        $agent = new RAGFlowAgent();
        $agent->setTitle('Test Agent for Formatting');
        $agent->setDescription('Test Description');
        $agent->setRagFlowInstance($instance);
        $agent->setStatus('draft');
        $agent->setRemoteId('remote-123');
        $agent->setDsl([]);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($agent);

        $formatted = $this->formatter->formatSingleAgentForList($agent);

        $this->assertIsArray($formatted);
        $this->assertArrayHasKey('id', $formatted);
        $this->assertArrayHasKey('title', $formatted);
        $this->assertArrayHasKey('description', $formatted);
        $this->assertArrayHasKey('status', $formatted);
        $this->assertArrayHasKey('remote_id', $formatted);
        $this->assertArrayHasKey('instance_name', $formatted);
        $this->assertArrayHasKey('create_time', $formatted);
        $this->assertArrayHasKey('last_sync_time', $formatted);

        $this->assertEquals('Test Agent for Formatting', $formatted['title']);
        $this->assertEquals('draft', $formatted['status']);
    }

    public function testFormatAgentsForList(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Multi Agent Test');
        $instance->setApiUrl('https://multi-agent.example.com/api');
        $instance->setApiKey('multi-agent-key');

        $this->persistAndFlush($instance);

        $agent1 = new RAGFlowAgent();
        $agent1->setTitle('Agent 1');
        $agent1->setRagFlowInstance($instance);
        $agent1->setStatus('draft');
        $agent1->setDsl([]);

        $agent2 = new RAGFlowAgent();
        $agent2->setTitle('Agent 2');
        $agent2->setRagFlowInstance($instance);
        $agent2->setStatus('published');
        $agent2->setDsl([]);

        $this->persistAndFlush($agent1);
        $this->persistAndFlush($agent2);

        $formatted = $this->formatter->formatAgentsForList([$agent1, $agent2]);

        $this->assertIsArray($formatted);
        $this->assertCount(2, $formatted);
        $this->assertEquals('Agent 1', $formatted[0]['title']);
        $this->assertEquals('Agent 2', $formatted[1]['title']);
    }

    public function testFormatAgentDetail(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Detail Test Instance');
        $instance->setApiUrl('https://detail-test.example.com/api');
        $instance->setApiKey('detail-test-key');

        $agent = new RAGFlowAgent();
        $agent->setTitle('Detailed Agent');
        $agent->setDescription('Detailed Description');
        $agent->setRagFlowInstance($instance);
        $agent->setStatus('published');
        $agent->setRemoteId('detail-remote-456');
        $agent->setDsl(['graph' => 'detailed', 'nodes' => ['node1', 'node2']]);
        $agent->setSyncErrorMessage(null);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($agent);

        $formatted = $this->formatter->formatAgentDetail($agent);

        $this->assertIsArray($formatted);
        $this->assertArrayHasKey('id', $formatted);
        $this->assertArrayHasKey('title', $formatted);
        $this->assertArrayHasKey('description', $formatted);
        $this->assertArrayHasKey('dsl', $formatted);
        $this->assertArrayHasKey('status', $formatted);
        $this->assertArrayHasKey('remote_id', $formatted);
        $this->assertArrayHasKey('instance', $formatted);
        $this->assertArrayHasKey('create_time', $formatted);
        $this->assertArrayHasKey('update_time', $formatted);
        $this->assertArrayHasKey('last_sync_time', $formatted);
        $this->assertArrayHasKey('sync_error_message', $formatted);

        $this->assertEquals('Detailed Agent', $formatted['title']);
        $this->assertEquals('published', $formatted['status']);
        $this->assertIsArray($formatted['dsl']);
        $this->assertIsArray($formatted['instance']);
        $this->assertArrayHasKey('id', $formatted['instance']);
        $this->assertArrayHasKey('name', $formatted['instance']);
    }
}
