<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\DTO;

/**
 * Conversation数据DTO
 */
final class ConversationDataDto
{
    private ?string $id;

    private ?string $name;

    private ?string $description;

    /** @var array<string>|null */
    private ?array $datasetIds;

    /** @var array<string, mixed>|null */
    private ?array $config;

    private ?string $status;

    private ?\DateTimeImmutable $createdAt;

    private ?\DateTimeImmutable $updatedAt;

    /**
     * @param array<string>|null $datasetIds
     * @param array<string, mixed>|null $config
     */
    public function __construct(
        ?string $id = null,
        ?string $name = null,
        ?string $description = null,
        ?array $datasetIds = null,
        ?array $config = null,
        ?string $status = null,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $updatedAt = null,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->datasetIds = $datasetIds;
        $this->config = $config;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    /**
     * 从数组创建Conversation数据DTO
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            self::extractStringOrNull($data, 'id'),
            self::extractStringOrNull($data, 'name'),
            self::extractStringOrNull($data, 'description'),
            self::extractStringArrayOrNull($data, 'dataset_ids'),
            self::extractArrayOrNull($data, 'config'),
            self::extractStringOrNull($data, 'status'),
            self::extractDateTimeOrNull($data, 'created_at'),
            self::extractDateTimeOrNull($data, 'updated_at')
        );
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
            'name' => $this->name,
            'description' => $this->description,
            'dataset_ids' => $this->datasetIds,
            'config' => $this->config,
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return array<string>|null
     */
    public function getDatasetIds(): ?array
    {
        return $this->datasetIds;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getConfig(): ?array
    {
        return $this->config;
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

    /**
     * 安全提取字符串值
     * @param array<string, mixed> $data
     */
    private static function extractStringOrNull(array $data, string $key): ?string
    {
        return isset($data[$key]) && (is_string($data[$key]) || is_numeric($data[$key])) ? (string) $data[$key] : null;
    }

    /**
     * 安全提取字符串数组
     * @param array<string, mixed> $data
     * @return array<string>|null
     */
    private static function extractStringArrayOrNull(array $data, string $key): ?array
    {
        if (!isset($data[$key]) || !is_array($data[$key])) {
            return null;
        }

        return array_map(static function ($item): string {
            return is_string($item) || is_numeric($item) ? (string) $item : '';
        }, $data[$key]);
    }

    /**
     * 安全提取数组
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    private static function extractArrayOrNull(array $data, string $key): ?array
    {
        if (!isset($data[$key]) || !is_array($data[$key])) {
            return null;
        }

        /** @var array<string, mixed> $array */
        $array = $data[$key];

        return $array;
    }

    /**
     * 安全提取日期时间
     * @param array<string, mixed> $data
     */
    private static function extractDateTimeOrNull(array $data, string $key): ?\DateTimeImmutable
    {
        return self::parseDateTime($data[$key] ?? null);
    }
}
