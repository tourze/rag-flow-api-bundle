<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;

#[When(env: 'test')]
#[When(env: 'dev')]
class RAGFlowInstanceFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        // 创建主测试实例
        $instance = new RAGFlowInstance();
        $instance->setName('user1');  // 与LlmModelFixtures期望的名称一致
        $instance->setApiUrl('https://ragflow-test.mixpwr.com');
        $instance->setApiKey('ragflow-hlNzQ5ODM0OTg1YTExZjBhZGI2NmU2Nm');
        $instance->setDescription('Default RAGFlow instance for development');
        $instance->setTimeout(30);
        $instance->setEnabled(true);
        $instance->setHealthy(true);

        $manager->persist($instance);

        // 也创建test-instance别名，以保持向后兼容
        $testInstance = new RAGFlowInstance();
        $testInstance->setName('test-instance');
        $testInstance->setApiUrl('https://ragflow-test.mixpwr.com');
        $testInstance->setApiKey('ragflow-hlNzQ5ODM0OTg1YTExZjBhZGI2NmU2Nm');
        $testInstance->setDescription('Test instance alias');
        $testInstance->setTimeout(30);
        $testInstance->setEnabled(true);
        $testInstance->setHealthy(true);

        $manager->persist($testInstance);
        $manager->flush();
    }

    public static function getGroups(): array
    {
        return [
            'rag-flow-api',
            'rag-flow-instance',
        ];
    }
}
