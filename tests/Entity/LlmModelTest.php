<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\PHPUnitSymfonyKernelTest\DoctrineTrait;
use Tourze\PHPUnitSymfonyKernelTest\ServiceLocatorTrait;
use Tourze\RAGFlowApiBundle\Entity\LlmModel;

/**
 * @internal
 */
#[CoversClass(LlmModel::class)]
class LlmModelTest extends AbstractEntityTestCase
{
    use DoctrineTrait;
    use ServiceLocatorTrait;

    protected function createEntity(): LlmModel
    {
        $model = new LlmModel();
        $model->setFid('test-model');
        $model->setLlmName('test-model');
        $model->setModelType('chat');
        $model->setProviderName('test-provider');

        return $model;
    }

    public function testCreateLlmModel(): void
    {
        $model = new LlmModel();
        $model->setFid('gpt-3.5-turbo');
        $model->setLlmName('gpt-3.5-turbo');
        $model->setModelType('chat');
        $model->setProviderName('OpenAI');
        $model->setMaxTokens(4096);
        $model->setAvailable(true);

        $this->assertEquals('gpt-3.5-turbo', $model->getFid());
        $this->assertEquals('gpt-3.5-turbo', $model->getLlmName());
        $this->assertEquals('chat', $model->getModelType());
        $this->assertEquals('OpenAI', $model->getProviderName());
        $this->assertEquals(4096, $model->getMaxTokens());
        $this->assertTrue($model->getAvailable()); // 使用 getAvailable() 而不是 isAvailable()
    }

    public function testFactoryId(): void
    {
        $model = new LlmModel();
        $model->setLlmName('test-model');
        $model->setModelType('chat');
        $model->setProviderName('test-provider');

        // 测试工厂ID (fid)
        $model->setFid('OpenAI');
        $this->assertEquals('OpenAI', $model->getFid());

        $model->setFid('Anthropic');
        $this->assertEquals('Anthropic', $model->getFid());

        $model->setFid('DeepSeek');
        $this->assertEquals('DeepSeek', $model->getFid());
    }

    public function testModelTypes(): void
    {
        $model = new LlmModel();
        $model->setFid('test-model');
        $model->setLlmName('test-model');
        $model->setProviderName('test-provider');

        // 测试不同的模型类型
        $model->setModelType('chat');
        $this->assertEquals('chat', $model->getModelType());
        $this->assertTrue($model->isChatModel()); // 使用实体提供的辅助方法

        $model->setModelType('embedding');
        $this->assertEquals('embedding', $model->getModelType());
        $this->assertTrue($model->isEmbeddingModel());

        $model->setModelType('image2text');
        $this->assertEquals('image2text', $model->getModelType());

        $model->setModelType('speech2text');
        $this->assertEquals('speech2text', $model->getModelType());

        $model->setModelType('rerank');
        $this->assertEquals('rerank', $model->getModelType());
        $this->assertTrue($model->isRerankModel());
    }

    public function testAvailabilityAndStatus(): void
    {
        $model = new LlmModel();
        $model->setFid('test-model');
        $model->setLlmName('test-model');
        $model->setModelType('chat');
        $model->setProviderName('test-provider');

        // 测试可用性
        $model->setAvailable(true);
        $this->assertTrue($model->getAvailable()); // 使用 getAvailable() 而不是 isAvailable()

        $model->setAvailable(false);
        $this->assertFalse($model->getAvailable());

        // 测试状态
        $model->setStatus(1);
        $this->assertEquals(1, $model->getStatus());

        $model->setStatus(0);
        $this->assertEquals(0, $model->getStatus());
    }

    public function testMaxTokens(): void
    {
        $model = new LlmModel();
        $model->setFid('test-model');
        $model->setLlmName('test-model');
        $model->setModelType('chat');
        $model->setProviderName('test-provider');

        // 测试不同的token限制
        $model->setMaxTokens(4096);
        $this->assertEquals(4096, $model->getMaxTokens());

        $model->setMaxTokens(8192);
        $this->assertEquals(8192, $model->getMaxTokens());

        $model->setMaxTokens(32768);
        $this->assertEquals(32768, $model->getMaxTokens());

        $model->setMaxTokens(128000);
        $this->assertEquals(128000, $model->getMaxTokens());

        $model->setMaxTokens(1048576); // 1M tokens
        $this->assertEquals(1048576, $model->getMaxTokens());
    }

    public function testToolsSupport(): void
    {
        $model = new LlmModel();
        $model->setFid('test-model');
        $model->setLlmName('test-model');
        $model->setModelType('chat');
        $model->setProviderName('test-provider');

        // 测试工具支持
        $model->setIsTools(true);
        $this->assertTrue($model->getIsTools()); // 使用 getIsTools() 而不是 isTools()

        $model->setIsTools(false);
        $this->assertFalse($model->getIsTools());
    }

    public function testTags(): void
    {
        $model = new LlmModel();
        $model->setFid('test-model');
        $model->setLlmName('test-model');
        $model->setModelType('chat');
        $model->setProviderName('test-provider');

        // 测试标签
        $tags = ['LLM', 'CHAT', '4K'];
        $model->setTags($tags);
        $this->assertEquals($tags, $model->getTags());

        // 测试复杂标签
        $complexTags = ['LLM', 'CHAT', '128K', 'IMAGE2TEXT', 'TOOLS'];
        $model->setTags($complexTags);
        $this->assertEquals($complexTags, $model->getTags());

        // 测试设置为 null
        $model->setTags(null);
        $this->assertNull($model->getTags());
    }

    public function testApiTimestamps(): void
    {
        $model = new LlmModel();
        $model->setFid('test-model');
        $model->setLlmName('test-model');
        $model->setModelType('chat');
        $model->setProviderName('test-provider');

        $createDate = new \DateTimeImmutable('2024-01-01 10:00:00');
        $model->setApiCreateDate($createDate);
        $this->assertEquals($createDate, $model->getApiCreateDate());

        $createTime = new \DateTimeImmutable('2024-01-01 08:00:00');
        $model->setApiCreateTime($createTime);
        $this->assertEquals($createTime, $model->getApiCreateTime());

        $updateDate = new \DateTimeImmutable('2024-01-01 11:00:00');
        $model->setApiUpdateDate($updateDate);
        $this->assertEquals($updateDate, $model->getApiUpdateDate());

        $updateTime = new \DateTimeImmutable('2024-01-01 09:00:00');
        $model->setApiUpdateTime($updateTime);
        $this->assertEquals($updateTime, $model->getApiUpdateTime());
    }

    public function testToString(): void
    {
        $model = new LlmModel();
        $model->setFid('gpt-4-turbo');
        $modelName = 'gpt-4-turbo';
        $providerName = 'OpenAI';
        $model->setLlmName($modelName);
        $model->setProviderName($providerName);
        $model->setModelType('chat');

        // __toString 方法返回 getDisplayName() 的结果："模型名 (提供商)"
        $expectedString = sprintf('%s (%s)', $modelName, $providerName);
        $this->assertEquals($expectedString, (string) $model);
        $this->assertEquals($expectedString, $model->getDisplayName());
    }

    public function testRealWorldModels(): void
    {
        // 测试OpenAI模型
        $gpt4 = new LlmModel();
        $gpt4->setFid('gpt-4');
        $gpt4->setLlmName('gpt-4');
        $gpt4->setModelType('chat');
        $gpt4->setProviderName('OpenAI');
        $gpt4->setMaxTokens(8191);
        $gpt4->setAvailable(true);
        $gpt4->setStatus(1);
        $gpt4->setIsTools(true);
        $gpt4->setTags(['LLM', 'CHAT', '8K']);

        $this->assertEquals('gpt-4', $gpt4->getFid());
        $this->assertEquals('chat', $gpt4->getModelType());
        $this->assertTrue($gpt4->getIsTools()); // 使用 getIsTools() 而不是 isTools()
        $tags = $gpt4->getTags();
        $this->assertIsArray($tags);
        $this->assertContains('LLM', $tags);

        // 测试Claude模型
        $claude = new LlmModel();
        $claude->setFid('claude-3-opus-20240229');
        $claude->setLlmName('claude-3-opus-20240229');
        $claude->setModelType('chat');
        $claude->setProviderName('Anthropic');
        $claude->setMaxTokens(204800);
        $claude->setAvailable(true);
        $claude->setStatus(1);
        $claude->setIsTools(true);
        $claude->setTags(['LLM', 'IMAGE2TEXT', '200k']);

        $this->assertEquals('claude-3-opus-20240229', $claude->getFid());
        $this->assertEquals(204800, $claude->getMaxTokens());
        $claudeTags = $claude->getTags();
        $this->assertIsArray($claudeTags);
        $this->assertContains('IMAGE2TEXT', $claudeTags);

        // 测试嵌入模型
        $embedding = new LlmModel();
        $embedding->setFid('text-embedding-3-large');
        $embedding->setLlmName('text-embedding-3-large');
        $embedding->setModelType('embedding');
        $embedding->setProviderName('OpenAI');
        $embedding->setMaxTokens(8191);
        $embedding->setAvailable(true);
        $embedding->setStatus(1);
        $embedding->setIsTools(false);
        $embedding->setTags(['TEXT EMBEDDING', '8K']);

        $this->assertEquals('embedding', $embedding->getModelType());
        $this->assertFalse($embedding->getIsTools()); // 使用 getIsTools() 而不是 isTools()
        $embeddingTags = $embedding->getTags();
        $this->assertIsArray($embeddingTags);
        $this->assertContains('TEXT EMBEDDING', $embeddingTags);
    }

    public function testNullableFields(): void
    {
        $model = new LlmModel();

        // 测试可为空的字段（注意：fid, llmName, modelType, providerName 是必需字段）
        $this->assertNull($model->getMaxTokens());
        $this->assertNull($model->getStatus());
        $this->assertNull($model->getIsTools());
        $this->assertNull($model->getTags());
        $this->assertNull($model->getApiCreateDate());
        $this->assertNull($model->getApiCreateTime());
        $this->assertNull($model->getApiUpdateDate());
        $this->assertNull($model->getApiUpdateTime());
    }

    public function testDefaultValues(): void
    {
        $model = new LlmModel();

        // 测试默认值
        $this->assertFalse($model->getAvailable()); // available 默认为false
        $this->assertNull($model->getIsTools()); // isTools 默认为null
    }

    public function testComplexTagsStructure(): void
    {
        $model = new LlmModel();
        $model->setFid('complex-model');
        $model->setLlmName('complex-model');
        $model->setModelType('chat');
        $model->setProviderName('test-provider');

        // 测试复杂的标签结构
        $complexTags = [
            'LLM',
            'MULTIMODAL',
            'VISION',
            'CODE_GENERATION',
            'FUNCTION_CALLING',
            'JSON_MODE',
            '1M', // token capacity
            'GPT4_ARCHITECTURE',
            'SAFETY_ALIGNED',
        ];

        $model->setTags($complexTags);
        $this->assertEquals($complexTags, $model->getTags());
        $modelTags = $model->getTags();
        $this->assertIsArray($modelTags);
        $this->assertCount(9, $modelTags);
        $this->assertContains('MULTIMODAL', $modelTags);
        $this->assertContains('FUNCTION_CALLING', $modelTags);
    }

    public function testModelComparison(): void
    {
        // 创建两个相同配置的模型
        $model1 = new LlmModel();
        $model1->setFid('gpt-3.5-turbo');
        $model1->setLlmName('gpt-3.5-turbo');
        $model1->setModelType('chat');
        $model1->setProviderName('OpenAI');
        $model1->setMaxTokens(4096);

        $model2 = new LlmModel();
        $model2->setFid('gpt-3.5-turbo');
        $model2->setLlmName('gpt-3.5-turbo');
        $model2->setModelType('chat');
        $model2->setProviderName('OpenAI');
        $model2->setMaxTokens(4096);

        // 测试属性比较
        $this->assertEquals($model1->getFid(), $model2->getFid());
        $this->assertEquals($model1->getLlmName(), $model2->getLlmName());
        $this->assertEquals($model1->getProviderName(), $model2->getProviderName());
        $this->assertEquals($model1->getMaxTokens(), $model2->getMaxTokens());

        // 测试toString比较
        $this->assertEquals((string) $model1, (string) $model2);
    }

    public function testProviderSpecificModels(): void
    {
        // 测试不同提供商的模型特性

        // DeepSeek模型 - 支持推理
        $deepseek = new LlmModel();
        $deepseek->setFid('deepseek-reasoner');
        $deepseek->setLlmName('deepseek-reasoner');
        $deepseek->setModelType('chat');
        $deepseek->setProviderName('DeepSeek');
        $deepseek->setMaxTokens(64000);
        $deepseek->setAvailable(true);
        $deepseek->setIsTools(true);
        $deepseek->setTags(['LLM', 'CHAT', 'REASONING']);

        $deepseekTags = $deepseek->getTags();
        $this->assertIsArray($deepseekTags);
        $this->assertContains('REASONING', $deepseekTags);
        $this->assertTrue($deepseek->isChatModel());

        // Gemini模型 - 多模态
        $gemini = new LlmModel();
        $gemini->setFid('gemini-2.0-flash-001');
        $gemini->setLlmName('gemini-2.0-flash-001');
        $gemini->setModelType('image2text');
        $gemini->setProviderName('Google');
        $gemini->setMaxTokens(1048576);
        $gemini->setIsTools(true);
        $gemini->setTags(['LLM', 'CHAT', '1024K']);

        $this->assertEquals('image2text', $gemini->getModelType());
        $this->assertEquals(1048576, $gemini->getMaxTokens());

        // 重排序模型
        $reranker = new LlmModel();
        $reranker->setFid('jina-reranker-v2-base-multilingual');
        $reranker->setLlmName('jina-reranker-v2-base-multilingual');
        $reranker->setModelType('rerank');
        $reranker->setProviderName('Jina');
        $reranker->setMaxTokens(8196);
        $reranker->setIsTools(false);
        $reranker->setTags(['RE-RANK', '8k']);

        $this->assertEquals('rerank', $reranker->getModelType());
        $this->assertFalse($reranker->getIsTools()); // 使用 getIsTools() 而不是 isTools()
        $rerankerTags = $reranker->getTags();
        $this->assertIsArray($rerankerTags);
        $this->assertContains('RE-RANK', $rerankerTags);
        $this->assertTrue($reranker->isRerankModel());
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'fid' => ['fid', 'gpt-3.5-turbo'];
        yield 'llmName' => ['llmName', 'GPT-3.5 Turbo'];
        yield 'modelType' => ['modelType', 'chat'];
        yield 'providerName' => ['providerName', 'OpenAI'];
        yield 'maxTokens' => ['maxTokens', 4096];
        yield 'available' => ['available', true];
        yield 'status' => ['status', 1];
        yield 'isTools' => ['isTools', true];
    }
}
