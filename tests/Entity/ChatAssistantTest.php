<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Entity;

use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\PHPUnitSymfonyKernelTest\DoctrineTrait;
use Tourze\PHPUnitSymfonyKernelTest\ServiceLocatorTrait;
use Tourze\RAGFlowApiBundle\Entity\ChatAssistant;
use Tourze\RAGFlowApiBundle\Entity\Conversation;
use Tourze\RAGFlowApiBundle\Entity\Dataset;

/**
 * @internal
 */
#[CoversClass(ChatAssistant::class)]
class ChatAssistantTest extends AbstractEntityTestCase
{
    use DoctrineTrait;
    use ServiceLocatorTrait;

    protected function createEntity(): ChatAssistant
    {
        $assistant = new ChatAssistant();
        $assistant->setName('test-assistant');

        return $assistant;
    }

    public function testCreateChatAssistant(): void
    {
        $assistant = new ChatAssistant();
        $assistant->setName('测试助手');
        $assistant->setDescription('测试描述');
        $assistant->setLlmModel('gpt-3.5-turbo');
        $assistant->setTemperature(0.7);
        $assistant->setMaxTokens(4096);

        $this->assertEquals('测试助手', $assistant->getName());
        $this->assertEquals('测试描述', $assistant->getDescription());
        $this->assertEquals('gpt-3.5-turbo', $assistant->getLlmModel());
        $this->assertEquals(0.7, $assistant->getTemperature());
        $this->assertEquals(4096, $assistant->getMaxTokens());
        $this->assertTrue($assistant->isEnabled());
        $this->assertTrue($assistant->getShowQuote());
    }

    public function testDatasetRelation(): void
    {
        $dataset = new Dataset();
        $dataset->setName('测试数据集');
        $dataset->setDescription('测试数据集描述');

        $assistant = new ChatAssistant();
        $assistant->setName('测试助手');
        $assistant->setDataset($dataset);

        $this->assertSame($dataset, $assistant->getDataset());
    }

    public function testConversationsCollection(): void
    {
        $assistant = new ChatAssistant();
        $assistant->setName('测试助手');

        // 初始状态下应该是空集合
        $this->assertCount(0, $assistant->getConversations());

        $conversation1 = new Conversation();
        $conversation1->setTitle('对话1');
        $conversation1->setChatAssistant($assistant);

        $conversation2 = new Conversation();
        $conversation2->setTitle('对话2');
        $conversation2->setChatAssistant($assistant);

        // 添加对话
        $assistant->addConversation($conversation1);
        $assistant->addConversation($conversation2);

        $this->assertCount(2, $assistant->getConversations());
        $this->assertTrue($assistant->getConversations()->contains($conversation1));
        $this->assertTrue($assistant->getConversations()->contains($conversation2));

        // 移除对话
        $assistant->removeConversation($conversation1);
        $this->assertCount(1, $assistant->getConversations());
        $this->assertFalse($assistant->getConversations()->contains($conversation1));
        $this->assertTrue($assistant->getConversations()->contains($conversation2));
    }

    public function testDatasetIds(): void
    {
        $assistant = new ChatAssistant();
        $assistant->setName('测试助手');

        // 初始值为 null
        $this->assertNull($assistant->getDatasetIds());

        $datasetIds = ['dataset1', 'dataset2', 'dataset3'];
        $assistant->setDatasetIds($datasetIds);

        $this->assertEquals($datasetIds, $assistant->getDatasetIds());

        // 设置为 null
        $assistant->setDatasetIds(null);
        $this->assertNull($assistant->getDatasetIds());
    }

    public function testLlmModelSettings(): void
    {
        $assistant = new ChatAssistant();
        $assistant->setName('测试助手');

        // 测试温度参数
        $assistant->setTemperature(0.8);
        $this->assertEquals(0.8, $assistant->getTemperature());

        // 测试 top_p 参数
        $assistant->setTopP(0.95);
        $this->assertEquals(0.95, $assistant->getTopP());

        // 测试最大 tokens
        $assistant->setMaxTokens(2048);
        $this->assertEquals(2048, $assistant->getMaxTokens());

        // 测试存在惩罚参数
        $assistant->setPresencePenalty(0.1);
        $this->assertEquals(0.1, $assistant->getPresencePenalty());

        // 测试频率惩罚参数
        $assistant->setFrequencyPenalty(0.2);
        $this->assertEquals(0.2, $assistant->getFrequencyPenalty());
    }

    public function testSystemPromptAndConfig(): void
    {
        $assistant = new ChatAssistant();
        $assistant->setName('测试助手');

        $systemPrompt = '你是一个专业的AI助手，请帮助用户解答问题。';
        $assistant->setSystemPrompt($systemPrompt);
        $this->assertEquals($systemPrompt, $assistant->getSystemPrompt());

        $config = ['key1' => 'value1', 'key2' => ['nested' => 'value']];
        $assistant->setConfig($config);
        $this->assertEquals($config, $assistant->getConfig());
    }

    public function testUISettings(): void
    {
        $assistant = new ChatAssistant();
        $assistant->setName('测试助手');

        // 测试头像
        $avatarUrl = 'https://example.com/avatar.png';
        $assistant->setAvatar($avatarUrl);
        $this->assertEquals($avatarUrl, $assistant->getAvatar());

        // 测试语言
        $assistant->setLanguage('zh-CN');
        $this->assertEquals('zh-CN', $assistant->getLanguage());

        // 测试开场白
        $opener = '你好！我是你的AI助手，有什么可以帮助你的吗？';
        $assistant->setOpener($opener);
        $this->assertEquals($opener, $assistant->getOpener());

        // 测试空响应消息
        $emptyResponse = '抱歉，我没有找到相关信息。';
        $assistant->setEmptyResponse($emptyResponse);
        $this->assertEquals($emptyResponse, $assistant->getEmptyResponse());

        // 测试是否显示引用
        $assistant->setShowQuote(false);
        $this->assertFalse($assistant->getShowQuote());
    }

    public function testSearchSettings(): void
    {
        $assistant = new ChatAssistant();
        $assistant->setName('测试助手');

        // 测试相似度阈值
        $assistant->setSimilarityThreshold(0.75);
        $this->assertEquals(0.75, $assistant->getSimilarityThreshold());

        // 测试关键词相似度权重
        $assistant->setKeywordsSimilarityWeight(0.3);
        $this->assertEquals(0.3, $assistant->getKeywordsSimilarityWeight());

        // 测试返回top N个结果
        $assistant->setTopN(10);
        $this->assertEquals(10, $assistant->getTopN());

        // 测试top_k参数
        $assistant->setTopK(20);
        $this->assertEquals(20, $assistant->getTopK());
    }

    public function testVariablesAndRerank(): void
    {
        $assistant = new ChatAssistant();
        $assistant->setName('测试助手');

        // 测试提示词变量配置
        $variables = ['user_name' => 'string', 'context' => 'text'];
        $assistant->setVariables($variables);
        $this->assertEquals($variables, $assistant->getVariables());

        // 测试重排序模型
        $assistant->setRerankModel('bge-reranker-base');
        $this->assertEquals('bge-reranker-base', $assistant->getRerankModel());
    }

    public function testStatusAndMetadata(): void
    {
        $assistant = new ChatAssistant();
        $assistant->setName('测试助手');

        // 测试状态
        $assistant->setStatus('active');
        $this->assertEquals('active', $assistant->getStatus());

        // 测试提示词类型
        $assistant->setPromptType('custom');
        $this->assertEquals('custom', $assistant->getPromptType());

        // 测试是否引用
        $assistant->setDoRefer('yes');
        $this->assertEquals('yes', $assistant->getDoRefer());

        // 测试租户ID
        $assistant->setTenantId('tenant-123');
        $this->assertEquals('tenant-123', $assistant->getTenantId());
    }

    public function testTimestamps(): void
    {
        $assistant = new ChatAssistant();
        $assistant->setName('测试助手');

        $remoteCreateTime = new \DateTimeImmutable('2024-01-01 10:00:00');
        $assistant->setRemoteCreateTime($remoteCreateTime);
        $this->assertEquals($remoteCreateTime, $assistant->getRemoteCreateTime());

        $remoteUpdateTime = new \DateTimeImmutable('2024-01-01 11:00:00');
        $assistant->setRemoteUpdateTime($remoteUpdateTime);
        $this->assertEquals($remoteUpdateTime, $assistant->getRemoteUpdateTime());

        $lastSyncTime = new \DateTimeImmutable('2024-01-01 12:00:00');
        $assistant->setLastSyncTime($lastSyncTime);
        $this->assertEquals($lastSyncTime, $assistant->getLastSyncTime());
    }

    public function testRemoteId(): void
    {
        $assistant = new ChatAssistant();
        $assistant->setName('测试助手');

        // 初始值为 null
        $this->assertNull($assistant->getRemoteId());

        $remoteId = 'remote-assistant-123';
        $assistant->setRemoteId($remoteId);
        $this->assertEquals($remoteId, $assistant->getRemoteId());
    }

    public function testToString(): void
    {
        $assistant = new ChatAssistant();
        $assistantName = '测试AI助手';
        $assistant->setName($assistantName);

        $this->assertEquals($assistantName, (string) $assistant);
    }

    public function testDefaultValues(): void
    {
        $assistant = new ChatAssistant();

        $this->assertTrue($assistant->isEnabled());
        $this->assertTrue($assistant->getShowQuote());
        $this->assertInstanceOf(Collection::class, $assistant->getConversations());
        $this->assertCount(0, $assistant->getConversations());
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'name' => ['name', 'Test Assistant'];
        yield 'remoteId' => ['remoteId', 'remote-123'];
        yield 'description' => ['description', 'Test description'];
        yield 'systemPrompt' => ['systemPrompt', 'You are a helpful assistant'];
        yield 'llmModel' => ['llmModel', 'gpt-3.5-turbo'];
        yield 'temperature' => ['temperature', 0.7];
        yield 'topP' => ['topP', 0.9];
        yield 'maxTokens' => ['maxTokens', 4096];
        yield 'presencePenalty' => ['presencePenalty', 0.1];
        yield 'frequencyPenalty' => ['frequencyPenalty', 0.2];
        yield 'avatar' => ['avatar', 'https://example.com/avatar.png'];
        yield 'language' => ['language', 'zh-CN'];
        yield 'enabled' => ['enabled', true];
        yield 'showQuote' => ['showQuote', false];
        yield 'similarityThreshold' => ['similarityThreshold', 0.8];
        yield 'keywordsSimilarityWeight' => ['keywordsSimilarityWeight', 0.3];
        yield 'topN' => ['topN', 10];
        yield 'topK' => ['topK', 20];
        yield 'rerankModel' => ['rerankModel', 'bge-reranker-base'];
        yield 'status' => ['status', 'active'];
        yield 'promptType' => ['promptType', 'custom'];
        yield 'doRefer' => ['doRefer', 'yes'];
        yield 'tenantId' => ['tenantId', 'tenant-123'];
        yield 'opener' => ['opener', 'Hello!'];
        yield 'emptyResponse' => ['emptyResponse', 'Sorry, no result'];
    }
}
