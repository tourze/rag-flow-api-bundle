<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowAgent;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;

/**
 * RAGFlow智能体实体测试
 *
 * @internal
 */
#[CoversClass(RAGFlowAgent::class)]
class RAGFlowAgentTest extends AbstractEntityTestCase
{
    protected function createEntity(): RAGFlowAgent
    {
        $agent = new RAGFlowAgent();
        $agent->setTitle('test-agent');

        return $agent;
    }

    public function testGetSetTitle(): void
    {
        $title = '测试智能体';
        $agent = $this->createEntity();
        $agent->setTitle($title);
        self::assertSame($title, $agent->getTitle());
    }

    public function testGetSetDescription(): void
    {
        $description = '这是一个测试智能体';
        $agent = $this->createEntity();
        $agent->setDescription($description);
        self::assertSame($description, $agent->getDescription());

        $agent->setDescription(null);
        self::assertNull($agent->getDescription());
    }

    public function testGetSetDsl(): void
    {
        $dsl = [
            'canvas' => [
                'nodes' => [],
                'edges' => [],
            ],
            'config' => [
                'model' => 'gpt-3.5-turbo',
            ],
        ];

        $agent = $this->createEntity();
        $agent->setDsl($dsl);
        self::assertSame($dsl, $agent->getDsl());
    }

    public function testGetSetRemoteId(): void
    {
        $remoteId = 'remote-agent-123';
        $agent = $this->createEntity();
        $agent->setRemoteId($remoteId);
        self::assertSame($remoteId, $agent->getRemoteId());

        $agent->setRemoteId(null);
        self::assertNull($agent->getRemoteId());
    }

    public function testGetSetRagFlowInstance(): void
    {
        $agent = $this->createEntity();
        $instance = new RAGFlowInstance();
        $instance->setName('测试实例');
        $agent->setRagFlowInstance($instance);

        self::assertSame($instance, $agent->getRagFlowInstance());
    }

    public function testGetSetStatus(): void
    {
        $status = 'published';
        $agent = $this->createEntity();
        $agent->setStatus($status);
        self::assertSame($status, $agent->getStatus());
    }

    public function testDefaultStatus(): void
    {
        $agent = $this->createEntity();
        self::assertSame('draft', $agent->getStatus());
    }

    public function testGetSetRemoteCreateTime(): void
    {
        $time = new \DateTimeImmutable('2024-01-01 12:00:00');
        $agent = $this->createEntity();
        $agent->setRemoteCreateTime($time);
        self::assertSame($time, $agent->getRemoteCreateTime());

        $agent->setRemoteCreateTime(null);
        self::assertNull($agent->getRemoteCreateTime());
    }

    public function testGetSetRemoteUpdateTime(): void
    {
        $time = new \DateTimeImmutable('2024-01-01 12:00:00');
        $agent = $this->createEntity();
        $agent->setRemoteUpdateTime($time);
        self::assertSame($time, $agent->getRemoteUpdateTime());

        $agent->setRemoteUpdateTime(null);
        self::assertNull($agent->getRemoteUpdateTime());
    }

    public function testGetSetLastSyncTime(): void
    {
        $time = new \DateTimeImmutable('2024-01-01 12:00:00');
        $agent = $this->createEntity();
        $agent->setLastSyncTime($time);
        self::assertSame($time, $agent->getLastSyncTime());

        $agent->setLastSyncTime(null);
        self::assertNull($agent->getLastSyncTime());
    }

    public function testGetSetSyncErrorMessage(): void
    {
        $message = 'Sync failed: Connection timeout';
        $agent = $this->createEntity();
        $agent->setSyncErrorMessage($message);
        self::assertSame($message, $agent->getSyncErrorMessage());

        $agent->setSyncErrorMessage(null);
        self::assertNull($agent->getSyncErrorMessage());
    }

    public function testToString(): void
    {
        $title = '测试智能体';
        $agent = $this->createEntity();
        $agent->setTitle($title);
        self::assertSame($title, (string) $agent);
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'title' => ['title', 'Test Agent'];
        yield 'description' => ['description', 'Test description'];
        yield 'remoteId' => ['remoteId', 'remote-agent-123'];
        yield 'status' => ['status', 'published'];
        yield 'syncErrorMessage' => ['syncErrorMessage', 'Test error'];
    }
}
