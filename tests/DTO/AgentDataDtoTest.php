<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\DTO\AgentDataDto;

/**
 * 测试Agent数据DTO
 * @internal
 */
#[CoversClass(AgentDataDto::class)]
class AgentDataDtoTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $createdAt = new \DateTimeImmutable('2023-01-01 00:00:00');
        $updatedAt = new \DateTimeImmutable('2023-01-02 00:00:00');
        $dsl = ['type' => 'chat', 'config' => ['model' => 'gpt-3.5']];

        $dto = new AgentDataDto(
            id: '123',
            title: 'Test Agent',
            description: 'Test Description',
            dsl: $dsl,
            status: 'active',
            createdAt: $createdAt,
            updatedAt: $updatedAt
        );

        $this->assertEquals('123', $dto->getId());
        $this->assertEquals('Test Agent', $dto->getTitle());
        $this->assertEquals('Test Description', $dto->getDescription());
        $this->assertEquals($dsl, $dto->getDsl());
        $this->assertEquals('active', $dto->getStatus());
        $this->assertEquals($createdAt, $dto->getCreatedAt());
        $this->assertEquals($updatedAt, $dto->getUpdatedAt());
    }

    public function testConstructorWithNullValues(): void
    {
        $dto = new AgentDataDto();

        $this->assertNull($dto->getId());
        $this->assertNull($dto->getTitle());
        $this->assertNull($dto->getDescription());
        $this->assertNull($dto->getDsl());
        $this->assertNull($dto->getStatus());
        $this->assertNull($dto->getCreatedAt());
        $this->assertNull($dto->getUpdatedAt());
    }

    public function testFromArrayWithCompleteData(): void
    {
        $data = [
            'id' => '123',
            'title' => 'Test Agent',
            'description' => 'Test Description',
            'dsl' => ['type' => 'chat'],
            'status' => 'active',
            'created_at' => '2023-01-01T00:00:00Z',
            'updated_at' => '2023-01-02T00:00:00Z',
        ];

        $dto = AgentDataDto::fromArray($data);

        $this->assertEquals('123', $dto->getId());
        $this->assertEquals('Test Agent', $dto->getTitle());
        $this->assertEquals('Test Description', $dto->getDescription());
        $this->assertEquals(['type' => 'chat'], $dto->getDsl());
        $this->assertEquals('active', $dto->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $dto->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $dto->getUpdatedAt());
    }

    public function testFromArrayWithNumericId(): void
    {
        $data = [
            'id' => 123,
            'title' => 'Test Agent',
        ];

        $dto = AgentDataDto::fromArray($data);

        $this->assertEquals('123', $dto->getId());
        $this->assertEquals('Test Agent', $dto->getTitle());
    }

    public function testFromArrayWithPartialData(): void
    {
        $data = [
            'title' => 'Test Agent',
            'status' => 'active',
        ];

        $dto = AgentDataDto::fromArray($data);

        $this->assertNull($dto->getId());
        $this->assertEquals('Test Agent', $dto->getTitle());
        $this->assertNull($dto->getDescription());
        $this->assertNull($dto->getDsl());
        $this->assertEquals('active', $dto->getStatus());
        $this->assertNull($dto->getCreatedAt());
        $this->assertNull($dto->getUpdatedAt());
    }

    public function testFromArrayWithInvalidData(): void
    {
        $data = [
            'id' => ['invalid'],
            'title' => 123, // not string
            'description' => null,
            'dsl' => 'invalid', // not array
            'status' => 456, // not string
            'created_at' => 'invalid-date',
            'updated_at' => ['invalid'],
        ];

        $dto = AgentDataDto::fromArray($data);

        $this->assertNull($dto->getId());
        $this->assertNull($dto->getTitle());
        $this->assertNull($dto->getDescription());
        $this->assertNull($dto->getDsl());
        $this->assertNull($dto->getStatus());
        $this->assertNull($dto->getCreatedAt());
        $this->assertNull($dto->getUpdatedAt());
    }

    public function testToArray(): void
    {
        $createdAt = new \DateTimeImmutable('2023-01-01 00:00:00');
        $updatedAt = new \DateTimeImmutable('2023-01-02 00:00:00');
        $dsl = ['type' => 'chat'];

        $dto = new AgentDataDto(
            id: '123',
            title: 'Test Agent',
            description: 'Test Description',
            dsl: $dsl,
            status: 'active',
            createdAt: $createdAt,
            updatedAt: $updatedAt
        );

        $array = $dto->toArray();

        $expected = [
            'id' => '123',
            'title' => 'Test Agent',
            'description' => 'Test Description',
            'dsl' => $dsl,
            'status' => 'active',
            'created_at' => $createdAt->format('c'),
            'updated_at' => $updatedAt->format('c'),
        ];

        $this->assertEquals($expected, $array);
    }

    public function testToArrayWithNullValues(): void
    {
        $dto = new AgentDataDto();

        $array = $dto->toArray();

        $expected = [
            'id' => null,
            'title' => null,
            'description' => null,
            'dsl' => null,
            'status' => null,
            'created_at' => null,
            'updated_at' => null,
        ];

        $this->assertEquals($expected, $array);
    }

    public function testParseDateTimeWithValidString(): void
    {
        $result = $this->invokePrivateMethod(AgentDataDto::class, 'parseDateTime', ['2023-01-01T00:00:00Z']);

        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
        $this->assertEquals('2023-01-01T00:00:00+00:00', $result->format('c'));
    }

    public function testParseDateTimeWithNull(): void
    {
        $result = $this->invokePrivateMethod(AgentDataDto::class, 'parseDateTime', [null]);

        $this->assertNull($result);
    }

    public function testParseDateTimeWithDateTimeImmutable(): void
    {
        $dateTime = new \DateTimeImmutable('2023-01-01 00:00:00');
        $result = $this->invokePrivateMethod(AgentDataDto::class, 'parseDateTime', [$dateTime]);

        $this->assertSame($dateTime, $result);
    }

    public function testParseDateTimeWithInvalidString(): void
    {
        $result = $this->invokePrivateMethod(AgentDataDto::class, 'parseDateTime', ['invalid-date']);

        $this->assertNull($result);
    }

    public function testParseDateTimeWithInvalidType(): void
    {
        $result = $this->invokePrivateMethod(AgentDataDto::class, 'parseDateTime', [[2023]]);

        $this->assertNull($result);
    }

    /**
     * 调用私有方法的辅助函数
     */
    private function invokePrivateMethod(string $className, string $methodName, array $parameters = []): mixed
    {
        $reflection = new \ReflectionClass($className);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs(null, $parameters);
    }
}
