<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\PHPUnitSymfonyKernelTest\DoctrineTrait;
use Tourze\PHPUnitSymfonyKernelTest\ServiceLocatorTrait;
use Tourze\RAGFlowApiBundle\Entity\ChatAssistant;
use Tourze\RAGFlowApiBundle\Entity\Conversation;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;

/**
 * @internal
 */
#[CoversClass(Conversation::class)]
class ConversationTest extends AbstractEntityTestCase
{
    use DoctrineTrait;
    use ServiceLocatorTrait;

    protected function createEntity(): Conversation
    {
        $conversation = new Conversation();
        $conversation->setTitle('test-conversation');

        return $conversation;
    }

    public function testCreateConversation(): void
    {
        $conversation = new Conversation();
        $conversation->setTitle('测试对话');
        $conversation->setRemoteId('remote-123');

        $this->assertEquals('测试对话', $conversation->getTitle());
        $this->assertEquals('remote-123', $conversation->getRemoteId());
    }

    public function testChatAssistantRelation(): void
    {
        $ragFlowInstance = new RAGFlowInstance();
        $ragFlowInstance->setName('测试实例');
        $ragFlowInstance->setApiUrl('https://test.ragflow.io');
        $ragFlowInstance->setApiKey('test-key');

        $dataset = new Dataset();
        $dataset->setName('测试数据集');
        $dataset->setDescription('测试数据集');
        $dataset->setRagFlowInstance($ragFlowInstance);

        $assistant = new ChatAssistant();
        $assistant->setName('测试助手');
        $assistant->setDataset($dataset);

        $conversation = new Conversation();
        $conversation->setTitle('关联测试对话');
        $conversation->setRemoteId('test-remote-id');
        $conversation->setRagFlowInstance($ragFlowInstance);
        $conversation->setChatAssistant($assistant);

        $this->assertSame($assistant, $conversation->getChatAssistant());
    }

    public function testRemoteId(): void
    {
        $conversation = new Conversation();
        $conversation->setTitle('测试对话');
        $conversation->setRemoteId('initial-remote-id');

        // 验证初始设置值
        $this->assertEquals('initial-remote-id', $conversation->getRemoteId());

        $remoteId = 'remote-conversation-456';
        $conversation->setRemoteId($remoteId);
        $this->assertEquals($remoteId, $conversation->getRemoteId());
    }

    public function testUserId(): void
    {
        $conversation = new Conversation();
        $conversation->setTitle('测试对话');
        $conversation->setRemoteId('test-remote-id');

        // 初始值为 null
        $this->assertNull($conversation->getUserId());

        $userId = 'unique-user-789';
        $conversation->setUserId($userId);
        $this->assertEquals($userId, $conversation->getUserId());
    }

    public function testMessagesHistory(): void
    {
        $conversation = new Conversation();
        $conversation->setTitle('测试对话');
        $conversation->setRemoteId('message-test-id');

        // 测试消息历史记录
        $messages = [
            [
                'role' => 'user',
                'content' => '你好，请介绍一下人工智能',
                'timestamp' => '2024-01-01T10:00:00Z',
            ],
            [
                'role' => 'assistant',
                'content' => '人工智能是计算机科学的一个分支...',
                'timestamp' => '2024-01-01T10:00:05Z',
            ],
            [
                'role' => 'user',
                'content' => '能详细说说机器学习吗？',
                'timestamp' => '2024-01-01T10:01:00Z',
            ],
        ];

        $conversation->setMessages($messages);
        $this->assertEquals($messages, $conversation->getMessages());

        // 测试设置为 null
        $conversation->setMessages(null);
        $this->assertNull($conversation->getMessages());
    }

    public function testConversationContext(): void
    {
        $conversation = new Conversation();
        $conversation->setTitle('测试对话');
        $conversation->setRemoteId('test-remote-id');

        // 测试上下文
        $context = [
            'user_ip' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0...',
            'language' => 'zh-CN',
            'model_version' => 'gpt-3.5-turbo-1106',
            'total_tokens' => 1250,
            'conversation_length' => 5,
            'topics' => ['AI', '机器学习', '深度学习'],
        ];

        $conversation->setContext($context);
        $this->assertEquals($context, $conversation->getContext());

        // 测试设置为 null
        $conversation->setContext(null);
        $this->assertNull($conversation->getContext());
    }

    public function testTimestamps(): void
    {
        $conversation = new Conversation();
        $conversation->setTitle('测试对话');
        $conversation->setRemoteId('timestamp-test-id');

        $remoteCreateTime = new \DateTimeImmutable('2024-01-01 10:00:00');
        $conversation->setRemoteCreateTime($remoteCreateTime);
        $this->assertEquals($remoteCreateTime, $conversation->getRemoteCreateTime());

        $remoteUpdateTime = new \DateTimeImmutable('2024-01-01 11:00:00');
        $conversation->setRemoteUpdateTime($remoteUpdateTime);
        $this->assertEquals($remoteUpdateTime, $conversation->getRemoteUpdateTime());

        $lastSyncTime = new \DateTimeImmutable('2024-01-01 12:00:00');
        $conversation->setLastSyncTime($lastSyncTime);
        $this->assertEquals($lastSyncTime, $conversation->getLastSyncTime());
    }

    public function testToString(): void
    {
        $conversation = new Conversation();
        $title = '重要商务对话';
        $conversation->setTitle($title);
        $conversation->setRemoteId('tostring-test-id');

        $this->assertEquals($title, (string) $conversation);
    }

    public function testConversationStatus(): void
    {
        $conversation = new Conversation();
        $conversation->setTitle('状态测试对话');
        $conversation->setRemoteId('status-test-id');

        // 测试对话状态
        $conversation->setStatus('active');
        $this->assertEquals('active', $conversation->getStatus());

        $conversation->setStatus('completed');
        $this->assertEquals('completed', $conversation->getStatus());

        $conversation->setStatus('archived');
        $this->assertEquals('archived', $conversation->getStatus());
    }

    public function testComplexMessagesStructure(): void
    {
        $conversation = new Conversation();
        $conversation->setTitle('复杂消息结构测试');
        $conversation->setRemoteId('complex-test-id');

        // 测试包含复杂结构的消息
        $complexMessages = [
            [
                'role' => 'user',
                'content' => '请分析这份财务报告',
                'timestamp' => '2024-01-01T10:00:00Z',
                'attachments' => [
                    ['type' => 'file', 'name' => 'report.pdf', 'size' => 1024000],
                ],
                'metadata' => ['intent' => 'analysis', 'urgency' => 'high'],
            ],
            [
                'role' => 'assistant',
                'content' => '我已经分析了您的财务报告，以下是关键发现：',
                'timestamp' => '2024-01-01T10:00:30Z',
                'references' => [
                    ['document' => 'report.pdf', 'page' => 3, 'confidence' => 0.95],
                    ['document' => 'report.pdf', 'page' => 7, 'confidence' => 0.88],
                ],
                'thinking_process' => '首先分析了营收数据...',
                'tokens_used' => 450,
            ],
        ];

        $conversation->setMessages($complexMessages);
        $this->assertEquals($complexMessages, $conversation->getMessages());

        // 验证消息数量
        $messages = $conversation->getMessages();
        $this->assertIsArray($messages);
        $this->assertCount(2, $messages);
        $this->assertEquals(2, $conversation->getMessageCount());

        // 验证第一条消息的结构
        $this->assertIsArray($messages[0]);
        $firstMessage = $messages[0];
        $this->assertEquals('user', $firstMessage['role']);
        $this->assertArrayHasKey('attachments', $firstMessage);
        $this->assertArrayHasKey('metadata', $firstMessage);

        // 验证第二条消息的结构
        $this->assertIsArray($messages[1]);
        $secondMessage = $messages[1];
        $this->assertEquals('assistant', $secondMessage['role']);
        $this->assertArrayHasKey('references', $secondMessage);
        $this->assertArrayHasKey('thinking_process', $secondMessage);
        $this->assertEquals(450, $secondMessage['tokens_used']);
    }

    public function testNullableFields(): void
    {
        $conversation = new Conversation();
        $conversation->setTitle('可空字段测试');

        // 设置必需字段
        $conversation->setRemoteId('test-id');

        // 测试可为空的字段
        $this->assertNull($conversation->getChatAssistant());
        $this->assertNull($conversation->getRagFlowInstance());
        $this->assertNull($conversation->getUserId());
        $this->assertNull($conversation->getMessages());
        $this->assertNull($conversation->getContext());
        $this->assertNull($conversation->getStatus());
        $this->assertNull($conversation->getLastActivityTime());
        $this->assertNull($conversation->getRemoteCreateTime());
        $this->assertNull($conversation->getRemoteUpdateTime());
        $this->assertNull($conversation->getLastSyncTime());
        $this->assertNull($conversation->getDialog());
    }

    public function testBidirectionalRelationship(): void
    {
        $ragFlowInstance = new RAGFlowInstance();
        $ragFlowInstance->setName('关系测试实例');
        $ragFlowInstance->setApiUrl('https://test.ragflow.io');
        $ragFlowInstance->setApiKey('test-key');

        $dataset = new Dataset();
        $dataset->setName('关系测试数据集');
        $dataset->setDescription('测试双向关系');
        $dataset->setRagFlowInstance($ragFlowInstance);

        $assistant = new ChatAssistant();
        $assistant->setName('关系测试助手');
        $assistant->setDataset($dataset);

        $conversation = new Conversation();
        $conversation->setTitle('关系测试对话');
        $conversation->setRemoteId('test-remote-id');
        $conversation->setRagFlowInstance($ragFlowInstance);
        $conversation->setChatAssistant($assistant);

        // 测试双向关系设置
        $assistant->addConversation($conversation);

        $this->assertTrue($assistant->getConversations()->contains($conversation));
        $this->assertSame($assistant, $conversation->getChatAssistant());

        // 测试关系移除
        $assistant->removeConversation($conversation);
        $this->assertFalse($assistant->getConversations()->contains($conversation));

        // 注意：移除后conversation的chatAssistant应该被设置为null
        $this->assertNull($conversation->getChatAssistant());
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'remoteId' => ['remoteId', 'remote-conv-123'];
        yield 'title' => ['title', 'Test Conversation'];
        yield 'userId' => ['userId', 'user-123'];
        yield 'status' => ['status', 'active'];
        yield 'messageCount' => ['messageCount', 5];
    }
}
