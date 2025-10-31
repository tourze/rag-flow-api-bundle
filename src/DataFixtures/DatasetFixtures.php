<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Repository\RAGFlowInstanceRepository;

#[When(env: 'test')]
#[When(env: 'dev')]
class DatasetFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
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

        $datasets = [
            [
                'name' => '通用知识库',
                'description' => '包含各种通用知识的知识库',
                'remoteId' => 'dataset-1',
            ],
            [
                'name' => '技术文档库',
                'description' => '技术文档和编程相关的资料库',
                'remoteId' => 'dataset-2',
            ],
            [
                'name' => '产品手册',
                'description' => '产品使用手册和说明文档',
                'remoteId' => 'dataset-3',
            ],
        ];

        $now = new \DateTimeImmutable();

        foreach ($datasets as $i => $data) {
            $dataset = new Dataset();
            $dataset->setRemoteId($data['remoteId']);
            $dataset->setName($data['name']);
            $dataset->setDescription($data['description']);
            $dataset->setRagFlowInstance($instance);
            $dataset->setRemoteCreateTime($now->modify("-{$i} days"));
            $dataset->setRemoteUpdateTime($now->modify("-{$i} days"));
            $dataset->setLastSyncTime($now);

            $manager->persist($dataset);
            $this->addReference('dataset-' . ($i + 1), $dataset);
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
            'dataset',
        ];
    }
}
