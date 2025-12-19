<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowAgent;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Repository\RAGFlowAgentRepository;

/**
 * @internal
 */
#[CoversClass(RAGFlowAgentRepository::class)]
#[RunTestsInSeparateProcesses]
class RAGFlowAgentRepositoryTest extends AbstractRepositoryTestCase
{
    private RAGFlowAgentRepository $repository;

    protected function onSetUp(): void
    {
        // 初始化repository
        $this->repository = self::getService(RAGFlowAgentRepository::class);
    }

    protected function createNewEntity(): object
    {
        $uniqueSuffix = uniqid('', true);

        // 创建RAGFlow实例
        $ragFlowInstance = new RAGFlowInstance();
        $ragFlowInstance->setName('测试实例_' . $uniqueSuffix);
        $ragFlowInstance->setApiUrl('http://localhost:9380');
        $ragFlowInstance->setApiKey('test-key-' . $uniqueSuffix);
        self::getEntityManager()->persist($ragFlowInstance);
        self::getEntityManager()->flush();

        // 创建智能体
        $agent = new RAGFlowAgent();
        $agent->setTitle('测试智能体_' . $uniqueSuffix);
        $agent->setDescription('用于测试的智能体');
        $agent->setDsl(['type' => 'test', 'config' => []]);
        $agent->setRagFlowInstance($ragFlowInstance);
        $agent->setStatus('draft');

        return $agent;
    }

    protected function getRepository(): RAGFlowAgentRepository
    {
        if (!isset($this->repository)) {
            $this->repository = self::getService(RAGFlowAgentRepository::class);
        }

        return $this->repository;
    }

    public function testFindByInstance(): void
    {
        $uniqueSuffix = uniqid('', true);

        // 创建RAGFlow实例
        $ragFlowInstance = new RAGFlowInstance();
        $ragFlowInstance->setName('测试实例_' . $uniqueSuffix);
        $ragFlowInstance->setApiUrl('http://localhost:9380');
        $ragFlowInstance->setApiKey('test-key-' . $uniqueSuffix);
        self::getEntityManager()->persist($ragFlowInstance);

        // 创建另一个RAGFlow实例
        $otherInstance = new RAGFlowInstance();
        $otherInstance->setName('其他实例_' . $uniqueSuffix);
        $otherInstance->setApiUrl('http://localhost:9381');
        $otherInstance->setApiKey('other-key-' . $uniqueSuffix);
        self::getEntityManager()->persist($otherInstance);

        // 为第一个实例创建智能体
        for ($i = 1; $i <= 3; ++$i) {
            $agent = new RAGFlowAgent();
            $agent->setTitle("测试智能体{$i}");
            $agent->setDescription("测试智能体描述{$i}");
            $agent->setDsl(['type' => 'test', 'id' => $i]);
            $agent->setRagFlowInstance($ragFlowInstance);
            $agent->setStatus('active');
            self::getEntityManager()->persist($agent);
        }

        // 为第二个实例创建智能体
        $otherAgent = new RAGFlowAgent();
        $otherAgent->setTitle('其他智能体');
        $otherAgent->setDescription('其他智能体描述');
        $otherAgent->setDsl(['type' => 'other']);
        $otherAgent->setRagFlowInstance($otherInstance);
        $otherAgent->setStatus('active');
        self::getEntityManager()->persist($otherAgent);

        self::getEntityManager()->flush();

        // 测试查找第一个实例的智能体
        $agents = $this->repository->findByInstance($ragFlowInstance);
        $this->assertCount(3, $agents);
        foreach ($agents as $agent) {
            $this->assertEquals($ragFlowInstance->getId(), $agent->getRagFlowInstance()->getId());
        }

        // 测试查找第二个实例的智能体
        $otherAgents = $this->repository->findByInstance($otherInstance);
        $this->assertCount(1, $otherAgents);
        $this->assertEquals('其他智能体', $otherAgents[0]->getTitle());
    }

    public function testFindNeedingSync(): void
    {
        $uniqueSuffix = uniqid('', true);

        // 创建RAGFlow实例
        $ragFlowInstance = new RAGFlowInstance();
        $ragFlowInstance->setName('测试实例_' . $uniqueSuffix);
        $ragFlowInstance->setApiUrl('http://localhost:9380');
        $ragFlowInstance->setApiKey('test-key-' . $uniqueSuffix);
        self::getEntityManager()->persist($ragFlowInstance);

        // 创建需要同步的智能体（没有remoteId）
        $agentWithoutRemoteId = new RAGFlowAgent();
        $agentWithoutRemoteId->setTitle('无远程ID智能体');
        $agentWithoutRemoteId->setDsl(['type' => 'test']);
        $agentWithoutRemoteId->setRagFlowInstance($ragFlowInstance);
        $agentWithoutRemoteId->setStatus('active');
        self::getEntityManager()->persist($agentWithoutRemoteId);

        // 创建需要同步的智能体（同步失败状态）
        $syncFailedAgent = new RAGFlowAgent();
        $syncFailedAgent->setTitle('同步失败智能体');
        $syncFailedAgent->setDsl(['type' => 'test']);
        $syncFailedAgent->setRemoteId('remote-id-123');
        $syncFailedAgent->setRagFlowInstance($ragFlowInstance);
        $syncFailedAgent->setStatus('sync_failed');
        self::getEntityManager()->persist($syncFailedAgent);

        // 创建不需要同步的智能体
        $normalAgent = new RAGFlowAgent();
        $normalAgent->setTitle('正常智能体');
        $normalAgent->setDsl(['type' => 'test']);
        $normalAgent->setRemoteId('remote-id-456');
        $normalAgent->setRagFlowInstance($ragFlowInstance);
        $normalAgent->setStatus('active');
        self::getEntityManager()->persist($normalAgent);

        self::getEntityManager()->flush();

        // 测试查找需要同步的智能体
        $agentsNeedingSync = $this->repository->findNeedingSync();
        $this->assertGreaterThanOrEqual(2, count($agentsNeedingSync));

        $foundTitles = array_map(fn ($agent) => $agent->getTitle(), $agentsNeedingSync);
        $this->assertContains('无远程ID智能体', $foundTitles);
        $this->assertContains('同步失败智能体', $foundTitles);
        $this->assertNotContains('正常智能体', $foundTitles);
    }

    public function testFindByRemoteId(): void
    {
        $uniqueSuffix = uniqid('', true);

        // 创建RAGFlow实例
        $ragFlowInstance = new RAGFlowInstance();
        $ragFlowInstance->setName('测试实例_' . $uniqueSuffix);
        $ragFlowInstance->setApiUrl('http://localhost:9380');
        $ragFlowInstance->setApiKey('test-key-' . $uniqueSuffix);
        self::getEntityManager()->persist($ragFlowInstance);

        // 创建另一个RAGFlow实例
        $otherInstance = new RAGFlowInstance();
        $otherInstance->setName('其他实例_' . $uniqueSuffix);
        $otherInstance->setApiUrl('http://localhost:9381');
        $otherInstance->setApiKey('other-key-' . $uniqueSuffix);
        self::getEntityManager()->persist($otherInstance);

        $remoteId = 'test-remote-id-123';

        // 为第一个实例创建智能体
        $agent = new RAGFlowAgent();
        $agent->setTitle('测试智能体');
        $agent->setDsl(['type' => 'test']);
        $agent->setRemoteId($remoteId);
        $agent->setRagFlowInstance($ragFlowInstance);
        $agent->setStatus('active');
        self::getEntityManager()->persist($agent);

        // 为第二个实例创建相同remoteId的智能体
        $otherAgent = new RAGFlowAgent();
        $otherAgent->setTitle('其他智能体');
        $otherAgent->setDsl(['type' => 'other']);
        $otherAgent->setRemoteId($remoteId);
        $otherAgent->setRagFlowInstance($otherInstance);
        $otherAgent->setStatus('active');
        self::getEntityManager()->persist($otherAgent);

        self::getEntityManager()->flush();

        // 测试查找第一个实例的智能体
        $foundAgent = $this->repository->findByRemoteId($remoteId, $ragFlowInstance);
        $this->assertInstanceOf(RAGFlowAgent::class, $foundAgent);
        $this->assertEquals('测试智能体', $foundAgent->getTitle());
        $this->assertEquals($remoteId, $foundAgent->getRemoteId());

        // 测试查找第二个实例的智能体
        $foundOtherAgent = $this->repository->findByRemoteId($remoteId, $otherInstance);
        $this->assertInstanceOf(RAGFlowAgent::class, $foundOtherAgent);
        $this->assertEquals('其他智能体', $foundOtherAgent->getTitle());

        // 测试查找不存在的智能体
        $notFound = $this->repository->findByRemoteId('non-existent-id', $ragFlowInstance);
        $this->assertNull($notFound);
    }

    public function testFindByStatus(): void
    {
        $uniqueSuffix = uniqid('', true);

        // 创建RAGFlow实例
        $ragFlowInstance = new RAGFlowInstance();
        $ragFlowInstance->setName('测试实例_' . $uniqueSuffix);
        $ragFlowInstance->setApiUrl('http://localhost:9380');
        $ragFlowInstance->setApiKey('test-key-' . $uniqueSuffix);
        self::getEntityManager()->persist($ragFlowInstance);

        $statuses = ['draft', 'active', 'inactive', 'sync_failed'];
        $statusCounts = [];

        foreach ($statuses as $status) {
            $statusCounts[$status] = 0;
            for ($i = 1; $i <= 2; ++$i) {
                $agent = new RAGFlowAgent();
                $agent->setTitle("{$status}智能体{$i}");
                $agent->setDsl(['type' => 'test', 'status' => $status]);
                $agent->setRagFlowInstance($ragFlowInstance);
                $agent->setStatus($status);
                self::getEntityManager()->persist($agent);
                ++$statusCounts[$status];
            }
        }

        self::getEntityManager()->flush();

        // 测试查找每种状态的智能体
        foreach ($statuses as $status) {
            $agents = $this->repository->findByStatus($status);
            $this->assertGreaterThanOrEqual($statusCounts[$status], count($agents));

            foreach ($agents as $agent) {
                $this->assertEquals($status, $agent->getStatus());
            }
        }
    }
}
