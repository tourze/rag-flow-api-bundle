<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Fixtures;

use Tourze\RAGFlowApiBundle\Entity\VirtualChatAssistant;
use Tourze\RAGFlowApiBundle\Entity\VirtualChunk;
use Tourze\RAGFlowApiBundle\Entity\VirtualConversation;

/**
 * 虚拟实体测试数据提供者
 *
 * 为虚拟实体提供测试数据，因为它们不对应真实的数据库表
 */
class VirtualEntityFixtures
{
    /**
     * 获取测试用的 VirtualChunk 数据
     *
     * @return array<VirtualChunk>
     */
    public static function getVirtualChunks(): array
    {
        $chunks = [];

        for ($i = 1; $i <= 5; ++$i) {
            $chunk = new VirtualChunk();
            $chunk->setId('chunk_' . $i);
            $chunk->setDatasetId('dataset_' . ($i % 2 + 1));
            $chunk->setDocumentId('document_' . $i);
            $chunk->setTitle('测试文本块标题 ' . $i);
            $chunk->setContent('这是测试文本块 ' . $i . ' 的内容，包含一些示例文本用于测试目的。');
            $chunk->setKeywords('关键词' . $i . ',测试,示例');
            $chunk->setSimilarityScore(0.8 + ($i * 0.02));
            $chunk->setPosition($i * 10);
            $chunk->setLength(strlen($chunk->getContent()));
            $chunk->setStatus('active');
            $chunk->setLanguage('zh');
            $chunk->setCreateTime('2024-01-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . ' 10:00:00');
            $chunk->setUpdateTime('2024-01-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . ' 10:00:00');
            $chunk->setMetadata(['source' => 'test', 'version' => $i]);

            $chunks[] = $chunk;
        }

        return $chunks;
    }

    /**
     * 获取测试用的 VirtualConversation 数据
     *
     * @return array<VirtualConversation>
     */
    public static function getVirtualConversations(): array
    {
        $conversations = [];

        for ($i = 1; $i <= 5; ++$i) {
            $conversation = new VirtualConversation();
            $conversation->setId('conversation_' . $i);
            $conversation->setChatId('chat_' . ($i % 2 + 1));
            $conversation->setSessionId('session_' . $i);
            $conversation->setUserMessage('用户问题 ' . $i);
            $conversation->setAssistantMessage('助手回复 ' . $i);
            $conversation->setRole(0 === $i % 2 ? 'assistant' : 'user');
            $conversation->setMessageCount($i * 2);
            $conversation->setStatus('active');
            $conversation->setResponseTime(1.5 + ($i * 0.2));
            $conversation->setTokenCount(100 + ($i * 20));
            $conversation->setContext(['model' => 'gpt-3.5-turbo', 'temperature' => 0.7]);
            $conversation->setReferences([['id' => 'ref_' . $i, 'title' => '参考文档 ' . $i]]);
            $conversation->setCreateTime('2024-01-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . ' 14:00:00');
            $conversation->setUpdateTime('2024-01-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . ' 14:00:00');

            $conversations[] = $conversation;
        }

        return $conversations;
    }

    /**
     * 获取测试用的 VirtualChatAssistant 数据
     *
     * @return array<VirtualChatAssistant>
     */
    public static function getVirtualChatAssistants(): array
    {
        $assistants = [];

        for ($i = 1; $i <= 5; ++$i) {
            $assistant = new VirtualChatAssistant();
            $assistant->setId('assistant_' . $i);
            $assistant->setName('测试助手 ' . $i);
            $assistant->setDescription('这是测试聊天助手 ' . $i . ' 的描述');
            $assistant->setDatasetIds(['dataset_' . $i, 'dataset_' . ($i + 1)]);
            $assistant->setSystemPrompt('你是一个测试助手，编号为 ' . $i);
            $assistant->setModel('gpt-3.5-turbo');
            $assistant->setTemperature(0.7 + ($i * 0.05));
            $assistant->setMaxTokens(1000 + ($i * 100));
            $assistant->setTopP(0.9 + ($i * 0.01));
            $assistant->setTopK(40 + ($i * 2));
            $assistant->setLanguage('zh');
            $assistant->setIsActive(true);
            $assistant->setSessionCount($i * 10);
            $assistant->setMessageCount($i * 50);
            $assistant->setLastUsedAt('2024-01-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . ' 16:00:00');
            $assistant->setCreateTime('2024-01-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . ' 10:00:00');
            $assistant->setUpdateTime('2024-01-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . ' 10:00:00');

            $assistants[] = $assistant;
        }

        return $assistants;
    }
}
