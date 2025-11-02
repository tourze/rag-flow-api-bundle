<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\DTO;

/**
 * Dataset数据DTO
 */
final class DatasetDataDto
{
    private ?string $id;

    private ?string $name;

    private ?string $description;

    private ?string $language;

    private ?int $chunkCount;

    private ?int $documentCount;

    private ?string $status;

    /** @var array<string, mixed>|null */
    private ?array $permission;

    private ?\DateTimeImmutable $createdAt;

    private ?\DateTimeImmutable $updatedAt;

    /**
     * @param array<string, mixed>|null $permission
     */
    public function __construct(
        ?string $id = null,
        ?string $name = null,
        ?string $description = null,
        ?string $language = null,
        ?int $chunkCount = null,
        ?int $documentCount = null,
        ?string $status = null,
        ?array $permission = null,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $updatedAt = null,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->language = $language;
        $this->chunkCount = $chunkCount;
        $this->documentCount = $documentCount;
        $this->status = $status;
        $this->permission = $permission;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    /**
     * 从数组创建Dataset数据DTO
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            self::extractStringOrNull($data, 'id'),
            self::extractStringOrNull($data, 'name'),
            self::extractStringOrNull($data, 'description'),
            self::extractStringOrNull($data, 'language'),
            self::extractIntOrNull($data, 'chunk_num'),
            self::extractIntOrNull($data, 'document_amount'),
            self::extractStringOrNull($data, 'status'),
            self::extractArrayOrNull($data, 'permission'),
            self::extractDateTimeOrNull($data, 'create_time'),
            self::extractDateTimeOrNull($data, 'update_time')
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
     * 从数组中提取整数或null
     *
     * @param array<string, mixed> $data
     */
    private static function extractIntOrNull(array $data, string $key): ?int
    {
        if (!isset($data[$key])) {
            return null;
        }

        $value = $data[$key];

        return (is_int($value) || is_numeric($value)) ? (int) $value : null;
    }

    /**
     * 从数组中提取数组或null
     *
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
            'name' => $this->name,
            'description' => $this->description,
            'language' => $this->language,
            'chunk_num' => $this->chunkCount,
            'document_amount' => $this->documentCount,
            'status' => $this->status,
            'permission' => $this->permission,
            'create_time' => $this->createdAt?->format('c'),
            'update_time' => $this->updatedAt?->format('c'),
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

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function getChunkCount(): ?int
    {
        return $this->chunkCount;
    }

    public function getDocumentCount(): ?int
    {
        return $this->documentCount;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPermission(): ?array
    {
        return $this->permission;
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
