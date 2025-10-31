<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\RAGFlowApiBundle\Entity\ChatAssistant;
use Tourze\RAGFlowApiBundle\Entity\Conversation;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Repository\RAGFlowInstanceRepository;

#[When(env: 'test')]
#[When(env: 'dev')]
class ConversationFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public const CONVERSATION_REFERENCE_PREFIX = 'conversation-';
    public const CONVERSATION_COUNT = 5;

    public function __construct(
        private readonly RAGFlowInstanceRepository $instanceRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $instance = $this->instanceRepository->findOneBy(['name' => 'user1']);
        if (null === $instance) {
            throw new \RuntimeException('RAGFlowInstance not found. Please load RAGFlowInstanceFixtures first.');
        }

        $conversations = [
            [
                'title' => 'Python学习咨询',
                'userId' => 'user-001',
                'messageCount' => 8,
                'status' => 'active',
                'chatAssistantIndex' => 0,
                'messages' => [
                    'msg_1' => ['role' => 'user', 'content' => '如何开始学习Python？', 'timestamp' => time() - 3600],
                    'msg_2' => ['role' => 'assistant', 'content' => '学习Python可以从基础语法开始...', 'timestamp' => time() - 3500],
                ],
            ],
            [
                'title' => '代码审查讨论',
                'userId' => 'user-002',
                'messageCount' => 12,
                'status' => 'active',
                'chatAssistantIndex' => 1,
                'messages' => [
                    'msg_1' => ['role' => 'user', 'content' => '请帮我审查这段代码', 'timestamp' => time() - 7200],
                    'msg_2' => ['role' => 'assistant', 'content' => '我看了你的代码，有以下建议...', 'timestamp' => time() - 7100],
                ],
            ],
            [
                'title' => '英文翻译服务',
                'userId' => 'user-001',
                'messageCount' => 5,
                'status' => 'completed',
                'chatAssistantIndex' => 2,
                'messages' => [
                    'msg_1' => ['role' => 'user', 'content' => '请翻译这段文字', 'timestamp' => time() - 86400],
                    'msg_2' => ['role' => 'assistant', 'content' => '翻译结果如下...', 'timestamp' => time() - 86300],
                ],
            ],
            [
                'title' => '数学问题求解',
                'userId' => 'user-003',
                'messageCount' => 6,
                'status' => 'active',
                'chatAssistantIndex' => 3,
                'messages' => [
                    'msg_1' => ['role' => 'user', 'content' => '如何求解二次方程？', 'timestamp' => time() - 1800],
                    'msg_2' => ['role' => 'assistant', 'content' => '二次方程的求解公式是...', 'timestamp' => time() - 1700],
                ],
            ],
            [
                'title' => '创意写作灵感',
                'userId' => 'user-004',
                'messageCount' => 15,
                'status' => 'active',
                'chatAssistantIndex' => 4,
                'messages' => [
                    'msg_1' => ['role' => 'user', 'content' => '帮我写一个科幻故事的开头', 'timestamp' => time() - 10800],
                    'msg_2' => ['role' => 'assistant', 'content' => '在2157年的地球...', 'timestamp' => time() - 10700],
                ],
            ],
        ];

        $now = new \DateTimeImmutable();

        for ($i = 0; $i < self::CONVERSATION_COUNT; ++$i) {
            $data = $conversations[$i];
            $conversation = new Conversation();

            $conversation->setRemoteId('conv-' . ($i + 1));
            $conversation->setTitle($data['title']);
            $conversation->setUserId($data['userId']);
            $conversation->setMessageCount($data['messageCount']);
            $conversation->setStatus($data['status']);
            $conversation->setMessages($data['messages']);

            // 设置关联的RAGFlowInstance
            $conversation->setRagFlowInstance($instance);

            // 设置关联的ChatAssistant
            /** @var ChatAssistant $chatAssistant */
            $chatAssistant = $this->getReference(ChatAssistantFixtures::CHAT_ASSISTANT_REFERENCE_PREFIX . $data['chatAssistantIndex'], ChatAssistant::class);
            $conversation->setChatAssistant($chatAssistant);

            // 设置对话上下文
            $conversation->setContext([
                'user_agent' => 'Mozilla/5.0',
                'ip_address' => '192.168.1.' . ($i + 1),
                'session_start' => $now->format('Y-m-d H:i:s'),
            ]);

            // 设置对话详情
            $conversation->setDialog([
                'conversation_id' => 'conv-' . ($i + 1),
                'participant_count' => 2,
                'language' => 'zh',
                'topics' => ['AI', '编程', '学习'],
            ]);

            // 设置时间字段
            $dayOffset = $i + 1;
            $conversation->setRemoteCreateTime($now->modify("-{$dayOffset} days"));
            $conversation->setRemoteUpdateTime($now->modify('-' . (int) ($dayOffset / 2) . ' hours'));
            $conversation->setLastActivityTime($now->modify('-' . ($i * 2) . ' hours'));
            $conversation->setLastSyncTime($now);

            $manager->persist($conversation);
            $this->addReference(self::CONVERSATION_REFERENCE_PREFIX . $i, $conversation);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            RAGFlowInstanceFixtures::class,
            ChatAssistantFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return [
            'rag-flow-api',
            'conversation',
        ];
    }
}
