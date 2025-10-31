<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowAgent;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Repository\RAGFlowInstanceRepository;

#[When(env: 'test')]
#[When(env: 'dev')]
class RAGFlowAgentFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public const AGENT_REFERENCE_PREFIX = 'agent-';
    public const AGENT_COUNT = 4;

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

        $agents = [
            [
                'title' => '智能客服助手',
                'description' => '专门处理客户咨询和售后问题的AI助手',
                'status' => 'published',
                'dsl' => [
                    'version' => '1.0',
                    'nodes' => [
                        ['id' => 'start', 'type' => 'start', 'position' => ['x' => 100, 'y' => 100]],
                        ['id' => 'classify', 'type' => 'classifier', 'position' => ['x' => 300, 'y' => 100]],
                        ['id' => 'response', 'type' => 'response', 'position' => ['x' => 500, 'y' => 100]],
                    ],
                    'edges' => [
                        ['source' => 'start', 'target' => 'classify'],
                        ['source' => 'classify', 'target' => 'response'],
                    ],
                ],
            ],
            [
                'title' => '文档分析专家',
                'description' => '用于分析和提取文档关键信息的智能体',
                'status' => 'published',
                'dsl' => [
                    'version' => '1.0',
                    'nodes' => [
                        ['id' => 'start', 'type' => 'start', 'position' => ['x' => 100, 'y' => 100]],
                        ['id' => 'extract', 'type' => 'extractor', 'position' => ['x' => 300, 'y' => 100]],
                        ['id' => 'summarize', 'type' => 'summarizer', 'position' => ['x' => 500, 'y' => 100]],
                        ['id' => 'output', 'type' => 'output', 'position' => ['x' => 700, 'y' => 100]],
                    ],
                    'edges' => [
                        ['source' => 'start', 'target' => 'extract'],
                        ['source' => 'extract', 'target' => 'summarize'],
                        ['source' => 'summarize', 'target' => 'output'],
                    ],
                ],
            ],
            [
                'title' => '代码审查助手',
                'description' => '帮助开发者进行代码审查和质量检查',
                'status' => 'draft',
                'dsl' => [
                    'version' => '1.0',
                    'nodes' => [
                        ['id' => 'start', 'type' => 'start', 'position' => ['x' => 100, 'y' => 100]],
                        ['id' => 'analyze', 'type' => 'analyzer', 'position' => ['x' => 300, 'y' => 100]],
                        ['id' => 'suggest', 'type' => 'suggester', 'position' => ['x' => 500, 'y' => 100]],
                    ],
                    'edges' => [
                        ['source' => 'start', 'target' => 'analyze'],
                        ['source' => 'analyze', 'target' => 'suggest'],
                    ],
                ],
            ],
            [
                'title' => '多语言翻译助手',
                'description' => '支持多种语言之间的准确翻译',
                'status' => 'published',
                'dsl' => [
                    'version' => '1.0',
                    'nodes' => [
                        ['id' => 'start', 'type' => 'start', 'position' => ['x' => 100, 'y' => 100]],
                        ['id' => 'detect', 'type' => 'language_detector', 'position' => ['x' => 300, 'y' => 100]],
                        ['id' => 'translate', 'type' => 'translator', 'position' => ['x' => 500, 'y' => 100]],
                        ['id' => 'review', 'type' => 'reviewer', 'position' => ['x' => 700, 'y' => 100]],
                    ],
                    'edges' => [
                        ['source' => 'start', 'target' => 'detect'],
                        ['source' => 'detect', 'target' => 'translate'],
                        ['source' => 'translate', 'target' => 'review'],
                    ],
                ],
            ],
        ];

        $now = new \DateTimeImmutable();

        for ($i = 0; $i < self::AGENT_COUNT; ++$i) {
            $data = $agents[$i];
            $agent = new RAGFlowAgent();

            $agent->setTitle($data['title']);
            $agent->setDescription($data['description']);
            $agent->setStatus($data['status']);
            $agent->setDsl($data['dsl']);
            $agent->setRemoteId('agent-' . ($i + 1));
            $agent->setRagFlowInstance($instance);

            // 设置时间字段
            $dayOffset = $i + 1;
            $agent->setRemoteCreateTime($now->modify("-{$dayOffset} days"));
            $agent->setRemoteUpdateTime($now->modify('-' . (int) ($dayOffset / 2) . ' days'));
            $agent->setLastSyncTime($now);

            // 仅草稿状态的智能体设置错误信息
            if ('draft' === $data['status']) {
                $agent->setSyncErrorMessage(null);
            }

            $manager->persist($agent);
            $this->addReference(self::AGENT_REFERENCE_PREFIX . $i, $agent);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            RAGFlowInstanceFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return [
            'rag-flow-api',
            'agent',
        ];
    }
}
