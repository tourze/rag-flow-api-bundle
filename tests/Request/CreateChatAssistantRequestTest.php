<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use HttpClientBundle\Request\AutoRetryRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Request\CreateChatAssistantRequest;
use Tourze\RAGFlowApiBundle\Tests\TypeAssertionTrait;

/**
 * @internal
 */
#[CoversClass(CreateChatAssistantRequest::class)]
class CreateChatAssistantRequestTest extends TestCase
{
    use TypeAssertionTrait;
    private string $testName;

    /** @var array<string> */
    private array $testDatasetIds;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testName = 'Test Chat Assistant';
        $this->testDatasetIds = ['dataset1', 'dataset2', 'dataset3'];
    }

    #[Test]
    public function testGetRequestPathReturnsCorrectPath(): void
    {
        $request = new CreateChatAssistantRequest($this->testName, $this->testDatasetIds);
        $this->assertEquals('/api/v1/chats', $request->getRequestPath());
    }

    #[Test]
    public function testGetRequestMethodReturnsPost(): void
    {
        $request = new CreateChatAssistantRequest($this->testName, $this->testDatasetIds);
        $this->assertEquals('POST', $request->getRequestMethod());
    }

    #[Test]
    public function testGetRequestOptionsWithMinimalParameters(): void
    {
        $request = new CreateChatAssistantRequest($this->testName, $this->testDatasetIds);
        $options = $request->getRequestOptions();

        $this->assertArrayHasKey('json', $options);

        $json = $options['json'];
        self::assertArrayAccessible($json);
        $this->assertEquals($this->testName, $json['name']);
        $this->assertEquals($this->testDatasetIds, $json['dataset_ids']);
        $this->assertArrayNotHasKey('avatar', $json);
        $this->assertArrayNotHasKey('llm', $json);
        $this->assertArrayNotHasKey('prompt', $json);
    }

    #[Test]
    public function testGetRequestOptionsWithAllParameters(): void
    {
        $avatar = 'https://example.com/avatar.png';
        $llm = [
            'model' => 'gpt-3.5-turbo',
            'temperature' => 0.7,
            'max_tokens' => 1000,
        ];
        $prompt = [
            'system' => 'You are a helpful assistant.',
            'user' => 'Please help me with my questions.',
        ];

        $request = new CreateChatAssistantRequest(
            $this->testName,
            $this->testDatasetIds,
            $avatar,
            $llm,
            $prompt
        );

        $options = $request->getRequestOptions();
        $json = $options['json'];
        self::assertArrayAccessible($json);

        $this->assertEquals($this->testName, $json['name']);
        $this->assertEquals($this->testDatasetIds, $json['dataset_ids']);
        $this->assertEquals($avatar, $json['avatar']);
        $this->assertEquals($llm, $json['llm']);
        $this->assertEquals($prompt, $json['prompt']);
    }

    #[Test]
    public function testGetRequestOptionsWithPartialParameters(): void
    {
        $llm = ['model' => 'gpt-4'];

        $request = new CreateChatAssistantRequest(
            $this->testName,
            $this->testDatasetIds,
            null,
            $llm
        );

        $options = $request->getRequestOptions();
        $json = $options['json'];
        self::assertArrayAccessible($json);

        $this->assertEquals($this->testName, $json['name']);
        $this->assertEquals($this->testDatasetIds, $json['dataset_ids']);
        $this->assertArrayNotHasKey('avatar', $json);
        $this->assertEquals($llm, $json['llm']);
        $this->assertArrayNotHasKey('prompt', $json);
    }

    #[Test]
    public function testImplementsAutoRetryRequest(): void
    {
        $request = new CreateChatAssistantRequest($this->testName, $this->testDatasetIds);
        $this->assertInstanceOf(AutoRetryRequest::class, $request);
    }

    #[Test]
    public function testGetMaxRetries(): void
    {
        $request = new CreateChatAssistantRequest($this->testName, $this->testDatasetIds);
        $this->assertEquals(3, $request->getMaxRetries());
    }

    #[Test]
    public function testConstructorAcceptsEmptyDatasetIds(): void
    {
        $request = new CreateChatAssistantRequest($this->testName, []);
        $options = $request->getRequestOptions();
        $json = $options['json'];
        self::assertArrayAccessible($json);

        $this->assertEquals([], $json['dataset_ids']);
    }
}
