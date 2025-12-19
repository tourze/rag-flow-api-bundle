<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowAgent;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Service\AgentRequestValidator;

/**
 * @internal
 */
#[CoversClass(AgentRequestValidator::class)]
#[RunTestsInSeparateProcesses]
class AgentRequestValidatorTest extends AbstractIntegrationTestCase
{
    private AgentRequestValidator $validator;

    protected function onSetUp(): void
    {
        $this->validator = self::getService(AgentRequestValidator::class);
    }

    public function testServiceCreation(): void
    {
        $this->assertInstanceOf(AgentRequestValidator::class, $this->validator);
    }

    public function testValidateCreateDataWithValidData(): void
    {
        $data = [
            'title' => 'Valid Title',
            'instance_id' => 123,
            'description' => 'Valid Description',
        ];

        $result = $this->validator->validateCreateData($data);

        $this->assertNull($result);
    }

    public function testValidateCreateDataWithMissingTitle(): void
    {
        $data = [
            'instance_id' => 123,
        ];

        $result = $this->validator->validateCreateData($data);

        $this->assertNotNull($result);
        $this->assertEquals(400, $result->getStatusCode());
        $content = json_decode($result->getContent() !== null ? $result->getContent() : '', true);
        $this->assertIsArray($content);
        $this->assertStringContainsString('标题不能为空', $content['message'] ?? '');
    }

    public function testValidateCreateDataWithEmptyTitle(): void
    {
        $data = [
            'title' => '',
            'instance_id' => 123,
        ];

        $result = $this->validator->validateCreateData($data);

        $this->assertNotNull($result);
        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testValidateCreateDataWithMissingInstanceId(): void
    {
        $data = [
            'title' => 'Valid Title',
        ];

        $result = $this->validator->validateCreateData($data);

        $this->assertNotNull($result);
        $this->assertEquals(400, $result->getStatusCode());
        $content = json_decode($result->getContent() !== null ? $result->getContent() : '', true);
        $this->assertIsArray($content);
        $this->assertStringContainsString('RAGFlow实例ID不能为空', $content['message'] ?? '');
    }

    public function testValidateAgentWithValidAgent(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Validator Test Instance');
        $instance->setApiUrl('https://validator-test.example.com/api');
        $instance->setApiKey('validator-test-key');

        $agent = new RAGFlowAgent();
        $agent->setTitle('Valid Agent');
        $agent->setRagFlowInstance($instance);
        $agent->setStatus('draft');
        $agent->setDsl([]);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($agent);

        $result = $this->validator->validateAgent($agent);

        $this->assertNull($result);
    }

    public function testValidateAgentWithInvalidAgent(): void
    {
        $agent = new RAGFlowAgent();
        // 不设置必需字段

        $result = $this->validator->validateAgent($agent);

        // 由于缺少必需字段，应该返回错误响应
        $this->assertNotNull($result);
        $this->assertEquals(400, $result->getStatusCode());
    }
}
