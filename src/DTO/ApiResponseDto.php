<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\DTO;

use ArrayAccess;
use JsonSerializable;
use LogicException;

/**
 * API响应基础DTO
 *
 * @template TData
 * @implements \ArrayAccess<string, mixed>
 */
final class ApiResponseDto implements \ArrayAccess, \JsonSerializable
{
    private int $code;

    private string $message;

    /** @var TData */
    private $data;

    /** @var array<string, mixed> */
    private array $raw;

    /**
     * @param int $code 响应状态码
     * @param string $message 响应消息
     * @param TData $data 解析后的数据
     * @param array<string, mixed> $raw 原始响应数据（用于向后兼容）
     */
    public function __construct(
        int $code,
        string $message,
        $data,
        array $raw,
    ) {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
        $this->raw = $raw;
    }

    /**
     * 从数组创建响应DTO
     *
     * @param array<string, mixed> $payload API响应载荷
     * @param callable(array<string, mixed>): TData $hydrator 数据水合器
     * @return self<TData>
     */
    public static function fromArray(array $payload, callable $hydrator): self
    {
        $codeValue = $payload['code'] ?? 0;
        $code = is_int($codeValue) ? $codeValue : (is_numeric($codeValue) ? (int) $codeValue : 0);

        $messageValue = $payload['message'] ?? '';
        $message = is_string($messageValue) ? $messageValue : (is_scalar($messageValue) ? (string) $messageValue : '');

        $rawData = $payload['data'] ?? [];
        if (!is_array($rawData)) {
            $rawData = [];
        }

        // 使用水合器处理data字段
        $data = $hydrator($rawData);

        return new self(
            $code,
            $message,
            $data,
            $payload
        );
    }

    /**
     * 获取响应状态码
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * 获取响应消息
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * 获取解析后的数据
     *
     * @return TData
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * 检查响应是否成功
     */
    public function isSuccess(): bool
    {
        return 0 === $this->code;
    }

    /**
     * 转换为传统数组格式（向后兼容）
     *
     * @return array<string, mixed>
     */
    public function toLegacyArray(): array
    {
        return $this->raw;
    }

    /**
     * 断言响应成功，否则抛出异常
     *
     * @throws \LogicException 当响应失败时
     */
    public function assertSuccess(): void
    {
        if (!$this->isSuccess()) {
            throw new \LogicException(sprintf('API请求失败: code=%d, message=%s', $this->code, $this->message));
        }
    }

    /**
     * 断言响应状态码为指定值
     *
     * @param int $expectedCode 期望的状态码
     * @throws \LogicException 当状态码不匹配时
     */
    public function assertCode(int $expectedCode): void
    {
        if ($this->code !== $expectedCode) {
            throw new \LogicException(sprintf('API响应状态码不匹配: expected=%d, actual=%d', $expectedCode, $this->code));
        }
    }

    // ArrayAccess实现（用于向后兼容，将在未来版本中移除）
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->raw);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->raw[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException('API响应为只读，不允许修改');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('API响应为只读，不允许删除');
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->raw;
    }
}
