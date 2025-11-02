<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\DTO\DatasetDataDto;

/**
 * 测试Dataset数据DTO
 * @internal
 */
#[CoversClass(DatasetDataDto::class)]
class DatasetDataDtoTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $createdAt = new \DateTimeImmutable('2023-01-01 00:00:00');
        $updatedAt = new \DateTimeImmutable('2023-01-02 00:00:00');

        $dto = new DatasetDataDto(
            id: '123',
            name: 'Test Dataset',
            description: 'Test Description',
            status: 'active',
            createdAt: $createdAt,
            updatedAt: $updatedAt
        );

        $this->assertEquals('123', $dto->getId());
        $this->assertEquals('Test Dataset', $dto->getName());
        $this->assertEquals('Test Description', $dto->getDescription());
        $this->assertEquals('active', $dto->getStatus());
        $this->assertEquals($createdAt, $dto->getCreatedAt());
        $this->assertEquals($updatedAt, $dto->getUpdatedAt());
    }

    public function testConstructorWithNullValues(): void
    {
        $dto = new DatasetDataDto();

        $this->assertNull($dto->getId());
        $this->assertNull($dto->getName());
        $this->assertNull($dto->getDescription());
        $this->assertNull($dto->getStatus());
        $this->assertNull($dto->getCreatedAt());
        $this->assertNull($dto->getUpdatedAt());
    }

    public function testToArray(): void
    {
        $createdAt = new \DateTimeImmutable('2023-01-01 00:00:00');
        $updatedAt = new \DateTimeImmutable('2023-01-02 00:00:00');

        $dto = new DatasetDataDto(
            id: '123',
            name: 'Test Dataset',
            description: 'Test Description',
            status: 'active',
            createdAt: $createdAt,
            updatedAt: $updatedAt
        );

        $array = $dto->toArray();

        $expected = [
            'id' => '123',
            'name' => 'Test Dataset',
            'description' => 'Test Description',
            'status' => 'active',
            'created_at' => $createdAt->format('c'),
            'updated_at' => $updatedAt->format('c'),
        ];

        $this->assertEquals($expected, $array);
    }

    public function testToArrayWithNullValues(): void
    {
        $dto = new DatasetDataDto();

        $array = $dto->toArray();

        $expected = [
            'id' => null,
            'name' => null,
            'description' => null,
            'status' => null,
            'created_at' => null,
            'updated_at' => null,
        ];

        $this->assertEquals($expected, $array);
    }

    public function testFromPartialData(): void
    {
        $partialData = [
            'id' => '123',
            'name' => 'Test Dataset',
            'description' => 'Test Description',
        ];

        $dto = DatasetDataDto::fromArray($partialData);

        $this->assertEquals('123', $dto->getId());
        $this->assertEquals('Test Dataset', $dto->getName());
        $this->assertEquals('Test Description', $dto->getDescription());
        $this->assertNull($dto->getStatus());
    }

    public function testFromEmptyData(): void
    {
        $dto = DatasetDataDto::fromArray([]);

        $this->assertNull($dto->getId());
        $this->assertNull($dto->getName());
        $this->assertNull($dto->getDescription());
        $this->assertNull($dto->getStatus());
        $this->assertNull($dto->getCreatedAt());
        $this->assertNull($dto->getUpdatedAt());
    }
}
