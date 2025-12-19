<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Entity\ChatAssistant;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Service\ChatAssistantService;
use Tourze\RAGFlowApiBundle\Service\LocalDataSyncService;
use Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManagerInterface;

/**
 * @internal
 */
#[CoversClass(ChatAssistantService::class)]
#[RunTestsInSeparateProcesses]
class ChatAssistantServiceTest extends AbstractIntegrationTestCase
{
    private ChatAssistantService $chatAssistantService;

    private RAGFlowInstanceManagerInterface $instanceManager;

    private LocalDataSyncService $localDataSyncService;

    protected function onSetUp(): void
    {
        // 获取服务
        $this->instanceManager = self::getService(RAGFlowInstanceManagerInterface::class);
        $this->localDataSyncService = self::getService(LocalDataSyncService::class);
        $this->chatAssistantService = self::getService(ChatAssistantService::class);

        // 创建测试用的RAGFlow实例
        $this->createTestInstance();
    }

    private function createTestInstance(): void
    {
        try {
            $config = [
                'name' => 'default',
                'api_url' => 'https://test-ragflow.example.com/api',
                'api_key' => 'test-api-key-123',
                'enabled' => true,
                'timeout' => 30,
            ];

            $this->instanceManager->createInstance($config);
        } catch (\Exception $e) {
            // 如果实例已存在则跳过
        }
    }

    public function testServiceCreation(): void
    {
        $this->assertInstanceOf(ChatAssistantService::class, $this->chatAssistantService);
    }

    public function testListWithDefaultParameters(): void
    {
        // 测试默认参数调用
        try {
            $result = $this->chatAssistantService->list();
            // 如果没有抛出异常，验证返回结构
            // 验证结果包含预期的键而不是类型
            $this->assertArrayHasKey('data', $result);
        } catch (\Exception $e) {
            // 在测试环境中，API调用失败是预期的
            $this->assertInstanceOf(\RuntimeException::class, $e);
            $this->assertStringContainsString('Failed to execute API request', $e->getMessage());
        }
    }

    public function testListWithCustomParameters(): void
    {
        $filters = [
            'name' => 'test-assistant',
            'orderby' => 'create_time',
            'desc' => false,
        ];

        $this->expectException(\Exception::class); // 预期会有异常，因为测试环境没有真实的RAGFlow API

        $result = $this->chatAssistantService->list(1, 10, $filters);
    }

    public function testGetAvailableLlmModels(): void
    {
        // 测试环境下应该返回可用模型，不应该抛出异常
        $result = $this->chatAssistantService->getAvailableLlmModels();

        // 验证返回的是数组
        $this->assertIsArray($result);

        // 验证至少有一些模型
        $this->assertNotEmpty($result);

        // 验证返回格式正确（key为显示名称，value为模型名称）
        foreach ($result as $displayName => $modelName) {
            $this->assertIsString($displayName);
            $this->assertIsString($modelName);
        }

        // 验证至少有一个deepseek模型（测试环境中的实际可用模型）
        $this->assertContains('deepseek-chat', $result);
    }

    public function testCreateChatAssistant(): void
    {
        // 获取默认RAGFlow实例
        $instance = $this->instanceManager->getDefaultInstance();

        $dataset = new Dataset();
        $dataset->setName('测试数据集');
        $dataset->setDescription('用于测试的数据集');
        $dataset->setRemoteId('test-dataset-123');
        $dataset->setRagFlowInstance($instance);

        /** @var Dataset $persistedDataset */
        $persistedDataset = $this->persistAndFlush($dataset);

        $remoteId = $persistedDataset->getRemoteId();
        $this->assertNotNull($remoteId);

        $assistant = new ChatAssistant();
        $assistant->setName('测试聊天助手');
        $assistant->setAvatar('https://example.com/avatar.png');
        $assistant->setDatasetIds([$remoteId]);
        $assistant->setLlmModel('gpt-3.5-turbo');
        $assistant->setTemperature(0.7);
        $assistant->setTopP(0.95);
        $assistant->setPresencePenalty(0.0);
        $assistant->setFrequencyPenalty(0.0);
        $assistant->setMaxTokens(2048);
        $assistant->setSimilarityThreshold(0.2);
        $assistant->setKeywordsSimilarityWeight(0.7);
        $assistant->setTopN(6);
        $assistant->setVariables([]);
        $assistant->setRerankModel('');
        $assistant->setEmptyResponse('抱歉，我无法找到相关信息来回答您的问题。');
        $assistant->setOpener('您好！我是您的AI助手，有什么可以帮助您的吗？');
        $assistant->setShowQuote(true);
        $assistant->setSystemPrompt('您是一个专业的AI助手。请根据提供的知识库内容来回答用户的问题。');

        $this->expectException(\Exception::class); // 预期会有异常，因为测试环境没有真实的RAGFlow API

        $result = $this->chatAssistantService->create($assistant);
    }

    public function testUpdateChatAssistant(): void
    {
        $assistantId = 'test-assistant-123';
        $updateData = [
            'name' => '更新后的助手名称',
            'llm' => [
                'model_name' => 'gpt-4',
                'temperature' => 0.5,
            ],
        ];

        $this->expectException(\Exception::class); // 预期会有异常，因为测试环境没有真实的RAGFlow API

        $result = $this->chatAssistantService->update($assistantId, $updateData);
    }

    public function testDeleteChatAssistant(): void
    {
        $assistantId = 'test-assistant-123';

        $this->expectException(\Exception::class); // 预期会有异常，因为测试环境没有真实的RAGFlow API

        $result = $this->chatAssistantService->delete($assistantId);
    }

    public function testFilterParameterExtraction(): void
    {
        // 测试空过滤器 - 应该抛出异常（因为测试环境没有真实的RAGFlow API）
        try {
            $result = $this->chatAssistantService->list(1, 10, null);
            self::fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Exception::class, $e);
        }

        // 测试有效过滤器 - 应该抛出异常（因为测试环境没有真实的RAGFlow API）
        $filters = [
            'name' => 'test',
            'orderby' => 'create_time',
            'desc' => true,
            'id' => 'assistant-123',
        ];

        try {
            $result = $this->chatAssistantService->list(1, 10, $filters);
            self::fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Exception::class, $e);
        }

        // 测试无效类型的过滤器 - 应该抛出异常（因为测试环境没有真实的RAGFlow API）
        $invalidFilters = [
            'name' => 123, // 应该是字符串
            'desc' => 'true', // 应该是布尔值
            'orderby' => ['invalid'], // 应该是字符串
        ];

        try {
            $result = $this->chatAssistantService->list(1, 10, $invalidFilters);
            self::fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }

    public function testServiceDependencies(): void
    {
        // 测试服务依赖注入是否正确
        $this->assertInstanceOf(RAGFlowInstanceManagerInterface::class, $this->instanceManager);
        $this->assertInstanceOf(LocalDataSyncService::class, $this->localDataSyncService);
    }

    public function testPaginationParameters(): void
    {
        // 在测试环境中，这些调用会因为没有真实API而抛出异常
        // 第一页，默认大小
        try {
            $this->chatAssistantService->list(1);
            self::fail('Expected exception was not thrown for page 1');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Exception::class, $e);
        }

        // 第二页，自定义大小
        try {
            $this->chatAssistantService->list(2, 50);
            self::fail('Expected exception was not thrown for page 2');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Exception::class, $e);
        }

        // 边界情况
        try {
            $this->chatAssistantService->list(1, 1);
            self::fail('Expected exception was not thrown for page 1, size 1');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Exception::class, $e);
        }

        try {
            $this->chatAssistantService->list(100, 100);
            self::fail('Expected exception was not thrown for page 100, size 100');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }

    public function testCreateWithMinimalData(): void
    {
        $assistant = new ChatAssistant();
        $assistant->setName('简单助手');
        $assistant->setDatasetIds(['kb-123']);

        $this->expectException(\Exception::class);
        $this->chatAssistantService->create($assistant);
    }

    public function testCreateWithCompleteData(): void
    {
        $assistant = new ChatAssistant();
        $assistant->setName('完整配置助手');
        $assistant->setAvatar('https://example.com/avatar.png');
        $assistant->setDatasetIds(['kb-123', 'kb-456']);
        $assistant->setLlmModel('gpt-4-turbo');
        $assistant->setTemperature(0.8);
        $assistant->setTopP(0.9);
        $assistant->setPresencePenalty(0.1);
        $assistant->setFrequencyPenalty(0.1);
        $assistant->setMaxTokens(4096);
        $assistant->setSimilarityThreshold(0.3);
        $assistant->setKeywordsSimilarityWeight(0.8);
        $assistant->setTopN(10);
        $assistant->setVariables([
            'user_name' => ['optional' => false],
            'context' => ['optional' => true],
        ]);
        $assistant->setRerankModel('bge-reranker-base');
        $assistant->setEmptyResponse('很抱歉，我找不到相关信息。');
        $assistant->setOpener('欢迎使用智能助手！');
        $assistant->setShowQuote(true);
        $assistant->setSystemPrompt('你是一个专业的技术顾问。请基于知识库内容提供准确的技术建议。');

        $this->expectException(\Exception::class);
        $this->chatAssistantService->create($assistant);
    }

    public function testUpdatePartialData(): void
    {
        $assistantId = 'test-assistant-456';
        $partialData = [
            'name' => '部分更新的助手',
        ];

        $this->expectException(\Exception::class);
        $this->chatAssistantService->update($assistantId, $partialData);
    }

    public function testBatchOperations(): void
    {
        // 测试批量操作的行为
        $assistantIds = ['assistant-1', 'assistant-2', 'assistant-3'];

        foreach ($assistantIds as $id) {
            $this->expectException(\Exception::class);
            $this->chatAssistantService->delete($id);
        }
    }

    public function testConvertToApiDataWithMinimalEntity(): void
    {
        // 测试转换最小配置的ChatAssistant实体
        $assistant = new ChatAssistant();
        $assistant->setName('最小助手');
        $assistant->setDatasetIds(['kb-123']);

        $result = $this->chatAssistantService->convertToApiData($assistant);

        // 验证基础数据结构
        $this->assertIsArray($result);
        $this->assertEquals('最小助手', $result['name']);
        $this->assertEquals(['kb-123'], $result['dataset_ids']);

        // 验证可选字段不包含在内
        $this->assertArrayNotHasKey('description', $result);
        $this->assertArrayNotHasKey('avatar', $result);
        $this->assertArrayNotHasKey('language', $result);

        // 验证嵌套结构 - 当没有LLM配置时，llm字段不会被包含
        $this->assertArrayNotHasKey('llm', $result);
        $this->assertArrayHasKey('prompt', $result);
    }

    public function testConvertToApiDataWithCompleteEntity(): void
    {
        // 测试转换完整配置的ChatAssistant实体
        $assistant = new ChatAssistant();
        $assistant->setName('完整配置助手');
        $assistant->setDescription('这是一个完整配置的助手');
        $assistant->setAvatar('https://example.com/avatar.png');
        $assistant->setLanguage('zh-CN');
        $assistant->setDatasetIds(['kb-123', 'kb-456']);
        $assistant->setLlmModel('gpt-4');
        $assistant->setTemperature(0.7);
        $assistant->setTopP(0.9);
        $assistant->setPresencePenalty(0.1);
        $assistant->setFrequencyPenalty(0.2);
        $assistant->setSimilarityThreshold(0.3);
        $assistant->setKeywordsSimilarityWeight(0.8);
        $assistant->setTopN(5);
        $assistant->setTopK(10);
        $assistant->setVariables(['user_name' => ['optional' => false]]);
        $assistant->setRerankModel('bge-reranker-base');
        $assistant->setEmptyResponse('抱歉，找不到相关信息。');
        $assistant->setOpener('您好！我是您的AI助手。');
        $assistant->setShowQuote(true);
        $assistant->setSystemPrompt('您是一个专业的AI助手。');

        $result = $this->chatAssistantService->convertToApiData($assistant);

        // 验证基础和可选字段
        $this->assertEquals('完整配置助手', $result['name']);
        $this->assertEquals('这是一个完整配置的助手', $result['description']);
        $this->assertEquals('https://example.com/avatar.png', $result['avatar']);
        $this->assertEquals('zh-CN', $result['language']);
        $this->assertEquals(['kb-123', 'kb-456'], $result['dataset_ids']);

        // 验证LLM配置
        $this->assertArrayHasKey('llm', $result);
        $llmConfig = $result['llm'];
        self::assertIsArray($llmConfig);
        $this->assertEquals('gpt-4', $llmConfig['model_name']);
        $this->assertEquals(0.7, $llmConfig['temperature']);
        $this->assertEquals(0.9, $llmConfig['top_p']);
        $this->assertEquals(0.1, $llmConfig['presence_penalty']);
        $this->assertEquals(0.2, $llmConfig['frequency_penalty']);

        // 验证Prompt配置
        $this->assertArrayHasKey('prompt', $result);
        $promptConfig = $result['prompt'];
        self::assertIsArray($promptConfig);
        $this->assertEquals(0.3, $promptConfig['similarity_threshold']);
        $this->assertEquals(0.8, $promptConfig['keywords_similarity_weight']);
        $this->assertEquals(5, $promptConfig['top_n']);
        $this->assertEquals(10, $promptConfig['top_k']);
        $this->assertEquals(['user_name' => ['optional' => false]], $promptConfig['variables']);
        $this->assertEquals('bge-reranker-base', $promptConfig['rerank_model']);
        $this->assertEquals('抱歉，找不到相关信息。', $promptConfig['empty_response']);
        $this->assertEquals('您好！我是您的AI助手。', $promptConfig['opener']);
        $this->assertTrue($promptConfig['show_quote']);
        $this->assertEquals('您是一个专业的AI助手。', $promptConfig['prompt']);
    }

    public function testConvertToApiDataWithNullLlmConfig(): void
    {
        // 测试当LLM配置为空时，llm字段不包含在结果中
        $assistant = new ChatAssistant();
        $assistant->setName('无LLM配置助手');
        $assistant->setDatasetIds(['kb-123']);
        // 不设置任何LLM相关字段

        $result = $this->chatAssistantService->convertToApiData($assistant);

        // llm字段应该存在但为空数组，会被buildLlmConfig方法返回null
        // 然后在addLlmConfig中，null值不会被添加到data中
        $this->assertArrayNotHasKey('llm', $result);
    }

    public function testConvertToApiDataWithPartialLlmConfig(): void
    {
        // 测试部分LLM配置
        $assistant = new ChatAssistant();
        $assistant->setName('部分LLM配置助手');
        $assistant->setDatasetIds(['kb-123']);
        $assistant->setLlmModel('gpt-3.5-turbo');
        $assistant->setTemperature(0.5);
        // 其他LLM字段保持null

        $result = $this->chatAssistantService->convertToApiData($assistant);

        $this->assertArrayHasKey('llm', $result);
        $llmConfig = $result['llm'];
        self::assertIsArray($llmConfig);
        $this->assertEquals('gpt-3.5-turbo', $llmConfig['model_name']);
        $this->assertEquals(0.5, $llmConfig['temperature']);
        $this->assertArrayNotHasKey('top_p', $llmConfig);
        $this->assertArrayNotHasKey('presence_penalty', $llmConfig);
        $this->assertArrayNotHasKey('frequency_penalty', $llmConfig);
    }

    public function testConvertToApiDataWithNullPromptFields(): void
    {
        // 测试部分Prompt配置为null的情况
        $assistant = new ChatAssistant();
        $assistant->setName('部分Prompt配置助手');
        $assistant->setDatasetIds(['kb-123']);
        $assistant->setSimilarityThreshold(0.2);
        $assistant->setShowQuote(false);
        // 其他Prompt字段保持null

        $result = $this->chatAssistantService->convertToApiData($assistant);

        $this->assertArrayHasKey('prompt', $result);
        $promptConfig = $result['prompt'];
        self::assertIsArray($promptConfig);

        // 设置的字段应该包含
        $this->assertEquals(0.2, $promptConfig['similarity_threshold']);
        $this->assertFalse($promptConfig['show_quote']);

        // 根据实现，未设置的字段可能不会被包含在结果中
        $this->assertArrayNotHasKey('keywords_similarity_weight', $promptConfig);
        $this->assertArrayNotHasKey('top_n', $promptConfig);
    }

    public function testConvertToApiDataTypeConsistency(): void
    {
        // 测试数据类型一致性 - 确保有LLM配置
        $assistant = new ChatAssistant();
        $assistant->setName('类型测试助手');
        $assistant->setDatasetIds(['kb-123']);
        $assistant->setLlmModel('gpt-4'); // 添加LLM配置
        $assistant->setTemperature(0.7);
        $assistant->setTopN(10);
        $assistant->setShowQuote(true);
        $assistant->setVariables(['test' => 'value']);

        $result = $this->chatAssistantService->convertToApiData($assistant);

        // 验证数据类型
        $this->assertIsString($result['name']);
        $this->assertIsArray($result['dataset_ids']);
        $this->assertIsArray($result['llm']);
        $this->assertIsArray($result['prompt']);
        $this->assertIsFloat($result['llm']['temperature']);
        $this->assertIsInt($result['prompt']['top_n']);
        $this->assertIsBool($result['prompt']['show_quote']);
        $this->assertIsArray($result['prompt']['variables']);
    }
}
