<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Repository\RAGFlowInstanceRepository;

/**
 * @internal
 */
#[CoversClass(RAGFlowInstanceRepository::class)]
#[RunTestsInSeparateProcesses]
class RAGFlowInstanceRepositoryTest extends AbstractRepositoryTestCase
{
    private RAGFlowInstanceRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = static::getService(RAGFlowInstanceRepository::class);
    }

    protected function createNewEntity(): RAGFlowInstance
    {
        $instance = new RAGFlowInstance();
        $instance->setName('test-entity-' . uniqid());
        $instance->setApiUrl('https://test-entity.com');
        $instance->setApiKey('test_key');
        $instance->setEnabled(true);

        return $instance;
    }

    protected function getRepository(): RAGFlowInstanceRepository
    {
        return $this->repository;
    }

    public function testFindEnabled(): void
    {
        // 清理现有数据以确保测试独立性
        self::getEntityManager()->createQuery('DELETE FROM ' . RAGFlowInstance::class)->execute();

        // 创建测试数据
        $enabledInstance = new RAGFlowInstance();
        $enabledInstance->setName('enabled-instance-test-' . uniqid());
        $enabledInstance->setApiUrl('https://enabled.test.com');
        $enabledInstance->setApiKey('enabled_key');
        $enabledInstance->setEnabled(true);

        $disabledInstance = new RAGFlowInstance();
        $disabledInstance->setName('disabled-instance-test-' . uniqid());
        $disabledInstance->setApiUrl('https://disabled.test.com');
        $disabledInstance->setApiKey('disabled_key');
        $disabledInstance->setEnabled(false);

        self::getEntityManager()->persist($enabledInstance);
        self::getEntityManager()->persist($disabledInstance);
        self::getEntityManager()->flush();

        // 测试查询
        $results = $this->repository->findEnabled();

        // 验证结果 - 应该只有一个启用的实例
        $this->assertCount(1, $results);
        $this->assertTrue($results[0]->isEnabled());
        $this->assertStringContainsString('enabled-instance-test-', $results[0]->getName());
    }

    public function testFindHealthy(): void
    {
        // 清理现有数据以确保测试独立性
        self::getEntityManager()->createQuery('DELETE FROM ' . RAGFlowInstance::class)->execute();

        $healthyInstance = new RAGFlowInstance();
        $healthyInstance->setName('healthy-instance-test-' . uniqid());
        $healthyInstance->setApiUrl('https://healthy.test.com');
        $healthyInstance->setApiKey('healthy_key');
        $healthyInstance->setEnabled(true);
        $healthyInstance->setHealthy(true);

        self::getEntityManager()->persist($healthyInstance);
        self::getEntityManager()->flush();

        $results = $this->repository->findHealthy();

        $this->assertCount(1, $results);
        $this->assertTrue($results[0]->isHealthy());
        $this->assertTrue($results[0]->isEnabled());
    }

    public function testFindNeedHealthCheck(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('need-check-instance');
        $instance->setApiUrl('https://check.test.com');
        $instance->setApiKey('check_key');
        $instance->setEnabled(true);

        self::getEntityManager()->persist($instance);
        self::getEntityManager()->flush();

        $results = $this->repository->findNeedHealthCheck(30);

        $this->assertGreaterThanOrEqual(1, count($results));
    }

    public function testSave(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('save-test-instance');
        $instance->setApiUrl('https://save.test.com');
        $instance->setApiKey('save_key');

        $this->repository->save($instance, true);

        $saved = $this->repository->findByName('save-test-instance');
        $this->assertNotNull($saved);
        $this->assertEquals('save-test-instance', $saved->getName());
    }

    public function testRemove(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('remove-test-instance');
        $instance->setApiUrl('https://remove.test.com');
        $instance->setApiKey('remove_key');

        $this->repository->save($instance, true);

        $saved = $this->repository->findByName('remove-test-instance');
        $this->assertNotNull($saved);

        $this->repository->remove($saved, true);

        $removed = $this->repository->findByName('remove-test-instance');
        $this->assertNull($removed);
    }

    public function testFindByNameExists(): void
    {
        // 清理现有数据以确保测试独立性
        self::getEntityManager()->createQuery('DELETE FROM ' . RAGFlowInstance::class)->execute();

        // 创建测试数据
        $uniqueName = 'test-instance-' . uniqid();
        $instance = new RAGFlowInstance();
        $instance->setName($uniqueName);
        $instance->setApiUrl('https://test.com');
        $instance->setApiKey('test_key');

        self::getEntityManager()->persist($instance);
        self::getEntityManager()->flush();

        // 测试查询
        $result = $this->repository->findByName($uniqueName);

        // 验证结果
        $this->assertInstanceOf(RAGFlowInstance::class, $result);
        $this->assertEquals($uniqueName, $result->getName());
    }

    public function testFindByNameNotExists(): void
    {
        $result = $this->repository->findByName('non-existent');
        $this->assertNull($result);
    }
}
