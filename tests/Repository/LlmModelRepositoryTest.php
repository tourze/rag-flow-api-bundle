<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\RAGFlowApiBundle\Entity\LlmModel;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Repository\LlmModelRepository;

/**
 * @internal
 */
#[CoversClass(LlmModelRepository::class)]
#[RunTestsInSeparateProcesses]
#[AsRepository(entityClass: LlmModel::class)]
class LlmModelRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // Repository tests don't need additional setup
    }

    protected function getRepository(): LlmModelRepository
    {
        /** @var LlmModelRepository */
        return self::getService(LlmModelRepository::class);
    }

    protected function createNewEntity(): object
    {
        $ragFlowInstance = $this->createRAGFlowInstance('测试实例');

        $llmModel = new LlmModel();
        $llmModel->setFid('test-model-' . uniqid());
        $llmModel->setLlmName('Test Model');
        $llmModel->setProviderName('TestProvider');
        $llmModel->setModelType('chat');
        $llmModel->setAvailable(true);
        $llmModel->setRagFlowInstance($ragFlowInstance);

        return $llmModel;
    }

    public function testRepositoryCreation(): void
    {
        $this->assertInstanceOf(LlmModelRepository::class, $this->getRepository());
    }

    public function testFindByFid(): void
    {
        // 创建测试实例
        $ragFlowInstance = $this->createRAGFlowInstance('LLM模型FID查找测试实例');

        // 创建测试LLM模型
        $llmModel = new LlmModel();
        $llmModel->setFid('model-fid-123');
        $llmModel->setLlmName('测试模型');
        $llmModel->setProviderName('TestProvider');
        $llmModel->setModelType('chat');
        $llmModel->setAvailable(true);
        $llmModel->setRagFlowInstance($ragFlowInstance);
        $persistedModel = $this->persistAndFlush($llmModel);
        $this->assertInstanceOf(LlmModel::class, $persistedModel);

        // 测试通过FID和实例查找
        $foundModel = $this->getRepository()->findByFid('model-fid-123', $ragFlowInstance);
        $this->assertNotNull($foundModel);
        $this->assertEquals($persistedModel->getId(), $foundModel->getId());
        $this->assertEquals('model-fid-123', $foundModel->getFid());
        $foundRagFlowInstance = $foundModel->getRagFlowInstance();
        $this->assertNotNull($foundRagFlowInstance);
        $this->assertEquals($ragFlowInstance->getId(), $foundRagFlowInstance->getId());

        // 测试查找不存在的FID
        $notFound = $this->getRepository()->findByFid('non-existent-fid', $ragFlowInstance);
        $this->assertNull($notFound);

        // 创建另一个实例
        $anotherInstance = $this->createRAGFlowInstance('另一个测试实例');

        // 在另一个实例中创建相同FID的模型
        $anotherModel = new LlmModel();
        $anotherModel->setFid('model-fid-123');
        $anotherModel->setLlmName('另一个实例的模型');
        $anotherModel->setProviderName('AnotherProvider');
        $anotherModel->setModelType('chat');
        $anotherModel->setAvailable(true);
        $anotherModel->setRagFlowInstance($anotherInstance);
        /** @var LlmModel $anotherPersistedModel */
        $anotherPersistedModel = $this->persistAndFlush($anotherModel);

        // 测试跨实例查找
        $foundInOriginalInstance = $this->getRepository()->findByFid('model-fid-123', $ragFlowInstance);
        $this->assertNotNull($foundInOriginalInstance);
        $this->assertEquals($persistedModel->getId(), $foundInOriginalInstance->getId());

        $foundInAnotherInstance = $this->getRepository()->findByFid('model-fid-123', $anotherInstance);
        $this->assertNotNull($foundInAnotherInstance);
        $this->assertEquals($anotherPersistedModel->getId(), $foundInAnotherInstance->getId());
    }

    public function testFindAvailableChatModels(): void
    {
        // 创建测试实例
        $ragFlowInstance = $this->createRAGFlowInstance('可用聊天模型测试实例');

        // 创建不同类型的模型
        $models = [
            [
                'fid' => 'chat-model-1',
                'name' => 'ChatGPT-4',
                'provider' => 'OpenAI',
                'type' => 'chat',
                'available' => true,
            ],
            [
                'fid' => 'chat-model-2',
                'name' => 'Claude-3',
                'provider' => 'Anthropic',
                'type' => 'chat',
                'available' => true,
            ],
            [
                'fid' => 'embedding-model-1',
                'name' => 'Text-Embedding',
                'provider' => 'OpenAI',
                'type' => 'embedding',
                'available' => true,
            ],
            [
                'fid' => 'chat-model-3',
                'name' => 'Gemini-Pro',
                'provider' => 'Google',
                'type' => 'chat',
                'available' => false, // 不可用
            ],
            [
                'fid' => 'rerank-model-1',
                'name' => 'Rerank-Model',
                'provider' => 'Cohere',
                'type' => 'rerank',
                'available' => true,
            ],
        ];

        foreach ($models as $modelData) {
            $model = new LlmModel();
            $model->setFid($modelData['fid']);
            $model->setLlmName($modelData['name']);
            $model->setProviderName($modelData['provider']);
            $model->setModelType($modelData['type']);
            $model->setAvailable($modelData['available']);
            $model->setRagFlowInstance($ragFlowInstance);
            $this->persistAndFlush($model);
        }

        // 测试查找可用的聊天模型
        $availableChatModels = $this->getRepository()->findAvailableChatModels($ragFlowInstance);
        $this->assertCount(2, $availableChatModels);

        // 验证返回的都是可用的聊天模型
        foreach ($availableChatModels as $model) {
            $this->assertTrue($model->getAvailable());
            $this->assertEquals('chat', $model->getModelType());
        }

        // 验证按提供商名称和模型名称排序
        $modelNames = array_map(static fn (LlmModel $model): string => $model->getLlmName(), $availableChatModels);
        $providerNames = array_map(static fn (LlmModel $model): string => $model->getProviderName(), $availableChatModels);

        $this->assertEquals(['Anthropic', 'OpenAI'], $providerNames);
        $this->assertEquals(['Claude-3', 'ChatGPT-4'], $modelNames);
    }

    public function testFindAvailableModelsByProvider(): void
    {
        // 创建测试实例
        $ragFlowInstance = $this->createRAGFlowInstance('按提供商分组测试实例');

        // 创建不同提供商的模型
        $models = [
            [
                'fid' => 'openai-chat-1',
                'name' => 'GPT-4',
                'provider' => 'OpenAI',
                'type' => 'chat',
                'available' => true,
            ],
            [
                'fid' => 'openai-embedding-1',
                'name' => 'Text-Embedding',
                'provider' => 'OpenAI',
                'type' => 'embedding',
                'available' => true,
            ],
            [
                'fid' => 'anthropic-chat-1',
                'name' => 'Claude-3',
                'provider' => 'Anthropic',
                'type' => 'chat',
                'available' => true,
            ],
            [
                'fid' => 'google-chat-1',
                'name' => 'Gemini-Pro',
                'provider' => 'Google',
                'type' => 'chat',
                'available' => true,
            ],
            [
                'fid' => 'unavailable-model-1',
                'name' => 'Unavialable-Model',
                'provider' => 'OpenAI',
                'type' => 'chat',
                'available' => false, // 不可用，不应出现在结果中
            ],
        ];

        foreach ($models as $modelData) {
            $model = new LlmModel();
            $model->setFid($modelData['fid']);
            $model->setLlmName($modelData['name']);
            $model->setProviderName($modelData['provider']);
            $model->setModelType($modelData['type']);
            $model->setAvailable($modelData['available']);
            $model->setRagFlowInstance($ragFlowInstance);
            $this->persistAndFlush($model);
        }

        // 测试按提供商分组查找可用模型
        $groupedModels = $this->getRepository()->findAvailableModelsByProvider($ragFlowInstance);

        // 验证分组结构
        $this->assertIsArray($groupedModels);
        $this->assertArrayHasKey('OpenAI', $groupedModels);
        $this->assertArrayHasKey('Anthropic', $groupedModels);
        $this->assertArrayHasKey('Google', $groupedModels);
        $this->assertArrayNotHasKey('Unavialable-Model', $groupedModels);

        // 验证每个提供商的模型数量
        $this->assertCount(2, $groupedModels['OpenAI']); // GPT-4 和 Text-Embedding
        $this->assertCount(1, $groupedModels['Anthropic']); // Claude-3
        $this->assertCount(1, $groupedModels['Google']); // Gemini-Pro

        // 验证模型按名称排序
        foreach ($groupedModels as $provider => $providerModels) {
            $modelNames = array_map(static fn (LlmModel $model): string => $model->getLlmName(), $providerModels);
            $sortedNames = $modelNames;
            sort($sortedNames);
            $this->assertEquals($sortedNames, $modelNames, "提供商 {$provider} 的模型应该按名称排序");
        }
    }

    public function testFindAvailableModelsByType(): void
    {
        // 创建测试实例
        $ragFlowInstance = $this->createRAGFlowInstance('按类型查找测试实例');

        // 创建不同类型的模型
        $models = [
            [
                'fid' => 'chat-1',
                'name' => 'ChatGPT-4',
                'provider' => 'OpenAI',
                'type' => 'chat',
                'available' => true,
            ],
            [
                'fid' => 'chat-2',
                'name' => 'Claude-3',
                'provider' => 'Anthropic',
                'type' => 'chat',
                'available' => true,
            ],
            [
                'fid' => 'embedding-1',
                'name' => 'Text-Embedding',
                'provider' => 'OpenAI',
                'type' => 'embedding',
                'available' => true,
            ],
            [
                'fid' => 'embedding-2',
                'name' => 'Sentence-Embedding',
                'provider' => 'HuggingFace',
                'type' => 'embedding',
                'available' => true,
            ],
            [
                'fid' => 'rerank-1',
                'name' => 'Rerank-Model',
                'provider' => 'Cohere',
                'type' => 'rerank',
                'available' => true,
            ],
            [
                'fid' => 'unavailable-chat',
                'name' => 'Unavailable-Chat',
                'provider' => 'OpenAI',
                'type' => 'chat',
                'available' => false,
            ],
        ];

        foreach ($models as $modelData) {
            $model = new LlmModel();
            $model->setFid($modelData['fid']);
            $model->setLlmName($modelData['name']);
            $model->setProviderName($modelData['provider']);
            $model->setModelType($modelData['type']);
            $model->setAvailable($modelData['available']);
            $model->setRagFlowInstance($ragFlowInstance);
            $this->persistAndFlush($model);
        }

        // 测试查找聊天模型
        $chatModels = $this->getRepository()->findAvailableModelsByType('chat', $ragFlowInstance);
        $this->assertCount(2, $chatModels);

        foreach ($chatModels as $model) {
            $this->assertTrue($model->getAvailable());
            $this->assertEquals('chat', $model->getModelType());
        }

        // 测试查找嵌入模型
        $embeddingModels = $this->getRepository()->findAvailableModelsByType('embedding', $ragFlowInstance);
        $this->assertCount(2, $embeddingModels);

        foreach ($embeddingModels as $model) {
            $this->assertTrue($model->getAvailable());
            $this->assertEquals('embedding', $model->getModelType());
        }

        // 测试查找重排序模型
        $rerankModels = $this->getRepository()->findAvailableModelsByType('rerank', $ragFlowInstance);
        $this->assertCount(1, $rerankModels);

        foreach ($rerankModels as $model) {
            $this->assertTrue($model->getAvailable());
            $this->assertEquals('rerank', $model->getModelType());
        }

        // 测试查找不存在的类型
        $imageModels = $this->getRepository()->findAvailableModelsByType('image', $ragFlowInstance);
        $this->assertEmpty($imageModels);
    }

    public function testFindAvailableModelsByProviderName(): void
    {
        // 创建测试实例
        $ragFlowInstance = $this->createRAGFlowInstance('按提供商名称查找测试实例');

        // 创建不同提供商的模型
        $models = [
            [
                'fid' => 'openai-chat-1',
                'name' => 'GPT-4',
                'provider' => 'OpenAI',
                'type' => 'chat',
                'available' => true,
            ],
            [
                'fid' => 'openai-embedding-1',
                'name' => 'Text-Embedding',
                'provider' => 'OpenAI',
                'type' => 'embedding',
                'available' => true,
            ],
            [
                'fid' => 'anthropic-chat-1',
                'name' => 'Claude-3',
                'provider' => 'Anthropic',
                'type' => 'chat',
                'available' => true,
            ],
            [
                'fid' => 'openai-unavailable',
                'name' => 'Unavailable-Model',
                'provider' => 'OpenAI',
                'type' => 'chat',
                'available' => false,
            ],
        ];

        foreach ($models as $modelData) {
            $model = new LlmModel();
            $model->setFid($modelData['fid']);
            $model->setLlmName($modelData['name']);
            $model->setProviderName($modelData['provider']);
            $model->setModelType($modelData['type']);
            $model->setAvailable($modelData['available']);
            $model->setRagFlowInstance($ragFlowInstance);
            $this->persistAndFlush($model);
        }

        // 测试查找OpenAI的可用模型
        $openaiModels = $this->getRepository()->findAvailableModelsByProviderName('OpenAI', $ragFlowInstance);
        $this->assertCount(2, $openaiModels); // 只有可用的模型

        foreach ($openaiModels as $model) {
            $this->assertTrue($model->getAvailable());
            $this->assertEquals('OpenAI', $model->getProviderName());
        }

        // 验证按模型名称排序
        $modelNames = array_map(static fn (LlmModel $model): string => $model->getLlmName(), $openaiModels);
        $this->assertEquals(['GPT-4', 'Text-Embedding'], $modelNames);

        // 测试查找Anthropic的可用模型
        $anthropicModels = $this->getRepository()->findAvailableModelsByProviderName('Anthropic', $ragFlowInstance);
        $this->assertCount(1, $anthropicModels);

        foreach ($anthropicModels as $model) {
            $this->assertTrue($model->getAvailable());
            $this->assertEquals('Anthropic', $model->getProviderName());
        }

        // 测试查找不存在的提供商
        $nonExistentModels = $this->getRepository()->findAvailableModelsByProviderName('NonExistentProvider', $ragFlowInstance);
        $this->assertEmpty($nonExistentModels);
    }

    public function testGetChoicesForEasyAdmin(): void
    {
        // 创建测试实例
        $ragFlowInstance = $this->createRAGFlowInstance('EasyAdmin选项测试实例');

        // 创建测试模型
        $models = [
            [
                'fid' => 'model-1',
                'name' => 'GPT-4',
                'provider' => 'OpenAI',
                'type' => 'chat',
                'available' => true,
            ],
            [
                'fid' => 'model-2',
                'name' => 'Claude-3',
                'provider' => 'Anthropic',
                'type' => 'chat',
                'available' => true,
            ],
            [
                'fid' => 'model-3',
                'name' => 'Text-Embedding',
                'provider' => 'OpenAI',
                'type' => 'embedding',
                'available' => true,
            ],
            [
                'fid' => 'model-4',
                'name' => 'Unavailable-Model',
                'provider' => 'OpenAI',
                'type' => 'chat',
                'available' => false,
            ],
        ];

        foreach ($models as $modelData) {
            $model = new LlmModel();
            $model->setFid($modelData['fid']);
            $model->setLlmName($modelData['name']);
            $model->setProviderName($modelData['provider']);
            $model->setModelType($modelData['type']);
            $model->setAvailable($modelData['available']);
            $model->setRagFlowInstance($ragFlowInstance);
            $this->persistAndFlush($model);
        }

        // 测试获取所有可用模型的选项
        $allChoices = $this->getRepository()->getChoicesForEasyAdmin($ragFlowInstance);
        $this->assertIsArray($allChoices);
        $this->assertCount(3, $allChoices); // 只包含可用的模型

        // 验证选项格式: key是显示名称，value是模型名称
        $expectedChoices = [
            'Claude-3 (Anthropic)' => 'Claude-3',
            'GPT-4 (OpenAI)' => 'GPT-4',
            'Text-Embedding (OpenAI)' => 'Text-Embedding',
        ];

        foreach ($expectedChoices as $expectedKey => $expectedValue) {
            $this->assertArrayHasKey($expectedKey, $allChoices);
            $this->assertEquals($expectedValue, $allChoices[$expectedKey]);
        }

        // 测试按模型类型筛选
        $chatChoices = $this->getRepository()->getChoicesForEasyAdmin($ragFlowInstance, 'chat');
        $this->assertCount(2, $chatChoices); // 只有聊天模型

        $expectedChatChoices = [
            'Claude-3 (Anthropic)' => 'Claude-3',
            'GPT-4 (OpenAI)' => 'GPT-4',
        ];

        foreach ($expectedChatChoices as $expectedKey => $expectedValue) {
            $this->assertArrayHasKey($expectedKey, $chatChoices);
            $this->assertEquals($expectedValue, $chatChoices[$expectedKey]);
        }

        // 测试按嵌入类型筛选
        $embeddingChoices = $this->getRepository()->getChoicesForEasyAdmin($ragFlowInstance, 'embedding');
        $this->assertCount(1, $embeddingChoices);

        $expectedEmbeddingChoices = [
            'Text-Embedding (OpenAI)' => 'Text-Embedding',
        ];

        foreach ($expectedEmbeddingChoices as $expectedKey => $expectedValue) {
            $this->assertArrayHasKey($expectedKey, $embeddingChoices);
            $this->assertEquals($expectedValue, $embeddingChoices[$expectedKey]);
        }
    }

    public function testDeleteByInstance(): void
    {
        // 创建两个测试实例
        $instance1 = $this->createRAGFlowInstance('删除测试实例1');
        $instance2 = $this->createRAGFlowInstance('删除测试实例2');

        // 为第一个实例创建模型
        $models1 = [
            [
                'fid' => 'instance1-model-1',
                'name' => 'Instance1-Model-1',
                'provider' => 'Provider1',
                'type' => 'chat',
                'available' => true,
            ],
            [
                'fid' => 'instance1-model-2',
                'name' => 'Instance1-Model-2',
                'provider' => 'Provider1',
                'type' => 'embedding',
                'available' => true,
            ],
        ];

        foreach ($models1 as $modelData) {
            $model = new LlmModel();
            $model->setFid($modelData['fid']);
            $model->setLlmName($modelData['name']);
            $model->setProviderName($modelData['provider']);
            $model->setModelType($modelData['type']);
            $model->setAvailable($modelData['available']);
            $model->setRagFlowInstance($instance1);
            $this->persistAndFlush($model);
        }

        // 为第二个实例创建模型
        $model2 = new LlmModel();
        $model2->setFid('instance2-model-1');
        $model2->setLlmName('Instance2-Model-1');
        $model2->setProviderName('Provider2');
        $model2->setModelType('chat');
        $model2->setAvailable(true);
        $model2->setRagFlowInstance($instance2);
        $this->persistAndFlush($model2);

        // 验证创建的模型数量
        $totalModelsBefore = count(self::getEntityManager()->getRepository(LlmModel::class)->findAll());
        $this->assertGreaterThanOrEqual(3, $totalModelsBefore);

        // 测试删除第一个实例的所有模型
        $deletedCount = $this->getRepository()->deleteByInstance($instance1);
        $this->assertEquals(2, $deletedCount);

        // 验证第一个实例的模型已被删除
        $instance1ModelsAfter = self::getEntityManager()->getRepository(LlmModel::class)->findBy(['ragFlowInstance' => $instance1]);
        $this->assertEmpty($instance1ModelsAfter);

        // 验证第二个实例的模型仍然存在
        $instance2ModelsAfter = self::getEntityManager()->getRepository(LlmModel::class)->findBy(['ragFlowInstance' => $instance2]);
        $this->assertCount(1, $instance2ModelsAfter);
        $this->assertEquals('Instance2-Model-1', $instance2ModelsAfter[0]->getLlmName());

        // 验证总模型数量减少
        $totalModelsAfter = count(self::getEntityManager()->getRepository(LlmModel::class)->findAll());
        $this->assertEquals($totalModelsBefore - 2, $totalModelsAfter);
    }

    /**
     * 创建测试RAGFlow实例
     */
    private function createRAGFlowInstance(string $name): RAGFlowInstance
    {
        $ragFlowInstance = new RAGFlowInstance();
        $ragFlowInstance->setName($name);
        $ragFlowInstance->setApiUrl('https://example.com/api');
        $ragFlowInstance->setApiKey('test-api-key');
        $persistedInstance = $this->persistAndFlush($ragFlowInstance);

        $this->assertInstanceOf(RAGFlowInstance::class, $persistedInstance);
        /** @var RAGFlowInstance $persistedInstance */

        return $persistedInstance;
    }
}
