<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\RAGFlowApiBundle\Entity\ChatAssistant;

#[When(env: 'test')]
#[When(env: 'dev')]
class ChatAssistantFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public const CHAT_ASSISTANT_REFERENCE_PREFIX = 'chat-assistant-';
    public const CHAT_ASSISTANT_COUNT = 5;

    public function load(ObjectManager $manager): void
    {
        $assistants = [
            [
                'name' => '通用助手',
                'description' => '一个通用的AI助手，可以回答各种问题',
                'llmModel' => 'deepseek-chat',
                'language' => 'zh',
                'temperature' => 0.7,
                'enabled' => true,
            ],
            [
                'name' => '代码助手',
                'description' => '专门帮助编程的AI助手',
                'llmModel' => 'gpt-4',
                'language' => 'en',
                'temperature' => 0.3,
                'enabled' => true,
            ],
            [
                'name' => '翻译助手',
                'description' => '专业的翻译助手，支持多语言翻译',
                'llmModel' => 'claude-3-sonnet',
                'language' => 'zh',
                'temperature' => 0.5,
                'enabled' => true,
            ],
            [
                'name' => '数学助手',
                'description' => '专门解决数学问题的AI助手',
                'llmModel' => 'gpt-3.5-turbo',
                'language' => 'zh',
                'temperature' => 0.2,
                'enabled' => false,
            ],
            [
                'name' => '创意写作助手',
                'description' => '帮助进行创意写作的AI助手',
                'llmModel' => 'deepseek-chat',
                'language' => 'zh',
                'temperature' => 1.2,
                'enabled' => true,
            ],
        ];

        $now = new \DateTimeImmutable();
        $yesterday = $now->modify('-1 day');
        $lastWeek = $now->modify('-1 week');

        for ($i = 0; $i < self::CHAT_ASSISTANT_COUNT; ++$i) {
            $assistant = new ChatAssistant();

            $data = $assistants[$i];

            $assistant->setRemoteId('assistant-' . ($i + 1));
            $assistant->setName($data['name']);
            $assistant->setDescription($data['description']);
            $assistant->setLlmModel($data['llmModel']);
            $assistant->setLanguage($data['language']);
            $assistant->setTemperature($data['temperature']);
            $assistant->setEnabled($data['enabled']);

            // 设置一些可选字段
            $assistant->setSystemPrompt('你是一个有用的AI助手。');
            $assistant->setOpener('你好！我是你的AI助手，有什么可以帮助你的吗？');
            $assistant->setShowQuote(true);
            $assistant->setMaxTokens(2000);
            $assistant->setTopP(0.9);
            $assistant->setPresencePenalty(0.1);
            $assistant->setFrequencyPenalty(0.1);

            // 设置时间字段
            $assistant->setRemoteCreateTime(0 === $i % 2 ? $lastWeek : $yesterday);
            $assistant->setRemoteUpdateTime(0 === $i % 3 ? $yesterday : $now);
            $assistant->setLastSyncTime($now);

            // 设置数据集ID
            $assistant->setDatasetIds(['dataset-' . (($i % 3) + 1)]);

            $manager->persist($assistant);
            $this->addReference(self::CHAT_ASSISTANT_REFERENCE_PREFIX . $i, $assistant);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            DatasetFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return [
            'rag-flow-api',
            'chat-assistant',
        ];
    }
}
