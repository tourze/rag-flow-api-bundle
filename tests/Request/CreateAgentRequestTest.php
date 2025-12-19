<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tourze\RAGFlowApiBundle\Request\CreateAgentRequest;

/**
 * @internal
 */
#[CoversClass(CreateAgentRequest::class)]
class CreateAgentRequestTest extends RequestTestCase
{
    /** @var array<string, mixed> */
    private array $testData;

    protected function onSetUp(): void
    {
        $this->testData = [
            'name' => 'Test Agent',
            'description' => 'A test agent for unit testing',
            'prompt' => 'You are a helpful assistant',
        ];
    }

    #[Test]
    public function testGetRequestPathReturnsCorrectPath(): void
    {
        $request = new CreateAgentRequest($this->testData);
        $this->assertEquals('/api/v1/agents', $request->getRequestPath());
    }

    #[Test]
    public function testGetRequestMethodReturnsPost(): void
    {
        $request = new CreateAgentRequest($this->testData);
        $this->assertEquals('POST', $request->getRequestMethod());
    }

    #[Test]
    public function testGetRequestOptionsReturnsJsonData(): void
    {
        $request = new CreateAgentRequest($this->testData);
        $options = $request->getRequestOptions();

        $this->assertIsArray($options);
        $this->assertArrayHasKey('json', $options);
        $this->assertEquals($this->testData, $options['json']);
    }

    #[Test]
    public function testConstructorAcceptsEmptyData(): void
    {
        $request = new CreateAgentRequest([]);
        $options = $request->getRequestOptions();

        $this->assertNotNull($options);
        $this->assertEquals(['json' => []], $options);
    }

    #[Test]
    public function testConstructorAcceptsComplexData(): void
    {
        $complexData = [
            'name' => 'Complex Agent',
            'settings' => [
                'temperature' => 0.7,
                'max_tokens' => 1000,
            ],
            'metadata' => [
                'version' => '1.0',
                'author' => 'Test',
            ],
        ];

        $request = new CreateAgentRequest($complexData);
        $options = $request->getRequestOptions();

        $this->assertNotNull($options);
        $this->assertEquals($complexData, $options['json']);
    }
}
