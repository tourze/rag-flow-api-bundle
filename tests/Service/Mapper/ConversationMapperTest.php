<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service\Mapper;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Entity\Conversation;
use Tourze\RAGFlowApiBundle\Service\Mapper\ConversationMapper;

/**
 * @internal
 */
#[CoversClass(ConversationMapper::class)]
final class ConversationMapperTest extends TestCase
{
    private ConversationMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new ConversationMapper();
    }

    public function testMapApiDataToEntityWithCompleteData(): void
    {
        $conversation = new Conversation();
        $apiData = [
            'name' => 'Test Conversation',
            'dialog' => [
                [
                    'role' => 'user',
                    'content' => 'Hello, how are you?',
                    'timestamp' => '2024-01-15 10:00:00',
                ],
                [
                    'role' => 'assistant',
                    'content' => 'I am doing well, thank you!',
                    'timestamp' => '2024-01-15 10:00:05',
                ],
            ],
            'create_time' => 1640995200000,
            'update_time' => '2024-01-15 10:30:45',
        ];

        $this->mapper->mapApiDataToEntity($conversation, $apiData);

        $this->assertSame('Test Conversation', $conversation->getName());
        $this->assertSame([
            [
                'role' => 'user',
                'content' => 'Hello, how are you?',
                'timestamp' => '2024-01-15 10:00:00',
            ],
            [
                'role' => 'assistant',
                'content' => 'I am doing well, thank you!',
                'timestamp' => '2024-01-15 10:00:05',
            ],
        ], $conversation->getDialog());
        $this->assertInstanceOf(\DateTimeImmutable::class, $conversation->getRemoteCreateTime());
        $this->assertSame('2022-01-01', $conversation->getRemoteCreateTime()->format('Y-m-d'));
        $this->assertInstanceOf(\DateTimeImmutable::class, $conversation->getRemoteUpdateTime());
        $this->assertSame('2024-01-15', $conversation->getRemoteUpdateTime()->format('Y-m-d'));
    }

    public function testMapApiDataToEntityWithMinimalData(): void
    {
        $conversation = new Conversation();
        $apiData = [
            'name' => 'Minimal Conversation',
        ];

        $this->mapper->mapApiDataToEntity($conversation, $apiData);

        $this->assertSame('Minimal Conversation', $conversation->getName());
        $this->assertNull($conversation->getDialog());
        $this->assertNull($conversation->getRemoteCreateTime());
        $this->assertNull($conversation->getRemoteUpdateTime());
    }

    public function testMapApiDataToEntityPreservesDialogStructure(): void
    {
        $conversation = new Conversation();
        $apiData = [
            'name' => 'Complex Dialog',
            'dialog' => [
                [
                    'role' => 'user',
                    'content' => 'What is the weather?',
                    'metadata' => ['source' => 'mobile'],
                ],
                [
                    'role' => 'assistant',
                    'content' => 'The weather is sunny.',
                    'metadata' => ['confidence' => 0.95],
                ],
                [
                    'role' => 'user',
                    'content' => 'Thank you!',
                ],
            ],
        ];

        $this->mapper->mapApiDataToEntity($conversation, $apiData);

        $this->assertSame([
            [
                'role' => 'user',
                'content' => 'What is the weather?',
                'metadata' => ['source' => 'mobile'],
            ],
            [
                'role' => 'assistant',
                'content' => 'The weather is sunny.',
                'metadata' => ['confidence' => 0.95],
            ],
            [
                'role' => 'user',
                'content' => 'Thank you!',
            ],
        ], $conversation->getDialog());
    }

    public function testMapApiDataToEntityConvertsTimestampFromMilliseconds(): void
    {
        $conversation = new Conversation();
        $apiData = [
            'name' => 'Test Conversation',
            'create_time' => 1640995200000,
            'update_time' => 1642204800000,
        ];

        $this->mapper->mapApiDataToEntity($conversation, $apiData);

        $this->assertInstanceOf(\DateTimeImmutable::class, $conversation->getRemoteCreateTime());
        $this->assertSame('2022-01-01', $conversation->getRemoteCreateTime()->format('Y-m-d'));
        $this->assertInstanceOf(\DateTimeImmutable::class, $conversation->getRemoteUpdateTime());
        $this->assertSame('2022-01-15', $conversation->getRemoteUpdateTime()->format('Y-m-d'));
    }

    public function testMapApiDataToEntityConvertsTimestampFromString(): void
    {
        $conversation = new Conversation();
        $apiData = [
            'name' => 'Test Conversation',
            'create_time' => '2024-01-15 10:30:45',
            'update_time' => '2024-01-20 15:45:30',
        ];

        $this->mapper->mapApiDataToEntity($conversation, $apiData);

        $this->assertInstanceOf(\DateTimeImmutable::class, $conversation->getRemoteCreateTime());
        $this->assertSame('2024-01-15', $conversation->getRemoteCreateTime()->format('Y-m-d'));
        $this->assertInstanceOf(\DateTimeImmutable::class, $conversation->getRemoteUpdateTime());
        $this->assertSame('2024-01-20', $conversation->getRemoteUpdateTime()->format('Y-m-d'));
    }

    public function testMapApiDataToEntityHandlesInvalidTimestamp(): void
    {
        $conversation = new Conversation();
        $apiData = [
            'name' => 'Test Conversation',
            'create_time' => 'invalid-date',
            'update_time' => null,
        ];

        $this->mapper->mapApiDataToEntity($conversation, $apiData);

        $this->assertInstanceOf(\DateTimeImmutable::class, $conversation->getRemoteCreateTime());
        $this->assertSame('1970-01-01', $conversation->getRemoteCreateTime()->format('Y-m-d'));
        $this->assertNull($conversation->getRemoteUpdateTime());
    }

    public function testMapApiDataToEntityIgnoresInvalidFieldTypes(): void
    {
        $conversation = new Conversation();
        // 预先设置必需字段 title，避免未初始化错误
        $conversation->setName('Initial Name');

        $apiData = [
            'name' => 123,
            'dialog' => 'not-an-array',
        ];

        $this->mapper->mapApiDataToEntity($conversation, $apiData);

        // name 是无效类型，不会被映射，保持初始值
        $this->assertSame('Initial Name', $conversation->getName());
        $this->assertNull($conversation->getDialog());
    }

    public function testMapApiDataToEntityHandlesEmptyDialog(): void
    {
        $conversation = new Conversation();
        $apiData = [
            'name' => 'Empty Dialog',
            'dialog' => [],
        ];

        $this->mapper->mapApiDataToEntity($conversation, $apiData);

        $this->assertSame('Empty Dialog', $conversation->getName());
        $this->assertSame([], $conversation->getDialog());
    }
}
