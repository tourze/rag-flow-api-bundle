<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\RAGFlowApiBundle\DependencyInjection\RAGFlowApiExtension;

/**
 * @internal
 */
#[CoversClass(RAGFlowApiExtension::class)]
class RAGFlowApiExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    public function testExtensionLoadsServices(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $extension = new RAGFlowApiExtension();
        $extension->load([], $container);

        // 检查服务是否正确加载
        $this->assertTrue($container->hasDefinition('Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManagerInterface'));
        $this->assertTrue($container->hasDefinition('Tourze\RAGFlowApiBundle\Service\RAGFlowApiClientFactory'));
    }

    public function testExtensionLoadsDevServices(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'dev');

        $extension = new RAGFlowApiExtension();
        $extension->load([], $container);

        // 在开发环境中应该加载DataFixtures服务
        $this->assertTrue($container->hasDefinition('Tourze\RAGFlowApiBundle\DataFixtures\RAGFlowInstanceFixtures'));
    }

    public function testExtensionLoadsTestServices(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $extension = new RAGFlowApiExtension();
        $extension->load([], $container);

        // 在测试环境中应该加载DataFixtures服务
        $this->assertTrue($container->hasDefinition('Tourze\RAGFlowApiBundle\DataFixtures\RAGFlowInstanceFixtures'));
    }

    public function testExtensionDoesNotLoadFixturesInProd(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new RAGFlowApiExtension();
        $extension->load([], $container);

        // 在生产环境中不应该加载DataFixtures服务
        $this->assertFalse($container->hasDefinition('Tourze\RAGFlowApiBundle\DataFixtures\RAGFlowInstanceFixtures'));
    }

    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $extension = new RAGFlowApiExtension();
        $extension->load([], $container);

        // 验证Extension可以正常加载
        $this->assertTrue($container->hasDefinition('Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManagerInterface'));
    }
}
