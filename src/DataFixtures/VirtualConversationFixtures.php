<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\RAGFlowApiBundle\Entity\VirtualConversation;

#[When(env: 'test')]
#[When(env: 'dev')]
class VirtualConversationFixtures extends Fixture implements FixtureGroupInterface
{
    public const VIRTUAL_CONVERSATION_REFERENCE_PREFIX = 'virtual-conversation-';
    public const VIRTUAL_CONVERSATION_COUNT = 4;

    public function load(ObjectManager $manager): void
    {
        $conversations = [
            [
                'id' => 'virtual-conv-001',
                'chatId' => 'chat-001',
                'sessionId' => 'session-001',
                'userMessage' => '如何使用Python进行数据分析？',
                'assistantMessage' => '使用Python进行数据分析通常需要以下几个步骤：\n1. 安装必要的库（如pandas、numpy）\n2. 导入数据\n3. 数据清洗和预处理\n4. 数据分析和可视化',
                'role' => 'assistant',
                'messageCount' => 8,
                'status' => 'completed',
                'responseTime' => 2.5,
                'tokenCount' => 245,
                'context' => [
                    'user_id' => 'user-001',
                    'session_start' => '2024-01-15 10:00:00',
                    'language' => 'zh',
                ],
                'references' => [
                    ['doc_id' => 'doc-1', 'chunk_id' => 'chunk-1', 'score' => 0.95],
                    ['doc_id' => 'doc-2', 'chunk_id' => 'chunk-3', 'score' => 0.88],
                ],
            ],
            [
                'id' => 'virtual-conv-002',
                'chatId' => 'chat-002',
                'sessionId' => 'session-002',
                'userMessage' => '请解释一下RESTful API的设计原则',
                'assistantMessage' => 'RESTful API的主要设计原则包括：\n1. 客户端-服务器架构\n2. 无状态通信\n3. 可缓存性\n4. 统一接口\n5. 分层系统\n6. 按需代码（可选）',
                'role' => 'assistant',
                'messageCount' => 5,
                'status' => 'completed',
                'responseTime' => 1.8,
                'tokenCount' => 156,
                'context' => [
                    'user_id' => 'user-002',
                    'session_start' => '2024-01-16 14:30:00',
                    'language' => 'zh',
                ],
                'references' => [
                    ['doc_id' => 'doc-3', 'chunk_id' => 'chunk-5', 'score' => 0.92],
                ],
            ],
            [
                'id' => 'virtual-conv-003',
                'chatId' => 'chat-003',
                'sessionId' => 'session-003',
                'userMessage' => '什么是微服务架构？',
                'assistantMessage' => '微服务架构是一种将应用程序构建为一组小型服务的方法，每个服务都在自己的进程中运行，并使用轻量级机制（通常是HTTP API）进行通信。',
                'role' => 'assistant',
                'messageCount' => 12,
                'status' => 'active',
                'responseTime' => 3.2,
                'tokenCount' => 312,
                'context' => [
                    'user_id' => 'user-003',
                    'session_start' => '2024-01-17 09:15:00',
                    'language' => 'zh',
                ],
                'references' => [
                    ['doc_id' => 'doc-4', 'chunk_id' => 'chunk-7', 'score' => 0.89],
                    ['doc_id' => 'doc-5', 'chunk_id' => 'chunk-9', 'score' => 0.87],
                ],
            ],
            [
                'id' => 'virtual-conv-004',
                'chatId' => 'chat-004',
                'sessionId' => 'session-004',
                'userMessage' => '如何优化数据库查询性能？',
                'assistantMessage' => '优化数据库查询性能的常见方法包括：\n1. 使用适当的索引\n2. 避免N+1查询问题\n3. 使用连接池\n4. 查询结果缓存\n5. 分页加载数据\n6. 避免SELECT *',
                'role' => 'assistant',
                'messageCount' => 6,
                'status' => 'completed',
                'responseTime' => 2.1,
                'tokenCount' => 198,
                'context' => [
                    'user_id' => 'user-004',
                    'session_start' => '2024-01-18 16:45:00',
                    'language' => 'zh',
                ],
                'references' => [
                    ['doc_id' => 'doc-2', 'chunk_id' => 'chunk-4', 'score' => 0.91],
                ],
            ],
        ];

        for ($i = 0; $i < self::VIRTUAL_CONVERSATION_COUNT; ++$i) {
            $data = $conversations[$i];
            $conversation = new VirtualConversation();

            $conversation->setId($data['id']);
            $conversation->setChatId($data['chatId']);
            $conversation->setSessionId($data['sessionId']);
            $conversation->setUserMessage($data['userMessage']);
            $conversation->setAssistantMessage($data['assistantMessage']);
            $conversation->setRole($data['role']);
            $conversation->setMessageCount($data['messageCount']);
            $conversation->setStatus($data['status']);
            $conversation->setResponseTime($data['responseTime']);
            $conversation->setTokenCount($data['tokenCount']);
            $conversation->setContext($data['context']);
            $conversation->setReferences($data['references']);

            // 注意：虚拟实体通常不需要持久化到数据库
            // 这里仅作为示例，实际使用中可能需要通过API获取数据
            // $manager->persist($conversation);
            $this->addReference(self::VIRTUAL_CONVERSATION_REFERENCE_PREFIX . $i, $conversation);
        }

        // 注意：虚拟实体不需要flush
        // $manager->flush();
    }

    public static function getGroups(): array
    {
        return [
            'rag-flow-api',
            'virtual-conversation',
        ];
    }
}
