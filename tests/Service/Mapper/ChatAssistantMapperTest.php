<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service\Mapper;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Entity\ChatAssistant;
use Tourze\RAGFlowApiBundle\Service\Mapper\ChatAssistantMapper;

/**
 * @internal
 */
#[CoversClass(ChatAssistantMapper::class)]
final class ChatAssistantMapperTest extends TestCase
{
    private ChatAssistantMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new ChatAssistantMapper();
    }

    public function testMapApiDataToEntityWithCompleteData(): void
    {
        $chatAssistant = new ChatAssistant();
        $apiData = [
            'name' => 'Test Assistant',
            'description' => 'Test Description',
            'avatar' => 'https://example.com/avatar.png',
            'language' => 'zh',
            'status' => 'active',
            'prompt_type' => 'enhanced',
            'do_refer' => 'yes',
            'tenant_id' => 'test-tenant',
            'dataset_ids' => ['dataset1', 'dataset2'],
            'llm' => [
                'model_name' => 'gpt-4',
                'temperature' => 0.7,
                'top_p' => 0.9,
                'presence_penalty' => 0.1,
                'frequency_penalty' => 0.2,
                'max_tokens' => 1000,
            ],
            'prompt' => [
                'rerank_model' => 'test-rerank',
                'opener' => 'Hello!',
                'empty_response' => 'No answer',
                'prompt' => 'You are a helpful assistant',
                'similarity_threshold' => 0.8,
                'keywords_similarity_weight' => 0.5,
                'top_n' => 5,
                'show_quote' => true,
                'variables' => ['var1' => 'value1'],
            ],
            'top_k' => 10,
            'create_time' => 1640995200000, // 2022-01-01 00:00:00 UTC in milliseconds
            'update_time' => 1640995260000, // 2022-01-01 00:01:00 UTC in milliseconds
        ];

        $this->mapper->mapApiDataToEntity($chatAssistant, $apiData);

        // 验证基础字段映射
        $this->assertSame('Test Assistant', $chatAssistant->getName());
        $this->assertSame('Test Description', $chatAssistant->getDescription());
        $this->assertSame('https://example.com/avatar.png', $chatAssistant->getAvatar());
        $this->assertSame('zh', $chatAssistant->getLanguage());
        $this->assertSame('active', $chatAssistant->getStatus());
        $this->assertSame('enhanced', $chatAssistant->getPromptType());
        $this->assertSame('yes', $chatAssistant->getDoRefer());
        $this->assertSame('test-tenant', $chatAssistant->getTenantId());

        // 验证数据集ID数组
        $this->assertSame(['dataset1', 'dataset2'], $chatAssistant->getDatasetIds());

        // 验证LLM配置
        $this->assertSame('gpt-4', $chatAssistant->getLlmModel());
        $this->assertSame(0.7, $chatAssistant->getTemperature());
        $this->assertSame(0.9, $chatAssistant->getTopP());
        $this->assertSame(0.1, $chatAssistant->getPresencePenalty());
        $this->assertSame(0.2, $chatAssistant->getFrequencyPenalty());
        $this->assertSame(1000, $chatAssistant->getMaxTokens());

        // 验证Prompt配置
        $this->assertSame('test-rerank', $chatAssistant->getRerankModel());
        $this->assertSame('Hello!', $chatAssistant->getOpener());
        $this->assertSame('No answer', $chatAssistant->getEmptyResponse());
        $this->assertSame('You are a helpful assistant', $chatAssistant->getSystemPrompt());
        $this->assertSame(0.8, $chatAssistant->getSimilarityThreshold());
        $this->assertSame(0.5, $chatAssistant->getKeywordsSimilarityWeight());
        $this->assertSame(5, $chatAssistant->getTopN());
        $this->assertTrue($chatAssistant->getShowQuote());
        $this->assertSame(['var1' => 'value1'], $chatAssistant->getVariables());

        // 验证TopK
        $this->assertSame(10, $chatAssistant->getTopK());

        // 验证时间戳转换
        $createTime = $chatAssistant->getRemoteCreateTime();
        $updateTime = $chatAssistant->getRemoteUpdateTime();
        $this->assertNotNull($createTime);
        $this->assertNotNull($updateTime);
        $this->assertSame(1640995200, $createTime->getTimestamp());
        $this->assertSame(1640995260, $updateTime->getTimestamp());
    }

    public function testMapApiDataToEntityWithMinimalData(): void
    {
        $chatAssistant = new ChatAssistant();
        $apiData = [
            'name' => 'Minimal Assistant',
        ];

        $this->mapper->mapApiDataToEntity($chatAssistant, $apiData);

        $this->assertSame('Minimal Assistant', $chatAssistant->getName());
        $this->assertNull($chatAssistant->getDescription());
        $this->assertNull($chatAssistant->getAvatar());
    }

    public function testMapApiDataToEntityIgnoresInvalidDataTypes(): void
    {
        $chatAssistant = new ChatAssistant();
        $apiData = [
            'name' => 'Valid Name',
            'description' => 123, // 无效类型，应被忽略
            'avatar' => null, // null值，应被忽略
            'dataset_ids' => 'not-an-array', // 无效类型，应被忽略
            'llm' => 'not-an-array', // 无效类型，应被忽略
        ];

        $this->mapper->mapApiDataToEntity($chatAssistant, $apiData);

        $this->assertSame('Valid Name', $chatAssistant->getName());
        $this->assertNull($chatAssistant->getDescription()); // 应保持null，因为无效值被忽略
        $this->assertNull($chatAssistant->getAvatar());
        $this->assertNull($chatAssistant->getDatasetIds()); // 应保持默认null值
    }

    public function testMapApiDataToEntityFiltersDatasetIds(): void
    {
        $chatAssistant = new ChatAssistant();
        $apiData = [
            'name' => 'Test Assistant',
            'dataset_ids' => ['valid1', 123, 'valid2', null, 'valid3'], // 混合类型
        ];

        $this->mapper->mapApiDataToEntity($chatAssistant, $apiData);

        // 只有字符串类型的ID应该被保留，保持原始键
        $expected = [0 => 'valid1', 2 => 'valid2', 4 => 'valid3'];
        $this->assertSame($expected, $chatAssistant->getDatasetIds());
    }

    public function testMapApiDataToEntityHandlesLlmFloatFields(): void
    {
        $chatAssistant = new ChatAssistant();
        $apiData = [
            'name' => 'Test Assistant',
            'llm' => [
                'temperature' => 0.5,
                'top_p' => 0.8,
                'presence_penalty' => 0.0,
                'frequency_penalty' => 1.0,
                'max_tokens' => 'not-a-number', // 无效类型，应被忽略
            ],
        ];

        $this->mapper->mapApiDataToEntity($chatAssistant, $apiData);

        $this->assertSame(0.5, $chatAssistant->getTemperature());
        $this->assertSame(0.8, $chatAssistant->getTopP());
        $this->assertSame(0.0, $chatAssistant->getPresencePenalty());
        $this->assertSame(1.0, $chatAssistant->getFrequencyPenalty());
        $this->assertNull($chatAssistant->getMaxTokens()); // 无效类型被忽略
    }

    public function testMapApiDataToEntityConvertsTimestampFromString(): void
    {
        $chatAssistant = new ChatAssistant();
        $apiData = [
            'name' => 'Test Assistant',
            'create_time' => '2022-01-01 12:00:00',
            'update_time' => 'invalid-time',
        ];

        $this->mapper->mapApiDataToEntity($chatAssistant, $apiData);

        $createTime = $chatAssistant->getRemoteCreateTime();
        $updateTime = $chatAssistant->getRemoteUpdateTime();

        $this->assertNotNull($createTime);
        $this->assertSame(1641038400, $createTime->getTimestamp()); // 2022-01-01 12:00:00 UTC

        $this->assertNotNull($updateTime);
        $this->assertSame(0, $updateTime->getTimestamp()); // 无效时间应返回epoch时间
    }

    public function testMapApiDataToEntityHandlesLegacyPromptConfig(): void
    {
        $chatAssistant = new ChatAssistant();
        $apiData = [
            'name' => 'Test Assistant',
            'prompt_config' => [
                'opener' => 'Legacy Hello!',
                'empty_response' => 'Legacy No answer',
                'show_quote' => false,
            ],
        ];

        $this->mapper->mapApiDataToEntity($chatAssistant, $apiData);

        // 验证兼容旧版prompt_config结构
        $this->assertSame('Legacy Hello!', $chatAssistant->getOpener());
        $this->assertSame('Legacy No answer', $chatAssistant->getEmptyResponse());
        $this->assertFalse($chatAssistant->getShowQuote());
    }

    public function testMapApiDataToEntityHandlesPromptNumericFields(): void
    {
        $chatAssistant = new ChatAssistant();
        $apiData = [
            'name' => 'Test Assistant',
            'prompt' => [
                'similarity_threshold' => '0.75', // 数值字符串
                'keywords_similarity_weight' => 0.6, // 浮点数
                'top_n' => 3, // 整数
            ],
        ];

        $this->mapper->mapApiDataToEntity($chatAssistant, $apiData);

        $this->assertSame(0.75, $chatAssistant->getSimilarityThreshold());
        $this->assertSame(0.6, $chatAssistant->getKeywordsSimilarityWeight());
        $this->assertSame(3, $chatAssistant->getTopN());
    }
}
