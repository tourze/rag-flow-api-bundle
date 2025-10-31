<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\PHPUnitSymfonyKernelTest\DoctrineTrait;
use Tourze\PHPUnitSymfonyKernelTest\ServiceLocatorTrait;
use Tourze\RAGFlowApiBundle\Entity\VirtualConversation;

/**
 * @internal
 */
#[CoversClass(VirtualConversation::class)]
class VirtualConversationTest extends AbstractEntityTestCase
{
    use DoctrineTrait;
    use ServiceLocatorTrait;

    protected function createEntity(): VirtualConversation
    {
        $conversation = new VirtualConversation();
        $conversation->setId('test-conv-id');
        $conversation->setSessionId('test-session');

        return $conversation;
    }

    public function testCreateVirtualConversation(): void
    {
        $conversation = new VirtualConversation();
        $conversation->setId('conv-123');
        $conversation->setChatId('chat-456');
        $conversation->setSessionId('session-789');
        $conversation->setUserMessage('你好');
        $conversation->setAssistantMessage('你好！有什么可以帮助你的吗？');
        $conversation->setRole('assistant');

        $this->assertEquals('conv-123', $conversation->getId());
        $this->assertEquals('chat-456', $conversation->getChatId());
        $this->assertEquals('session-789', $conversation->getSessionId());
        $this->assertEquals('你好', $conversation->getUserMessage());
        $this->assertEquals('你好！有什么可以帮助你的吗？', $conversation->getAssistantMessage());
        $this->assertEquals('assistant', $conversation->getRole());
    }

    public function testIdGetterAndSetter(): void
    {
        $conversation = new VirtualConversation();

        $this->assertNull($conversation->getId());

        $conversation->setId('conv-id-123');
        $this->assertEquals('conv-id-123', $conversation->getId());
    }

    public function testChatIdGetterAndSetter(): void
    {
        $conversation = new VirtualConversation();

        $this->assertNull($conversation->getChatId());

        $conversation->setChatId('chat-123');
        $this->assertEquals('chat-123', $conversation->getChatId());
    }

    public function testSessionIdGetterAndSetter(): void
    {
        $conversation = new VirtualConversation();

        $this->assertNull($conversation->getSessionId());

        $conversation->setSessionId('session-456');
        $this->assertEquals('session-456', $conversation->getSessionId());
    }

    public function testUserMessageGetterAndSetter(): void
    {
        $conversation = new VirtualConversation();

        $this->assertNull($conversation->getUserMessage());

        $userMessage = 'What is the weather today?';
        $conversation->setUserMessage($userMessage);
        $this->assertEquals($userMessage, $conversation->getUserMessage());
    }

    public function testAssistantMessageGetterAndSetter(): void
    {
        $conversation = new VirtualConversation();

        $this->assertNull($conversation->getAssistantMessage());

        $assistantMessage = 'The weather is sunny today.';
        $conversation->setAssistantMessage($assistantMessage);
        $this->assertEquals($assistantMessage, $conversation->getAssistantMessage());
    }

    public function testRoleGetterAndSetter(): void
    {
        $conversation = new VirtualConversation();

        $this->assertNull($conversation->getRole());

        $conversation->setRole('user');
        $this->assertEquals('user', $conversation->getRole());
    }

    public function testMessageCountGetterAndSetter(): void
    {
        $conversation = new VirtualConversation();

        $this->assertNull($conversation->getMessageCount());

        $conversation->setMessageCount(5);
        $this->assertEquals(5, $conversation->getMessageCount());
    }

    public function testStatusGetterAndSetter(): void
    {
        $conversation = new VirtualConversation();

        $this->assertNull($conversation->getStatus());

        $conversation->setStatus('completed');
        $this->assertEquals('completed', $conversation->getStatus());
    }

    public function testResponseTimeGetterAndSetter(): void
    {
        $conversation = new VirtualConversation();

        $this->assertNull($conversation->getResponseTime());

        $conversation->setResponseTime(1.25);
        $this->assertEquals(1.25, $conversation->getResponseTime());
    }

    public function testTokenCountGetterAndSetter(): void
    {
        $conversation = new VirtualConversation();

        $this->assertNull($conversation->getTokenCount());

        $conversation->setTokenCount(150);
        $this->assertEquals(150, $conversation->getTokenCount());
    }

    public function testContextGetterAndSetter(): void
    {
        $conversation = new VirtualConversation();

        $this->assertNull($conversation->getContext());

        $context = [
            'user_id' => 'user-123',
            'preferences' => ['language' => 'zh-CN'],
        ];
        $conversation->setContext($context);
        $this->assertEquals($context, $conversation->getContext());

        $conversation->setContext(null);
        $this->assertNull($conversation->getContext());
    }

    public function testReferencesGetterAndSetter(): void
    {
        $conversation = new VirtualConversation();

        $this->assertNull($conversation->getReferences());

        $references = [
            ['source' => 'doc1.pdf', 'page' => 5],
            ['source' => 'doc2.pdf', 'page' => 10],
        ];
        $conversation->setReferences($references);
        $this->assertEquals($references, $conversation->getReferences());

        $conversation->setReferences(null);
        $this->assertNull($conversation->getReferences());
    }

    public function testToStringWithId(): void
    {
        $conversation = new VirtualConversation();
        $conversation->setId('conv-789');

        $this->assertEquals('conv-789', (string) $conversation);
    }

    public function testToStringWithoutIdButWithSessionId(): void
    {
        $conversation = new VirtualConversation();
        $conversation->setSessionId('session-456');

        $this->assertEquals('session-456', (string) $conversation);
    }

    public function testToStringWithoutIdAndSessionId(): void
    {
        $conversation = new VirtualConversation();

        $this->assertEquals('(new)', (string) $conversation);
    }

    public function testNullableProperties(): void
    {
        $conversation = new VirtualConversation();

        // 所有属性初始值都应为 null
        $this->assertNull($conversation->getId());
        $this->assertNull($conversation->getChatId());
        $this->assertNull($conversation->getSessionId());
        $this->assertNull($conversation->getUserMessage());
        $this->assertNull($conversation->getAssistantMessage());
        $this->assertNull($conversation->getRole());
        $this->assertNull($conversation->getMessageCount());
        $this->assertNull($conversation->getStatus());
        $this->assertNull($conversation->getResponseTime());
        $this->assertNull($conversation->getTokenCount());
        $this->assertNull($conversation->getContext());
        $this->assertNull($conversation->getReferences());
    }

    /**
     * @param mixed $value
     */
    #[DataProvider('propertiesProvider')]
    public function testPropertyGettersAndSetters(string $property, $value): void
    {
        $conversation = new VirtualConversation();

        // 根据属性名直接调用对应的 getter 和 setter
        match ($property) {
            'id' => $this->assertPropertyGetterSetter($conversation, $value, fn ($e, $v) => $e->setId($v), fn ($e) => $e->getId()),
            'chatId' => $this->assertPropertyGetterSetter($conversation, $value, fn ($e, $v) => $e->setChatId($v), fn ($e) => $e->getChatId()),
            'sessionId' => $this->assertPropertyGetterSetter($conversation, $value, fn ($e, $v) => $e->setSessionId($v), fn ($e) => $e->getSessionId()),
            'userMessage' => $this->assertPropertyGetterSetter($conversation, $value, fn ($e, $v) => $e->setUserMessage($v), fn ($e) => $e->getUserMessage()),
            'assistantMessage' => $this->assertPropertyGetterSetter($conversation, $value, fn ($e, $v) => $e->setAssistantMessage($v), fn ($e) => $e->getAssistantMessage()),
            'role' => $this->assertPropertyGetterSetter($conversation, $value, fn ($e, $v) => $e->setRole($v), fn ($e) => $e->getRole()),
            'messageCount' => $this->assertPropertyGetterSetter($conversation, $value, fn ($e, $v) => $e->setMessageCount($v), fn ($e) => $e->getMessageCount()),
            'status' => $this->assertPropertyGetterSetter($conversation, $value, fn ($e, $v) => $e->setStatus($v), fn ($e) => $e->getStatus()),
            'responseTime' => $this->assertPropertyGetterSetter($conversation, $value, fn ($e, $v) => $e->setResponseTime($v), fn ($e) => $e->getResponseTime()),
            'tokenCount' => $this->assertPropertyGetterSetter($conversation, $value, fn ($e, $v) => $e->setTokenCount($v), fn ($e) => $e->getTokenCount()),
            'context' => $this->assertPropertyGetterSetter($conversation, $value, fn ($e, $v) => $e->setContext($v), fn ($e) => $e->getContext()),
            'references' => $this->assertPropertyGetterSetter($conversation, $value, fn ($e, $v) => $e->setReferences($v), fn ($e) => $e->getReferences()),
            default => self::fail("Unknown property: {$property}"),
        };
    }

    /**
     * @param callable(VirtualConversation, mixed): void $setter
     * @param callable(VirtualConversation): mixed       $getter
     * @param mixed                                      $value
     */
    private function assertPropertyGetterSetter(VirtualConversation $entity, $value, callable $setter, callable $getter): void
    {
        $setter($entity, $value);
        $this->assertEquals($value, $getter($entity));
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'id' => ['id', 'conv-123'];
        yield 'chatId' => ['chatId', 'chat-456'];
        yield 'sessionId' => ['sessionId', 'session-789'];
        yield 'userMessage' => ['userMessage', 'Hello, how are you?'];
        yield 'assistantMessage' => ['assistantMessage', 'I am fine, thank you!'];
        yield 'role' => ['role', 'assistant'];
        yield 'messageCount' => ['messageCount', 10];
        yield 'status' => ['status', 'active'];
        yield 'responseTime' => ['responseTime', 2.5];
        yield 'tokenCount' => ['tokenCount', 200];
        yield 'context' => ['context', ['user' => 'test', 'session' => 'active']];
        yield 'references' => ['references', [['doc' => 'test.pdf', 'page' => 1]]];
    }
}
