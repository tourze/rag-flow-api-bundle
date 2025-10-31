<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\DTO;

/**
 * Agent数据DTO
 */
final class AgentDataDto
{
    private ?string $id;

    private ?string $title;

    private ?string $description;

    /** @var array<string, mixed>|null */
    private ?array $dsl;

    private ?string $status;

    private ?\DateTimeImmutable $createdAt;

    private ?\DateTimeImmutable $updatedAt;

    /**
     * @param array<string, mixed>|null $dsl
     */
    public function __construct(
        ?string $id = null,
        ?string $title = null,
        ?string $description = null,
        ?array $dsl = null,
        ?string $status = null,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $updatedAt = null,
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->dsl = $dsl;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    /**
     * 从数组创建Agent数据DTO
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            self::extractStringIdOrNull($data, 'id'),
            self::extractStringOrNull($data, 'title'),
            self::extractStringOrNull($data, 'description'),
            self::extractArrayOrNull($data, 'dsl'),
            self::extractStringOrNull($data, 'status'),
            self::extractDateTimeOrNull($data, 'created_at'),
            self::extractDateTimeOrNull($data, 'updated_at')
        );
    }

    /**
     * 从数组中提取字符串值或null
     *
     * @param array<string, mixed> $data
     */
    private static function extractStringOrNull(array $data, string $key): ?string
    {
        return isset($data[$key]) && is_string($data[$key]) ? $data[$key] : null;
    }

    /**
     * 从数组中提取数组或null
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    private static function extractArrayOrNull(array $data, string $key): ?array
    {
        return isset($data[$key]) && is_array($data[$key]) ? $data[$key] : null;
    }

    /**
     * 从数组中提取字符串ID或null
     *
     * @param array<string, mixed> $data
     */
    private static function extractStringIdOrNull(array $data, string $key): ?string
    {
        if (!isset($data[$key])) {
            return null;
        }

        $value = $data[$key];
        return (is_string($value) || is_numeric($value)) ? (string) $value : null;
    }

    /**
     * 从数组中提取DateTime或null
     *
     * @param array<string, mixed> $data
     */
    private static function extractDateTimeOrNull(array $data, string $key): ?\DateTimeImmutable
    {
        return isset($data[$key]) ? self::parseDateTime($data[$key]) : null;
    }

    /**
     * 转换为数组
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'dsl' => $this->dsl,
            'status' => $this->status,
            'created_at' => $this->createdAt?->format('c'),
            'updated_at' => $this->updatedAt?->format('c'),
        ];
    }

    private static function parseDateTime(mixed $value): ?\DateTimeImmutable
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }

        if (is_string($value)) {
            try {
                return new \DateTimeImmutable($value);
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDsl(): ?array
    {
        return $this->dsl;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
