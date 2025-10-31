<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\DTO;

use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\DTO\AgentDataDto;
use Tourze\RAGFlowApiBundle\DTO\ApiResponseDto;

/**
 * @internal
 * @coversNothing
 */
class ApiResponseDtoTest extends TestCase
{
    public function testFromArrayWithSuccessResponse(): void
    {
        $payload = [
            'code' => 0,
            'message' => 'success',
            'data' => [
                'id' => 'agent123',
                'title' => 'Test Agent',
                'description' => 'Test Description',
            ],
        ];

        $hydrator = static fn (array $data): AgentDataDto => AgentDataDto::fromArray($data);
        $response = ApiResponseDto::fromArray($payload, $hydrator);

        $this->assertTrue($response->isSuccess());
        $this->assertSame(0, $response->getCode());
        $this->assertSame('success', $response->getMessage());

        $data = $response->getData();
        $this->assertInstanceOf(AgentDataDto::class, $data);
        $this->assertSame('agent123', $data->getId());
        $this->assertSame('Test Agent', $data->getTitle());
        $this->assertSame('Test Description', $data->getDescription());
    }

    public function testFromArrayWithErrorResponse(): void
    {
        $payload = [
            'code' => 400,
            'message' => 'Bad Request',
            'data' => null,
        ];

        $hydrator = static fn (array $data): array => $data;
        $response = ApiResponseDto::fromArray($payload, $hydrator);

        $this->assertFalse($response->isSuccess());
        $this->assertSame(400, $response->getCode());
        $this->assertSame('Bad Request', $response->getMessage());
        $this->assertSame([], $response->getData());
    }

    public function testArrayAccessForBackwardCompatibility(): void
    {
        $payload = [
            'code' => 0,
            'message' => 'success',
            'data' => ['test' => 'value'],
        ];

        $hydrator = static fn (array $data): array => $data;
        $response = ApiResponseDto::fromArray($payload, $hydrator);

        // 测试ArrayAccess
        $this->assertTrue(isset($response['code']));
        $this->assertSame(0, $response['code']);
        $this->assertSame('success', $response['message']);
        $this->assertSame(['test' => 'value'], $response['data']);
        $this->assertNull($response['nonexistent']);
    }

    public function testToLegacyArray(): void
    {
        $payload = [
            'code' => 0,
            'message' => 'success',
            'data' => ['test' => 'value'],
        ];

        $hydrator = static fn (array $data): array => $data;
        $response = ApiResponseDto::fromArray($payload, $hydrator);

        $legacyArray = $response->toLegacyArray();
        $this->assertSame($payload, $legacyArray);
    }

    public function testAssertSuccess(): void
    {
        $payload = [
            'code' => 0,
            'message' => 'success',
            'data' => ['test' => 'value'],
        ];

        $hydrator = static fn (array $data): array => $data;
        $response = ApiResponseDto::fromArray($payload, $hydrator);

        // 应该不抛出异常
        $response->assertSuccess();
    }

    public function testAssertSuccessThrowsException(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('API请求失败: code=400, message=Bad Request');

        $payload = [
            'code' => 400,
            'message' => 'Bad Request',
            'data' => null,
        ];

        $hydrator = static fn (array $data): array => $data;
        $response = ApiResponseDto::fromArray($payload, $hydrator);

        $response->assertSuccess();
    }

    public function testArrayAccessIsReadOnly(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('API响应为只读，不允许修改');

        $payload = [
            'code' => 0,
            'message' => 'success',
            'data' => ['test' => 'value'],
        ];

        $hydrator = static fn (array $data): array => $data;
        $response = ApiResponseDto::fromArray($payload, $hydrator);

        $response['code'] = 1; // 应该抛出异常
    }
}
