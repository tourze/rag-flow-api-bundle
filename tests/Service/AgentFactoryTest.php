<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Service\AgentFactory;

/**
 * @internal
 */
#[CoversClass(AgentFactory::class)]
#[RunTestsInSeparateProcesses]
class AgentFactoryTest extends AbstractIntegrationTestCase
{
    private AgentFactory $agentFactory;

    protected function onSetUp(): void
    {
        $this->agentFactory = self::getService(AgentFactory::class);
    }

    public function testServiceCreation(): void
    {
        $this->assertInstanceOf(AgentFactory::class, $this->agentFactory);
    }

    public function testCreateFromData(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Factory Test Instance');
        $instance->setApiUrl('https://factory-test.example.com/api');
        $instance->setApiKey('factory-test-key');

        $data = [
            'title' => 'Test Agent from Factory',
            'description' => 'Test Description',
            'dsl' => ['graph' => 'test', 'nodes' => []],
        ];

        $agent = $this->agentFactory->createFromData($data, $instance);

        $this->assertEquals('Test Agent from Factory', $agent->getTitle());
        $this->assertEquals('Test Description', $agent->getDescription());
        $this->assertEquals(['graph' => 'test', 'nodes' => []], $agent->getDsl());
        $this->assertSame($instance, $agent->getRagFlowInstance());
        $this->assertEquals('draft', $agent->getStatus());
    }

    public function testCreateFromDataWithMinimalData(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Minimal Test Instance');
        $instance->setApiUrl('https://minimal-test.example.com/api');
        $instance->setApiKey('minimal-test-key');

        $data = [
            'title' => 'Minimal Agent',
        ];

        $agent = $this->agentFactory->createFromData($data, $instance);

        $this->assertEquals('Minimal Agent', $agent->getTitle());
        $this->assertNull($agent->getDescription());
        $this->assertEquals([], $agent->getDsl());
        $this->assertEquals('draft', $agent->getStatus());
    }

    public function testCreateFromDataWithInvalidTypes(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Invalid Type Test');
        $instance->setApiUrl('https://invalid-test.example.com/api');
        $instance->setApiKey('invalid-test-key');

        $data = [
            'title' => 123, // Invalid type
            'description' => ['invalid'], // Invalid type
            'dsl' => 'invalid', // Invalid type
        ];

        $agent = $this->agentFactory->createFromData($data, $instance);

        $this->assertEquals('', $agent->getTitle());
        $this->assertNull($agent->getDescription());
        $this->assertEquals([], $agent->getDsl());
    }

    public function testUpdateFields(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Update Test Instance');
        $instance->setApiUrl('https://update-test.example.com/api');
        $instance->setApiKey('update-test-key');

        $data = [
            'title' => 'Original Title',
        ];

        $agent = $this->agentFactory->createFromData($data, $instance);

        $updateData = [
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'dsl' => ['updated' => true],
        ];

        $this->agentFactory->updateFields($agent, $updateData);

        $this->assertEquals('Updated Title', $agent->getTitle());
        $this->assertEquals('Updated Description', $agent->getDescription());
        $this->assertEquals(['updated' => true], $agent->getDsl());
    }

    public function testUpdateFieldsPartial(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Partial Update Test');
        $instance->setApiUrl('https://partial-test.example.com/api');
        $instance->setApiKey('partial-test-key');

        $data = [
            'title' => 'Original Title',
            'description' => 'Original Description',
        ];

        $agent = $this->agentFactory->createFromData($data, $instance);

        // 只更新 title
        $this->agentFactory->updateFields($agent, ['title' => 'New Title']);

        $this->assertEquals('New Title', $agent->getTitle());
        $this->assertEquals('Original Description', $agent->getDescription());
    }
}
