<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service\Mapper;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Entity\LlmModel;
use Tourze\RAGFlowApiBundle\Service\Mapper\LlmModelMapper;

/**
 * @internal
 */
#[CoversClass(LlmModelMapper::class)]
final class LlmModelMapperTest extends TestCase
{
    private LlmModelMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new LlmModelMapper();
    }

    public function testMapApiDataToEntityWithCompleteData(): void
    {
        $llmModel = new LlmModel();
        $apiData = [
            'llm_name' => 'GPT-4',
            'available' => true,
            'model_type' => 'chat',
            'max_tokens' => 8192,
            'status' => 1,
            'is_tools' => true,
            'tags' => ['gpt', 'openai'],
            'create_date' => '2024-01-01 00:00:00',
            'create_time' => '2024-01-01 00:00:00',
            'update_date' => '2024-01-02 00:00:00',
            'update_time' => '2024-01-02 00:00:00',
        ];

        $this->mapper->mapApiDataToEntity($llmModel, $apiData, 'OpenAI');

        $this->assertSame('GPT-4', $llmModel->getLlmName());
        $this->assertSame('OpenAI', $llmModel->getProviderName());
        $this->assertTrue($llmModel->getAvailable());
        $this->assertSame('chat', $llmModel->getModelType());
        $this->assertSame(8192, $llmModel->getMaxTokens());
        $this->assertSame(1, $llmModel->getStatus());
        $this->assertTrue($llmModel->getIsTools());
        $this->assertSame(['gpt', 'openai'], $llmModel->getTags());
        $this->assertNotNull($llmModel->getApiCreateDate());
        $this->assertNotNull($llmModel->getApiCreateTime());
        $this->assertNotNull($llmModel->getApiUpdateDate());
        $this->assertNotNull($llmModel->getApiUpdateTime());
    }

    public function testMapApiDataToEntityWithMinimalData(): void
    {
        $llmModel = new LlmModel();
        $apiData = [
            'llm_name' => 'Claude',
        ];

        $this->mapper->mapApiDataToEntity($llmModel, $apiData, 'Anthropic');

        $this->assertSame('Claude', $llmModel->getLlmName());
        $this->assertSame('Anthropic', $llmModel->getProviderName());
        $this->assertFalse($llmModel->getAvailable());
        $this->assertSame('unknown', $llmModel->getModelType());
    }

    public function testMapApiDataToEntityWithInvalidAvailableType(): void
    {
        $llmModel = new LlmModel();
        $apiData = [
            'llm_name' => 'Test Model',
            'available' => 'yes',
        ];

        $this->mapper->mapApiDataToEntity($llmModel, $apiData, 'TestProvider');

        $this->assertFalse($llmModel->getAvailable());
    }

    public function testMapApiDataToEntityWithInvalidModelType(): void
    {
        $llmModel = new LlmModel();
        $apiData = [
            'llm_name' => 'Test Model',
            'model_type' => 123,
        ];

        $this->mapper->mapApiDataToEntity($llmModel, $apiData, 'TestProvider');

        $this->assertSame('unknown', $llmModel->getModelType());
    }

    public function testMapApiDataToEntityWithInvalidTagsType(): void
    {
        $llmModel = new LlmModel();
        $apiData = [
            'llm_name' => 'Test Model',
            'tags' => ['valid', 123, 'another-valid'],
        ];

        $this->mapper->mapApiDataToEntity($llmModel, $apiData, 'TestProvider');

        $this->assertSame(['valid', 'another-valid'], $llmModel->getTags());
    }

    public function testMapApiDataToEntityWithInvalidDateFormats(): void
    {
        $llmModel = new LlmModel();
        $apiData = [
            'llm_name' => 'Test Model',
            'create_date' => 'invalid-date',
            'create_time' => 'invalid-time',
            'update_date' => 'invalid-date',
            'update_time' => 'invalid-time',
        ];

        $this->mapper->mapApiDataToEntity($llmModel, $apiData, 'TestProvider');

        $this->assertNull($llmModel->getApiCreateDate());
        $this->assertNull($llmModel->getApiCreateTime());
        $this->assertNull($llmModel->getApiUpdateDate());
        $this->assertNull($llmModel->getApiUpdateTime());
    }

    public function testMapApiDataToEntityIgnoresNonIntegerMaxTokens(): void
    {
        $llmModel = new LlmModel();
        $apiData = [
            'llm_name' => 'Test Model',
            'max_tokens' => 'invalid',
        ];

        $this->mapper->mapApiDataToEntity($llmModel, $apiData, 'TestProvider');

        $this->assertNull($llmModel->getMaxTokens());
    }

    public function testMapApiDataToEntityIgnoresNonIntegerStatus(): void
    {
        $llmModel = new LlmModel();
        $apiData = [
            'llm_name' => 'Test Model',
            'status' => 'active',
        ];

        $this->mapper->mapApiDataToEntity($llmModel, $apiData, 'TestProvider');

        $this->assertNull($llmModel->getStatus());
    }

    public function testMapApiDataToEntityIgnoresNonBooleanIsTools(): void
    {
        $llmModel = new LlmModel();
        $apiData = [
            'llm_name' => 'Test Model',
            'is_tools' => 'yes',
        ];

        $this->mapper->mapApiDataToEntity($llmModel, $apiData, 'TestProvider');

        $this->assertNull($llmModel->getIsTools());
    }

    public function testMapApiDataToEntityWithValidTimestamps(): void
    {
        $llmModel = new LlmModel();
        $apiData = [
            'llm_name' => 'Test Model',
            'create_date' => '2024-01-15 10:30:45',
            'create_time' => '2024-01-15 10:30:45',
            'update_date' => '2024-01-20 15:45:30',
            'update_time' => '2024-01-20 15:45:30',
        ];

        $this->mapper->mapApiDataToEntity($llmModel, $apiData, 'TestProvider');

        $this->assertInstanceOf(\DateTimeImmutable::class, $llmModel->getApiCreateDate());
        $this->assertSame('2024-01-15', $llmModel->getApiCreateDate()->format('Y-m-d'));
        $this->assertInstanceOf(\DateTimeImmutable::class, $llmModel->getApiCreateTime());
        $this->assertSame('2024-01-15', $llmModel->getApiCreateTime()->format('Y-m-d'));
        $this->assertInstanceOf(\DateTimeImmutable::class, $llmModel->getApiUpdateDate());
        $this->assertSame('2024-01-20', $llmModel->getApiUpdateDate()->format('Y-m-d'));
        $this->assertInstanceOf(\DateTimeImmutable::class, $llmModel->getApiUpdateTime());
        $this->assertSame('2024-01-20', $llmModel->getApiUpdateTime()->format('Y-m-d'));
    }
}
