<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\RAGFlowApiBundle\Entity\ChatAssistant;
use Tourze\RAGFlowApiBundle\Entity\Conversation;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Repository\ConversationRepository;

/**
 * @internal
 */
#[CoversClass(ConversationRepository::class)]
#[RunTestsInSeparateProcesses]
#[AsRepository(entityClass: Conversation::class)]
class ConversationRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // Repository tests don't need additional setup
    }

    protected function getRepository(): ConversationRepository
    {
        /** @var ConversationRepository */
        return self::getService(ConversationRepository::class);
    }

    protected function createNewEntity(): object
    {
        $ragFlowInstance = $this->createRAGFlowInstance('测试实例');
        $dataset = $this->createDataset($ragFlowInstance, '测试数据集');
        $chatAssistant = new ChatAssistant();
        $chatAssistant->setName('测试聊天助手');
        $chatAssistant->setDataset($dataset);
        $this->persistAndFlush($chatAssistant);

        $conversation = new Conversation();
        $conversation->setRemoteId('test-conversation-' . uniqid());
        $conversation->setRagFlowInstance($ragFlowInstance);
        $conversation->setTitle('测试对话');
        $conversation->setUserId('test-user');
        $conversation->setChatAssistant($chatAssistant);

        return $conversation;
    }

    public function testRepositoryCreation(): void
    {
        $this->assertInstanceOf(ConversationRepository::class, $this->getRepository());
    }

    public function testFindByRemoteId(): void
    {
        // 创建测试实例
        $ragFlowInstance = $this->createRAGFlowInstance('对话远程ID测试实例');

        // 创建测试对话
        $conversation = new Conversation();
        $conversation->setRemoteId('remote-conversation-456');
        $conversation->setRagFlowInstance($ragFlowInstance);
        $conversation->setTitle('测试对话');
        $conversation->setUserId('user123');
        $persistedConversation = $this->persistAndFlush($conversation);
        $this->assertInstanceOf(Conversation::class, $persistedConversation);

        // 测试通过远程ID查找
        $foundConversation = $this->getRepository()->findByRemoteId('remote-conversation-456');
        $this->assertNotNull($foundConversation);
        $this->assertEquals($persistedConversation->getId(), $foundConversation->getId());
        $this->assertEquals('remote-conversation-456', $foundConversation->getRemoteId());

        // 测试查找不存在的远程ID
        $notFound = $this->getRepository()->findByRemoteId('non-existent-remote-id');
        $this->assertNull($notFound);
    }

    public function testFindByChatAssistant(): void
    {
        // 创建测试实例
        $ragFlowInstance = $this->createRAGFlowInstance('聊天助手查找测试实例');

        // 创建测试数据集
        $dataset = $this->createDataset($ragFlowInstance, '聊天助手测试数据集');

        // 创建聊天助手
        $chatAssistant = new ChatAssistant();
        $chatAssistant->setName('测试聊天助手');
        $chatAssistant->setDescription('用于测试的聊天助手');
        $chatAssistant->setDataset($dataset);
        $persistedChatAssistant = $this->persistAndFlush($chatAssistant);
        $this->assertInstanceOf(ChatAssistant::class, $persistedChatAssistant);

        // 创建多个对话
        $conversation1 = new Conversation();
        $conversation1->setRemoteId('remote-conversation-1');
        $conversation1->setRagFlowInstance($ragFlowInstance);
        $conversation1->setTitle('第一个对话');
        $conversation1->setChatAssistant($persistedChatAssistant);
        $conversation1->setLastActivityTime(new \DateTimeImmutable('2024-01-01 10:00:00'));
        $this->persistAndFlush($conversation1);

        $conversation2 = new Conversation();
        $conversation2->setRemoteId('remote-conversation-2');
        $conversation2->setRagFlowInstance($ragFlowInstance);
        $conversation2->setTitle('第二个对话');
        $conversation2->setChatAssistant($persistedChatAssistant);
        $conversation2->setLastActivityTime(new \DateTimeImmutable('2024-01-02 15:00:00'));
        $this->persistAndFlush($conversation2);

        $conversation3 = new Conversation();
        $conversation3->setRemoteId('remote-conversation-3');
        $conversation3->setRagFlowInstance($ragFlowInstance);
        $conversation3->setTitle('第三个对话');
        $conversation3->setChatAssistant($persistedChatAssistant);
        $conversation3->setLastActivityTime(new \DateTimeImmutable('2024-01-01 20:00:00'));
        $this->persistAndFlush($conversation3);

        // 测试按聊天助手查找对话
        $conversations = $this->getRepository()->findByChatAssistant($persistedChatAssistant);
        $this->assertCount(3, $conversations);

        // 验证按最后活动时间降序排列
        $activityTimes = array_map(static fn (Conversation $conv): ?\DateTimeImmutable => $conv->getLastActivityTime(), $conversations);
        $this->assertEquals(
            [new \DateTimeImmutable('2024-01-02 15:00:00'), new \DateTimeImmutable('2024-01-01 20:00:00'), new \DateTimeImmutable('2024-01-01 10:00:00')],
            $activityTimes
        );
    }

    public function testFindByUserId(): void
    {
        // 创建测试实例
        $ragFlowInstance = $this->createRAGFlowInstance('用户ID查找测试实例');

        // 创建多个用户的对话
        $conversation1 = new Conversation();
        $conversation1->setRemoteId('remote-conversation-user1-1');
        $conversation1->setRagFlowInstance($ragFlowInstance);
        $conversation1->setTitle('用户1的第一个对话');
        $conversation1->setUserId('user1');
        $conversation1->setLastActivityTime(new \DateTimeImmutable('2024-01-02 10:00:00'));
        $this->persistAndFlush($conversation1);

        $conversation2 = new Conversation();
        $conversation2->setRemoteId('remote-conversation-user1-2');
        $conversation2->setRagFlowInstance($ragFlowInstance);
        $conversation2->setTitle('用户1的第二个对话');
        $conversation2->setUserId('user1');
        $conversation2->setLastActivityTime(new \DateTimeImmutable('2024-01-01 15:00:00'));
        $this->persistAndFlush($conversation2);

        $conversation3 = new Conversation();
        $conversation3->setRemoteId('remote-conversation-user2-1');
        $conversation3->setRagFlowInstance($ragFlowInstance);
        $conversation3->setTitle('用户2的第一个对话');
        $conversation3->setUserId('user2');
        $conversation3->setLastActivityTime(new \DateTimeImmutable('2024-01-03 09:00:00'));
        $this->persistAndFlush($conversation3);

        // 测试按用户ID查找
        $user1Conversations = $this->getRepository()->findByUserId('user1');
        $this->assertCount(2, $user1Conversations);

        foreach ($user1Conversations as $conversation) {
            $this->assertEquals('user1', $conversation->getUserId());
        }

        $user2Conversations = $this->getRepository()->findByUserId('user2');
        $this->assertCount(1, $user2Conversations);

        foreach ($user2Conversations as $conversation) {
            $this->assertEquals('user2', $conversation->getUserId());
        }

        // 验证按最后活动时间降序排列
        $activityTimes = array_map(static fn (Conversation $conv): ?\DateTimeImmutable => $conv->getLastActivityTime(), $user1Conversations);
        $this->assertEquals(
            [new \DateTimeImmutable('2024-01-02 10:00:00'), new \DateTimeImmutable('2024-01-01 15:00:00')],
            $activityTimes
        );
    }

    public function testFindByTitle(): void
    {
        // 创建测试实例
        $ragFlowInstance = $this->createRAGFlowInstance('标题查找测试实例');

        // 创建不同标题的对话
        $conversations = [
            ['remoteId' => 'remote-conversation-1', 'title' => '人工智能技术讨论'],
            ['remoteId' => 'remote-conversation-2', 'title' => '机器学习相关问题'],
            ['remoteId' => 'remote-conversation-3', 'title' => '人工智能应用案例'],
            ['remoteId' => 'remote-conversation-4', 'title' => '深度学习技术细节'],
        ];

        foreach ($conversations as $convData) {
            $conversation = new Conversation();
            $conversation->setRemoteId($convData['remoteId']);
            $conversation->setRagFlowInstance($ragFlowInstance);
            $conversation->setTitle($convData['title']);
            $conversation->setLastActivityTime(new \DateTimeImmutable('2024-01-' . (int) substr($convData['remoteId'], -1) . ' 10:00:00'));
            $this->persistAndFlush($conversation);
        }

        // 搜索包含"人工智能"的对话
        $aiConversations = $this->getRepository()->findByTitle('人工智能');
        $this->assertGreaterThanOrEqual(2, count($aiConversations));

        foreach ($aiConversations as $conversation) {
            $this->assertStringContainsString('人工智能', $conversation->getTitle());
        }

        // 搜索包含"学习"的对话
        $learningConversations = $this->getRepository()->findByTitle('学习');
        $this->assertGreaterThanOrEqual(2, count($learningConversations));

        foreach ($learningConversations as $conversation) {
            $this->assertStringContainsString('学习', $conversation->getTitle());
        }
    }

    public function testFindActiveConversations(): void
    {
        // 创建测试实例
        $ragFlowInstance = $this->createRAGFlowInstance('活跃对话测试实例');

        $since = new \DateTimeImmutable('2024-01-01 00:00:00');

        // 创建活跃对话（最近活动）
        $activeConversation1 = new Conversation();
        $activeConversation1->setRemoteId('remote-conversation-active-1');
        $activeConversation1->setRagFlowInstance($ragFlowInstance);
        $activeConversation1->setTitle('活跃对话1');
        $activeConversation1->setLastActivityTime(new \DateTimeImmutable('2024-01-05 10:00:00'));
        $this->persistAndFlush($activeConversation1);

        $activeConversation2 = new Conversation();
        $activeConversation2->setRemoteId('remote-conversation-active-2');
        $activeConversation2->setRagFlowInstance($ragFlowInstance);
        $activeConversation2->setTitle('活跃对话2');
        $activeConversation2->setLastActivityTime(new \DateTimeImmutable('2024-01-03 15:00:00'));
        $this->persistAndFlush($activeConversation2);

        // 创建不活跃对话（较早活动）
        $inactiveConversation = new Conversation();
        $inactiveConversation->setRemoteId('remote-conversation-inactive');
        $inactiveConversation->setRagFlowInstance($ragFlowInstance);
        $inactiveConversation->setTitle('不活跃对话');
        $inactiveConversation->setLastActivityTime(new \DateTimeImmutable('2023-12-01 10:00:00'));
        $this->persistAndFlush($inactiveConversation);

        // 创建没有活动时间的对话
        $noActivityConversation = new Conversation();
        $noActivityConversation->setRemoteId('remote-conversation-no-activity');
        $noActivityConversation->setRagFlowInstance($ragFlowInstance);
        $noActivityConversation->setTitle('无活动时间对话');
        $this->persistAndFlush($noActivityConversation);

        // 测试查找活跃对话
        $activeConversations = $this->getRepository()->findActiveConversations($since);
        $this->assertGreaterThanOrEqual(2, count($activeConversations));

        foreach ($activeConversations as $conversation) {
            $lastActivityTime = $conversation->getLastActivityTime();
            $this->assertNotNull($lastActivityTime);
            $this->assertGreaterThanOrEqual($since, $lastActivityTime);
        }

        // 验证按最后活动时间降序排列
        $activityTimes = array_map(static fn (Conversation $conv): ?\DateTimeImmutable => $conv->getLastActivityTime(), $activeConversations);
        for ($i = 0; $i < count($activityTimes) - 1; ++$i) {
            $this->assertGreaterThanOrEqual($activityTimes[$i + 1], $activityTimes[$i]);
        }
    }

    public function testFindPendingSync(): void
    {
        // 创建测试实例
        $ragFlowInstance = $this->createRAGFlowInstance('同步测试实例');

        $since = new \DateTimeImmutable('2024-01-01 00:00:00');

        // 创建未同步的对话
        $unsyncedConversation = new Conversation();
        $unsyncedConversation->setRemoteId('remote-conversation-unsynced');
        $unsyncedConversation->setRagFlowInstance($ragFlowInstance);
        $unsyncedConversation->setTitle('未同步的对话');
        // lastSyncTime 为 null
        $this->persistAndFlush($unsyncedConversation);

        // 创建同步时间较早的对话
        $oldSyncConversation = new Conversation();
        $oldSyncConversation->setRemoteId('remote-conversation-old-sync');
        $oldSyncConversation->setRagFlowInstance($ragFlowInstance);
        $oldSyncConversation->setTitle('同步时间较早的对话');
        $oldSyncConversation->setLastSyncTime(new \DateTimeImmutable('2023-12-01 00:00:00'));
        $this->persistAndFlush($oldSyncConversation);

        // 创建最近同步的对话
        $recentSyncConversation = new Conversation();
        $recentSyncConversation->setRemoteId('remote-conversation-recent-sync');
        $recentSyncConversation->setRagFlowInstance($ragFlowInstance);
        $recentSyncConversation->setTitle('最近同步的对话');
        $recentSyncConversation->setLastSyncTime(new \DateTimeImmutable('2024-01-15 00:00:00'));
        $this->persistAndFlush($recentSyncConversation);

        // 测试查找需要同步的对话
        $pendingSyncConversations = $this->getRepository()->findPendingSync($since);
        $this->assertGreaterThanOrEqual(2, count($pendingSyncConversations));

        foreach ($pendingSyncConversations as $conversation) {
            $lastSyncTime = $conversation->getLastSyncTime();
            $this->assertTrue(null === $lastSyncTime || $lastSyncTime < $since);
        }
    }

    public function testFindWithFilters(): void
    {
        // 创建测试实例
        $ragFlowInstance = $this->createRAGFlowInstance('筛选测试实例');

        // 创建测试数据集和聊天助手
        $dataset = $this->createDataset($ragFlowInstance, '筛选测试数据集');
        $chatAssistant = new ChatAssistant();
        $chatAssistant->setName('测试聊天助手');
        $chatAssistant->setDataset($dataset);
        $persistedChatAssistant = $this->persistAndFlush($chatAssistant);
        $this->assertInstanceOf(ChatAssistant::class, $persistedChatAssistant);

        // 创建不同条件的对话
        $conversation1 = new Conversation();
        $conversation1->setRemoteId('remote-conversation-filter-1');
        $conversation1->setRagFlowInstance($ragFlowInstance);
        $conversation1->setTitle('人工智能技术讨论');
        $conversation1->setUserId('user1');
        $conversation1->setChatAssistant($persistedChatAssistant);
        $conversation1->setStatus('active');
        $conversation1->setLastActivityTime(new \DateTimeImmutable('2024-01-05 10:00:00'));
        $this->persistAndFlush($conversation1);

        $conversation2 = new Conversation();
        $conversation2->setRemoteId('remote-conversation-filter-2');
        $conversation2->setRagFlowInstance($ragFlowInstance);
        $conversation2->setTitle('机器学习相关问题');
        $conversation2->setUserId('user2');
        $conversation2->setChatAssistant($persistedChatAssistant);
        $conversation2->setStatus('archived');
        $conversation2->setLastActivityTime(new \DateTimeImmutable('2024-01-03 15:00:00'));
        $this->persistAndFlush($conversation2);

        $conversation3 = new Conversation();
        $conversation3->setRemoteId('remote-conversation-filter-3');
        $conversation3->setRagFlowInstance($ragFlowInstance);
        $conversation3->setTitle('深度学习技术细节');
        $conversation3->setUserId('user1');
        $conversation3->setStatus('active');
        $conversation3->setLastActivityTime(new \DateTimeImmutable('2024-01-01 09:00:00'));
        $this->persistAndFlush($conversation3);

        // 测试标题筛选
        $titleFilters = ['title' => '人工智能'];
        $titleResult = $this->getRepository()->findWithFilters($titleFilters);
        $this->assertGreaterThanOrEqual(1, $titleResult['total']);

        foreach ($titleResult['items'] as $conversation) {
            $this->assertStringContainsString('人工智能', $conversation->getTitle());
        }

        // 测试用户筛选
        $userFilters = ['user_id' => 'user1'];
        $userResult = $this->getRepository()->findWithFilters($userFilters);
        $this->assertGreaterThanOrEqual(2, $userResult['total']);

        foreach ($userResult['items'] as $conversation) {
            $this->assertEquals('user1', $conversation->getUserId());
        }

        // 测试聊天助手筛选
        $assistantFilters = ['chat_assistant_id' => $persistedChatAssistant->getId()];
        $assistantResult = $this->getRepository()->findWithFilters($assistantFilters);
        $this->assertGreaterThanOrEqual(3, $assistantResult['total']);

        foreach ($assistantResult['items'] as $conversation) {
            $chatAssistant = $conversation->getChatAssistant();
            $this->assertNotNull($chatAssistant);
            $this->assertEquals($persistedChatAssistant->getId(), $chatAssistant->getId());
        }

        // 测试状态筛选
        $statusFilters = ['status' => 'active'];
        $statusResult = $this->getRepository()->findWithFilters($statusFilters);
        $this->assertGreaterThanOrEqual(2, $statusResult['total']);

        foreach ($statusResult['items'] as $conversation) {
            $this->assertEquals('active', $conversation->getStatus());
        }

        // 测试时间筛选
        $since = new \DateTimeImmutable('2024-01-02 00:00:00');
        $sinceFilters = ['since' => $since];
        $sinceResult = $this->getRepository()->findWithFilters($sinceFilters);
        $this->assertGreaterThanOrEqual(1, $sinceResult['total']);

        foreach ($sinceResult['items'] as $conversation) {
            $this->assertGreaterThanOrEqual($since, $conversation->getLastActivityTime());
        }

        // 测试分页
        $paginationResult = $this->getRepository()->findWithFilters([], 1, 2);
        $this->assertGreaterThanOrEqual(3, $paginationResult['total']);
        $this->assertCount(2, $paginationResult['items']);
    }

    public function testCountByChatAssistant(): void
    {
        // 创建测试实例
        $ragFlowInstance = $this->createRAGFlowInstance('计数测试实例');

        // 创建测试数据集和聊天助手
        $dataset = $this->createDataset($ragFlowInstance, '计数测试数据集');
        $chatAssistant = new ChatAssistant();
        $chatAssistant->setName('计数测试聊天助手');
        $chatAssistant->setDataset($dataset);
        $persistedChatAssistant = $this->persistAndFlush($chatAssistant);
        $this->assertInstanceOf(ChatAssistant::class, $persistedChatAssistant);

        $initialCount = $this->getRepository()->countByChatAssistant($persistedChatAssistant);

        // 为聊天助手创建多个对话
        for ($i = 1; $i <= 5; ++$i) {
            $conversation = new Conversation();
            $conversation->setRemoteId("remote-conversation-count-{$i}");
            $conversation->setRagFlowInstance($ragFlowInstance);
            $conversation->setTitle("计数测试对话{$i}");
            $conversation->setChatAssistant($persistedChatAssistant);
            $this->persistAndFlush($conversation);
        }

        $finalCount = $this->getRepository()->countByChatAssistant($persistedChatAssistant);
        $this->assertEquals($initialCount + 5, $finalCount);
    }

    public function testSave(): void
    {
        // 创建测试实例
        $ragFlowInstance = $this->createRAGFlowInstance('保存测试实例');

        // 创建新的对话并保存
        $conversation = new Conversation();
        $conversation->setRemoteId('remote-conversation-save-test');
        $conversation->setRagFlowInstance($ragFlowInstance);
        $conversation->setTitle('测试保存的对话');
        $conversation->setUserId('test-user');
        $conversation->setStatus('active');

        // 测试保存
        $this->getRepository()->save($conversation);

        // 验证对话已保存到数据库
        $savedConversation = self::getEntityManager()->find(Conversation::class, $conversation->getId());
        $this->assertNotNull($savedConversation);
        $this->assertEquals('remote-conversation-save-test', $savedConversation->getRemoteId());
        $this->assertEquals('测试保存的对话', $savedConversation->getTitle());
        $this->assertEquals('test-user', $savedConversation->getUserId());
        $this->assertEquals('active', $savedConversation->getStatus());
    }

    public function testRemove(): void
    {
        // 创建测试实例
        $ragFlowInstance = $this->createRAGFlowInstance('删除测试实例');

        // 创建要删除的对话
        $conversation = new Conversation();
        $conversation->setRemoteId('remote-conversation-remove-test');
        $conversation->setRagFlowInstance($ragFlowInstance);
        $conversation->setTitle('测试删除的对话');
        $conversation->setUserId('test-user');
        $this->persistAndFlush($conversation);

        $conversationId = $conversation->getId();
        $this->assertNotNull($conversationId);

        // 验证对话存在
        $existingConversation = self::getEntityManager()->find(Conversation::class, $conversationId);
        $this->assertNotNull($existingConversation);

        // 测试删除
        $this->getRepository()->remove($conversation);

        // 验证对话已从数据库删除
        $deletedConversation = self::getEntityManager()->find(Conversation::class, $conversationId);
        $this->assertNull($deletedConversation);
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

    /**
     * 创建测试数据集
     */
    private function createDataset(RAGFlowInstance $ragFlowInstance, string $name): Dataset
    {
        $dataset = new Dataset();
        $dataset->setName($name);
        $dataset->setDescription("用于{$name}测试的数据集");
        $dataset->setRagFlowInstance($ragFlowInstance);
        $persistedDataset = $this->persistAndFlush($dataset);

        $this->assertInstanceOf(Dataset::class, $persistedDataset);
        /** @var Dataset $persistedDataset */

        return $persistedDataset;
    }
}
