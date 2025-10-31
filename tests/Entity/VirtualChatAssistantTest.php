<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\PHPUnitSymfonyKernelTest\DoctrineTrait;
use Tourze\PHPUnitSymfonyKernelTest\ServiceLocatorTrait;
use Tourze\RAGFlowApiBundle\Entity\VirtualChatAssistant;

/**
 * @internal
 */
#[CoversClass(VirtualChatAssistant::class)]
class VirtualChatAssistantTest extends AbstractEntityTestCase
{
    use DoctrineTrait;
    use ServiceLocatorTrait;

    protected function createEntity(): VirtualChatAssistant
    {
        $assistant = new VirtualChatAssistant();
        $assistant->setId('test-id');
        $assistant->setName('test-assistant');

        return $assistant;
    }

    public function testCreateVirtualChatAssistant(): void
    {
        $assistant = new VirtualChatAssistant();
        $assistant->setId('test-id-123');
        $assistant->setName('测试虚拟助手');
        $assistant->setDescription('测试描述');
        $assistant->setModel('gpt-3.5-turbo');
        $assistant->setTemperature(0.7);
        $assistant->setMaxTokens(4096);

        $this->assertEquals('test-id-123', $assistant->getId());
        $this->assertEquals('测试虚拟助手', $assistant->getName());
        $this->assertEquals('测试描述', $assistant->getDescription());
        $this->assertEquals('gpt-3.5-turbo', $assistant->getModel());
        $this->assertEquals(0.7, $assistant->getTemperature());
        $this->assertEquals(4096, $assistant->getMaxTokens());
    }

    public function testIdGetterAndSetter(): void
    {
        $assistant = new VirtualChatAssistant();

        $this->assertNull($assistant->getId());

        $assistant->setId('remote-123');
        $this->assertEquals('remote-123', $assistant->getId());
    }

    public function testNameGetterAndSetter(): void
    {
        $assistant = new VirtualChatAssistant();

        $this->assertNull($assistant->getName());

        $assistant->setName('Test Assistant');
        $this->assertEquals('Test Assistant', $assistant->getName());
    }

    public function testDescriptionGetterAndSetter(): void
    {
        $assistant = new VirtualChatAssistant();

        $this->assertNull($assistant->getDescription());

        $description = 'This is a test description';
        $assistant->setDescription($description);
        $this->assertEquals($description, $assistant->getDescription());
    }

    public function testDatasetIdsGetterAndSetter(): void
    {
        $assistant = new VirtualChatAssistant();

        $this->assertNull($assistant->getDatasetIds());

        $datasetIds = ['dataset1', 'dataset2', 'dataset3'];
        $assistant->setDatasetIds($datasetIds);
        $this->assertEquals($datasetIds, $assistant->getDatasetIds());

        $assistant->setDatasetIds(null);
        $this->assertNull($assistant->getDatasetIds());
    }

    public function testSystemPromptGetterAndSetter(): void
    {
        $assistant = new VirtualChatAssistant();

        $this->assertNull($assistant->getSystemPrompt());

        $systemPrompt = 'You are a helpful assistant.';
        $assistant->setSystemPrompt($systemPrompt);
        $this->assertEquals($systemPrompt, $assistant->getSystemPrompt());
    }

    public function testModelGetterAndSetter(): void
    {
        $assistant = new VirtualChatAssistant();

        $this->assertNull($assistant->getModel());

        $assistant->setModel('gpt-4');
        $this->assertEquals('gpt-4', $assistant->getModel());
    }

    public function testTemperatureGetterAndSetter(): void
    {
        $assistant = new VirtualChatAssistant();

        $this->assertNull($assistant->getTemperature());

        $assistant->setTemperature(0.8);
        $this->assertEquals(0.8, $assistant->getTemperature());
    }

    public function testMaxTokensGetterAndSetter(): void
    {
        $assistant = new VirtualChatAssistant();

        $this->assertNull($assistant->getMaxTokens());

        $assistant->setMaxTokens(2048);
        $this->assertEquals(2048, $assistant->getMaxTokens());
    }

    public function testTopPGetterAndSetter(): void
    {
        $assistant = new VirtualChatAssistant();

        $this->assertNull($assistant->getTopP());

        $assistant->setTopP(0.95);
        $this->assertEquals(0.95, $assistant->getTopP());
    }

    public function testTopKGetterAndSetter(): void
    {
        $assistant = new VirtualChatAssistant();

        $this->assertNull($assistant->getTopK());

        $assistant->setTopK(40.0);
        $this->assertEquals(40.0, $assistant->getTopK());
    }

    public function testLanguageGetterAndSetter(): void
    {
        $assistant = new VirtualChatAssistant();

        $this->assertNull($assistant->getLanguage());

        $assistant->setLanguage('zh-CN');
        $this->assertEquals('zh-CN', $assistant->getLanguage());
    }

    public function testIsActiveGetterAndSetter(): void
    {
        $assistant = new VirtualChatAssistant();

        $this->assertNull($assistant->getIsActive());

        $assistant->setIsActive(true);
        $this->assertTrue($assistant->getIsActive());

        $assistant->setIsActive(false);
        $this->assertFalse($assistant->getIsActive());
    }

    public function testSessionCountGetterAndSetter(): void
    {
        $assistant = new VirtualChatAssistant();

        $this->assertNull($assistant->getSessionCount());

        $assistant->setSessionCount(10);
        $this->assertEquals(10, $assistant->getSessionCount());
    }

    public function testMessageCountGetterAndSetter(): void
    {
        $assistant = new VirtualChatAssistant();

        $this->assertNull($assistant->getMessageCount());

        $assistant->setMessageCount(100);
        $this->assertEquals(100, $assistant->getMessageCount());
    }

    public function testLastUsedAtGetterAndSetter(): void
    {
        $assistant = new VirtualChatAssistant();

        $this->assertNull($assistant->getLastUsedAt());

        $lastUsedAt = '2024-01-01 12:00:00';
        $assistant->setLastUsedAt($lastUsedAt);
        $this->assertEquals($lastUsedAt, $assistant->getLastUsedAt());
    }

    public function testToStringWithName(): void
    {
        $assistant = new VirtualChatAssistant();
        $assistantName = '测试虚拟AI助手';
        $assistant->setName($assistantName);

        $this->assertEquals($assistantName, (string) $assistant);
    }

    public function testToStringWithoutNameButWithId(): void
    {
        $assistant = new VirtualChatAssistant();
        $assistant->setId('test-id-456');

        $this->assertEquals('test-id-456', (string) $assistant);
    }

    public function testToStringWithoutNameAndId(): void
    {
        $assistant = new VirtualChatAssistant();

        $this->assertEquals('(new)', (string) $assistant);
    }

    public function testNullableProperties(): void
    {
        $assistant = new VirtualChatAssistant();

        // 所有属性初始值都应为 null
        $this->assertNull($assistant->getId());
        $this->assertNull($assistant->getName());
        $this->assertNull($assistant->getDescription());
        $this->assertNull($assistant->getDatasetIds());
        $this->assertNull($assistant->getSystemPrompt());
        $this->assertNull($assistant->getModel());
        $this->assertNull($assistant->getTemperature());
        $this->assertNull($assistant->getMaxTokens());
        $this->assertNull($assistant->getTopP());
        $this->assertNull($assistant->getTopK());
        $this->assertNull($assistant->getLanguage());
        $this->assertNull($assistant->getIsActive());
        $this->assertNull($assistant->getSessionCount());
        $this->assertNull($assistant->getMessageCount());
        $this->assertNull($assistant->getLastUsedAt());
    }

    /**
     * @param mixed $value
     */
    #[DataProvider('propertiesProvider')]
    public function testPropertyGettersAndSetters(string $property, $value): void
    {
        $assistant = new VirtualChatAssistant();

        // 根据属性名直接调用对应的 getter 和 setter
        match ($property) {
            'id' => $this->assertPropertyGetterSetter($assistant, $value, fn ($e, $v) => $e->setId($v), fn ($e) => $e->getId()),
            'name' => $this->assertPropertyGetterSetter($assistant, $value, fn ($e, $v) => $e->setName($v), fn ($e) => $e->getName()),
            'description' => $this->assertPropertyGetterSetter($assistant, $value, fn ($e, $v) => $e->setDescription($v), fn ($e) => $e->getDescription()),
            'datasetIds' => $this->assertPropertyGetterSetter($assistant, $value, fn ($e, $v) => $e->setDatasetIds($v), fn ($e) => $e->getDatasetIds()),
            'systemPrompt' => $this->assertPropertyGetterSetter($assistant, $value, fn ($e, $v) => $e->setSystemPrompt($v), fn ($e) => $e->getSystemPrompt()),
            'model' => $this->assertPropertyGetterSetter($assistant, $value, fn ($e, $v) => $e->setModel($v), fn ($e) => $e->getModel()),
            'temperature' => $this->assertPropertyGetterSetter($assistant, $value, fn ($e, $v) => $e->setTemperature($v), fn ($e) => $e->getTemperature()),
            'maxTokens' => $this->assertPropertyGetterSetter($assistant, $value, fn ($e, $v) => $e->setMaxTokens($v), fn ($e) => $e->getMaxTokens()),
            'topP' => $this->assertPropertyGetterSetter($assistant, $value, fn ($e, $v) => $e->setTopP($v), fn ($e) => $e->getTopP()),
            'topK' => $this->assertPropertyGetterSetter($assistant, $value, fn ($e, $v) => $e->setTopK($v), fn ($e) => $e->getTopK()),
            'language' => $this->assertPropertyGetterSetter($assistant, $value, fn ($e, $v) => $e->setLanguage($v), fn ($e) => $e->getLanguage()),
            'isActive' => $this->assertPropertyGetterSetter($assistant, $value, fn ($e, $v) => $e->setIsActive($v), fn ($e) => $e->getIsActive()),
            'sessionCount' => $this->assertPropertyGetterSetter($assistant, $value, fn ($e, $v) => $e->setSessionCount($v), fn ($e) => $e->getSessionCount()),
            'messageCount' => $this->assertPropertyGetterSetter($assistant, $value, fn ($e, $v) => $e->setMessageCount($v), fn ($e) => $e->getMessageCount()),
            'lastUsedAt' => $this->assertPropertyGetterSetter($assistant, $value, fn ($e, $v) => $e->setLastUsedAt($v), fn ($e) => $e->getLastUsedAt()),
            default => self::fail("Unknown property: {$property}"),
        };
    }

    /**
     * @param callable(VirtualChatAssistant, mixed): void $setter
     * @param callable(VirtualChatAssistant): mixed       $getter
     * @param mixed                                       $value
     */
    private function assertPropertyGetterSetter(VirtualChatAssistant $entity, $value, callable $setter, callable $getter): void
    {
        $setter($entity, $value);
        $this->assertEquals($value, $getter($entity));
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'id' => ['id', 'test-id'];
        yield 'name' => ['name', 'Test Assistant'];
        yield 'description' => ['description', 'Test description'];
        yield 'datasetIds' => ['datasetIds', ['dataset1', 'dataset2']];
        yield 'systemPrompt' => ['systemPrompt', 'You are a helpful assistant'];
        yield 'model' => ['model', 'gpt-3.5-turbo'];
        yield 'temperature' => ['temperature', 0.7];
        yield 'maxTokens' => ['maxTokens', 4096];
        yield 'topP' => ['topP', 0.9];
        yield 'topK' => ['topK', 40.0];
        yield 'language' => ['language', 'zh-CN'];
        yield 'isActive' => ['isActive', true];
        yield 'sessionCount' => ['sessionCount', 10];
        yield 'messageCount' => ['messageCount', 100];
        yield 'lastUsedAt' => ['lastUsedAt', '2024-01-01 12:00:00'];
    }
}
