<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\RAGFlowApiBundle\Repository\ChunkRepository;

/**
 * 文档块实体类
 *
 * 存储从RAG-Flow API同步的文档块信息的本地副本
 */
#[ORM\Entity(repositoryClass: ChunkRepository::class)]
#[ORM\Table(name: 'rag_flow_chunk', options: ['comment' => 'RAG-Flow文档块本地副本'])]
class Chunk
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, options: ['comment' => '远程RAG-Flow块ID'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[IndexColumn]
    private string $remoteId;

    #[ORM\ManyToOne(targetEntity: Document::class, inversedBy: 'chunks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE', options: ['comment' => '所属文档'])]
    private ?Document $document = null;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '块内容'])]
    #[Assert\NotBlank]
    private string $content;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '块在文档中的位置'])]
    #[Assert\PositiveOrZero]
    private ?int $position = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '块大小(字符数)'])]
    #[Assert\Positive]
    private ?int $size = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ['comment' => '相似度分数'])]
    #[Assert\Range(min: 0, max: 1)]
    private ?float $similarityScore = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(name: 'metadata', type: Types::JSON, nullable: true, options: ['comment' => '元数据(JSON格式)'])]
    #[Assert\Type(type: 'array')]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '远程创建时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $remoteCreateTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '远程更新时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $remoteUpdateTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '最后同步时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $lastSyncTime = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '带权重的内容'])]
    #[Assert\Length(max: 65535)]
    private ?string $contentWithWeight = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '页码'])]
    #[Assert\PositiveOrZero]
    private ?int $pageNumber = null;

    /**
     * @var array<int|string, mixed>|null
     */
    #[ORM\Column(name: 'positions', type: Types::JSON, nullable: true, options: ['comment' => '位置信息'])]
    #[Assert\Type(type: 'array')]
    private ?array $positions = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '起始位置'])]
    #[Assert\PositiveOrZero]
    private ?int $startPos = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '结束位置'])]
    #[Assert\PositiveOrZero]
    private ?int $endPos = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => 'token数量'])]
    #[Assert\PositiveOrZero]
    private ?int $tokenCount = null;

    /**
     * @var array<float>|null
     */
    #[ORM\Column(name: 'embedding_vector', type: Types::JSON, nullable: true, options: ['comment' => '嵌入向量'])]
    #[Assert\Type(type: 'array')]
    private ?array $embeddingVector = null;

    /**
     * @var array<string>|null
     */
    #[ORM\Column(name: 'keywords', type: Types::JSON, nullable: true, options: ['comment' => '关键词列表'])]
    #[Assert\Type(type: 'array')]
    private ?array $keywords = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * 设置ID（仅供测试或特殊场景使用,正常情况下由Doctrine管理）
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getRemoteId(): string
    {
        return $this->remoteId;
    }

    public function setRemoteId(string $remoteId): void
    {
        $this->remoteId = $remoteId;
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function setDocument(?Document $document): void
    {
        $this->document = $document;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): void
    {
        $this->size = $size;
    }

    public function getSimilarityScore(): ?float
    {
        return $this->similarityScore;
    }

    public function setSimilarityScore(?float $similarityScore): void
    {
        $this->similarityScore = $similarityScore;
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

    public function getRemoteCreateTime(): ?\DateTimeImmutable
    {
        return $this->remoteCreateTime;
    }

    public function setRemoteCreateTime(?\DateTimeImmutable $remoteCreateTime): void
    {
        $this->remoteCreateTime = $remoteCreateTime;
    }

    public function getRemoteUpdateTime(): ?\DateTimeImmutable
    {
        return $this->remoteUpdateTime;
    }

    public function setRemoteUpdateTime(?\DateTimeImmutable $remoteUpdateTime): void
    {
        $this->remoteUpdateTime = $remoteUpdateTime;
    }

    public function getLastSyncTime(): ?\DateTimeImmutable
    {
        return $this->lastSyncTime;
    }

    public function setLastSyncTime(?\DateTimeImmutable $lastSyncTime): void
    {
        $this->lastSyncTime = $lastSyncTime;
    }

    public function getContentWithWeight(): ?string
    {
        return $this->contentWithWeight;
    }

    public function setContentWithWeight(?string $contentWithWeight): void
    {
        $this->contentWithWeight = $contentWithWeight;
    }

    public function getPageNumber(): ?int
    {
        return $this->pageNumber;
    }

    public function setPageNumber(?int $pageNumber): void
    {
        $this->pageNumber = $pageNumber;
    }

    /**
     * @return array<int|string, mixed>|null
     */
    public function getPositions(): ?array
    {
        return $this->positions;
    }

    /**
     * @param array<int|string, mixed>|null $positions
     */
    public function setPositions(?array $positions): void
    {
        $this->positions = $positions;
    }

    public function getStartPos(): ?int
    {
        return $this->startPos;
    }

    public function setStartPos(?int $startPos): void
    {
        $this->startPos = $startPos;
    }

    public function getEndPos(): ?int
    {
        return $this->endPos;
    }

    public function setEndPos(?int $endPos): void
    {
        $this->endPos = $endPos;
    }

    public function getTokenCount(): ?int
    {
        return $this->tokenCount;
    }

    public function setTokenCount(?int $tokenCount): void
    {
        $this->tokenCount = $tokenCount;
    }

    /**
     * @return array<float>|null
     */
    public function getEmbeddingVector(): ?array
    {
        return $this->embeddingVector;
    }

    /**
     * @param array<float>|null $embeddingVector
     */
    public function setEmbeddingVector(?array $embeddingVector): void
    {
        $this->embeddingVector = $embeddingVector;
    }

    /**
     * @return array<string>|null
     */
    public function getKeywords(): ?array
    {
        return $this->keywords;
    }

    /**
     * @param array<string>|null $keywords
     */
    public function setKeywords(?array $keywords): void
    {
        $this->keywords = $keywords;
    }

    public function __toString(): string
    {
        return substr($this->content, 0, 100) . (strlen($this->content) > 100 ? '...' : '');
    }
}
