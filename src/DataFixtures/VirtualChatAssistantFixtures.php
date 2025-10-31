<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\RAGFlowApiBundle\Entity\VirtualChatAssistant;

#[When(env: 'test')]
#[When(env: 'dev')]
class VirtualChatAssistantFixtures extends Fixture implements FixtureGroupInterface
{
    public const VIRTUAL_ASSISTANT_REFERENCE_PREFIX = 'virtual-assistant-';
    public const VIRTUAL_ASSISTANT_COUNT = 3;

    public function load(ObjectManager $manager): void
    {
        $assistants = [
            [
                'id' => 'virtual-assistant-001',
                'name' => '虚拟通用助手',
                'description' => '通过API连接的虚拟AI助手',
                'datasetIds' => ['dataset-1', 'dataset-2'],
                'systemPrompt' => '你是一个有用的虚拟AI助手。',
                'model' => 'gpt-4',
                'temperature' => 0.7,
                'maxTokens' => 2000,
                'topP' => 0.9,
                'topK' => 40.0,
                'language' => 'zh',
                'isActive' => true,
                'sessionCount' => 125,
                'messageCount' => 847,
                'lastUsedAt' => '2024-01-15 10:30:00',
            ],
            [
                'id' => 'virtual-assistant-002',
                'name' => '虚拟代码助手',
                'description' => '专门处理编程相关问题的虚拟助手',
                'datasetIds' => ['dataset-2'],
                'systemPrompt' => '你是一个专业的代码助手。',
                'model' => 'deepseek-chat',
                'temperature' => 0.3,
                'maxTokens' => 4000,
                'topP' => 0.85,
                'topK' => 30.0,
                'language' => 'en',
                'isActive' => true,
                'sessionCount' => 89,
                'messageCount' => 523,
                'lastUsedAt' => '2024-01-16 14:20:00',
            ],
            [
                'id' => 'virtual-assistant-003',
                'name' => '虚拟翻译助手',
                'description' => '多语言翻译的虚拟助手',
                'datasetIds' => ['dataset-1', 'dataset-3'],
                'systemPrompt' => '你是一个专业的翻译助手。',
                'model' => 'claude-3-sonnet',
                'temperature' => 0.5,
                'maxTokens' => 3000,
                'topP' => 0.92,
                'topK' => 50.0,
                'language' => 'zh',
                'isActive' => false,
                'sessionCount' => 45,
                'messageCount' => 278,
                'lastUsedAt' => '2024-01-10 09:15:00',
            ],
        ];

        for ($i = 0; $i < self::VIRTUAL_ASSISTANT_COUNT; ++$i) {
            $data = $assistants[$i];
            $assistant = new VirtualChatAssistant();

            $assistant->setId($data['id']);
            $assistant->setName($data['name']);
            $assistant->setDescription($data['description']);
            $assistant->setDatasetIds($data['datasetIds']);
            $assistant->setSystemPrompt($data['systemPrompt']);
            $assistant->setModel($data['model']);
            $assistant->setTemperature($data['temperature']);
            $assistant->setMaxTokens($data['maxTokens']);
            $assistant->setTopP($data['topP']);
            $assistant->setTopK($data['topK']);
            $assistant->setLanguage($data['language']);
            $assistant->setIsActive($data['isActive']);
            $assistant->setSessionCount($data['sessionCount']);
            $assistant->setMessageCount($data['messageCount']);
            $assistant->setLastUsedAt($data['lastUsedAt']);

            // 注意：虚拟实体通常不需要持久化到数据库
            // 这里仅作为示例，实际使用中可能需要通过API获取数据
            // $manager->persist($assistant);
            $this->addReference(self::VIRTUAL_ASSISTANT_REFERENCE_PREFIX . $i, $assistant);
        }

        // 注意：虚拟实体不需要flush
        // $manager->flush();
    }

    public static function getGroups(): array
    {
        return [
            'rag-flow-api',
            'virtual-chat-assistant',
        ];
    }
}
