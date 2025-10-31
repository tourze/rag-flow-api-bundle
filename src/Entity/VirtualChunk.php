<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

/**
 * 虚拟文本块实体类
 *
 * 这是一个用于EasyAdmin CRUD界面的虚拟Entity，不对应数据库表
 * 实际的数据操作通过RAGFlow API进行
 */
#[ORM\Entity(readOnly: true)]
#[ORM\Table(name: 'virtual_chunk', options: ['comment' => '虚拟文本块表'])]
class VirtualChunk implements \Stringable
{
    use TimestampableAware;

    // 添加基本的 ORM 映射以支持 EasyAdmin，但标记为只读
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::STRING, length: 255, unique: true, options: ['comment' => '主键ID'])]
    private ?string $id = null;

    /**
     * 为 EasyAdmin 兼容性添加的 name 属性
     * 返回实体的显示名称
     */
    public function getName(): string
    {
        return $this->title ?? $this->id ?? 'Unknown';
    }

    /**
     * 为 EasyAdmin 兼容性添加的 isAccessible 方法
     * 返回实体是否可访问
     */
    public function isAccessible(): bool
    {
        return true;
    }

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '数据集ID'])]
    #[Assert\Length(max: 255)]
    private ?string $datasetId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '文档ID'])]
    #[Assert\Length(max: 255)]
    private ?string $documentId = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '文本块内容'])]
    #[Assert\Length(max: 65535)]
    private ?string $content = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '标题'])]
    #[Assert\Length(max: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::STRING, length: 1000, nullable: true, options: ['comment' => '关键词'])]
    #[Assert\Length(max: 1000)]
    private ?string $keywords = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ['comment' => '相似度分数'])]
    #[Assert\Range(min: 0, max: 1)]
    private ?float $similarityScore = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '位置'])]
    #[Assert\PositiveOrZero]
    private ?int $position = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '长度'])]
    #[Assert\PositiveOrZero]
    private ?int $length = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, options: ['comment' => '状态'])]
    #[Assert\Length(max: 50)]
    private ?string $status = null;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true, options: ['comment' => '语言'])]
    #[Assert\Length(max: 10)]
    private ?string $language = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'metadata', type: Types::JSON, nullable: true, options: ['comment' => '元数据'])]
    #[Assert\Type(type: 'array')]
    private ?array $metadata = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getDatasetId(): ?string
    {
        return $this->datasetId;
    }

    public function setDatasetId(?string $datasetId): void
    {
        $this->datasetId = $datasetId;
    }

    public function getDocumentId(): ?string
    {
        return $this->documentId;
    }

    public function setDocumentId(?string $documentId): void
    {
        $this->documentId = $documentId;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): void
    {
        $this->content = $content;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getKeywords(): ?string
    {
        return $this->keywords;
    }

    public function setKeywords(?string $keywords): void
    {
        $this->keywords = $keywords;
    }

    public function getSimilarityScore(): ?float
    {
        return $this->similarityScore;
    }

    public function setSimilarityScore(?float $similarityScore): void
    {
        $this->similarityScore = $similarityScore;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }

    public function getLength(): ?int
    {
        return $this->length;
    }

    public function setLength(?int $length): void
    {
        $this->length = $length;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): void
    {
        $this->language = $language;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function setMetadata(?array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function __toString(): string
    {
        return $this->title ?? $this->id ?? '(new)';
    }
}
