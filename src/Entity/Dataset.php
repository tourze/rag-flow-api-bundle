<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\RAGFlowApiBundle\Repository\DatasetRepository;

/**
 * 数据集实体类
 *
 * 存储从RAG-Flow API同步的数据集信息的本地副本
 */
#[ORM\Entity(repositoryClass: DatasetRepository::class)]
#[ORM\Table(name: 'rag_flow_dataset', options: ['comment' => 'RAG-Flow数据集本地副本'])]
class Dataset
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true, options: ['comment' => '远程RAG-Flow数据集ID'])]
    #[Assert\Length(max: 255)]
    #[IndexColumn]
    private ?string $remoteId = null;

    #[ORM\ManyToOne(targetEntity: RAGFlowInstance::class)]
    #[ORM\JoinColumn(nullable: false, options: ['comment' => '关联的RAG-Flow实例'])]
    private RAGFlowInstance $ragFlowInstance;

    #[ORM\Column(length: 255, unique: true, options: ['comment' => '数据集名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 255)]
    #[IndexColumn]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '数据集描述'])]
    #[Assert\Length(max: 2000)]
    private ?string $description = null;

    #[ORM\Column(length: 100, nullable: true, options: ['comment' => '解析方法'])]
    #[Assert\Length(max: 100)]
    private ?string $parserMethod = null;

    #[ORM\Column(length: 100, nullable: true, options: ['comment' => '分块方法'])]
    #[Assert\Length(max: 100)]
    private ?string $chunkMethod = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '分块大小'])]
    #[Assert\Positive]
    private ?int $chunkSize = null;

    #[ORM\Column(length: 10, nullable: true, options: ['comment' => '语言'])]
    #[Assert\Length(max: 10)]
    private ?string $language = null;

    #[ORM\Column(length: 255, nullable: true, options: ['comment' => '嵌入模型'])]
    #[Assert\Length(max: 255)]
    private ?string $embeddingModel = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ['comment' => '相似度阈值'])]
    #[Assert\Range(min: 0, max: 1)]
    private ?float $similarityThreshold = null;

    #[ORM\Column(length: 50, nullable: true, options: ['comment' => '状态'])]
    #[Assert\Length(max: 50)]
    #[IndexColumn]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '远程创建时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $remoteCreateTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '远程更新时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $remoteUpdateTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '最后同步时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $lastSyncTime = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否启用', 'default' => true])]
    #[Assert\NotNull]
    private bool $enabled = true;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(name: 'chunk_config', type: Types::JSON, nullable: true, options: ['comment' => '分块配置'])]
    #[Assert\Type(type: 'array')]
    private ?array $chunkConfig = null;

    /**
     * @var Collection<int, Document>
     */
    #[ORM\OneToMany(mappedBy: 'dataset', targetEntity: Document::class, cascade: ['persist', 'remove'])]
    private Collection $documents;

    /**
     * @var Collection<int, ChatAssistant>
     */
    #[ORM\OneToMany(mappedBy: 'dataset', targetEntity: ChatAssistant::class, cascade: ['persist', 'remove'])]
    private Collection $chatAssistants;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
        $this->chatAssistants = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * 获取数据库主键ID
     */
    public function getDatabaseId(): ?int
    {
        return $this->id;
    }

    /**
     * @param string|int|null $id
     */
    public function setId($id): void
    {
        if (is_numeric($id)) {
            $this->id = (int) $id;
        }
    }

    public function getRemoteId(): ?string
    {
        return $this->remoteId;
    }

    public function setRemoteId(?string $remoteId): void
    {
        $this->remoteId = $remoteId;
    }

    public function getRagFlowInstance(): RAGFlowInstance
    {
        return $this->ragFlowInstance;
    }

    public function setRagFlowInstance(RAGFlowInstance $ragFlowInstance): void
    {
        $this->ragFlowInstance = $ragFlowInstance;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getParserMethod(): ?string
    {
        return $this->parserMethod;
    }

    public function setParserMethod(?string $parserMethod): void
    {
        $this->parserMethod = $parserMethod;
    }

    public function getChunkMethod(): ?string
    {
        return $this->chunkMethod;
    }

    public function setChunkMethod(?string $chunkMethod): void
    {
        $this->chunkMethod = $chunkMethod;
    }

    public function getChunkSize(): ?int
    {
        return $this->chunkSize;
    }

    public function setChunkSize(?int $chunkSize): void
    {
        $this->chunkSize = $chunkSize;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): void
    {
        $this->language = $language;
    }

    public function getEmbeddingModel(): ?string
    {
        return $this->embeddingModel;
    }

    public function setEmbeddingModel(?string $embeddingModel): void
    {
        $this->embeddingModel = $embeddingModel;
    }

    public function getSimilarityThreshold(): ?float
    {
        return $this->similarityThreshold;
    }

    public function setSimilarityThreshold(?float $similarityThreshold): void
    {
        $this->similarityThreshold = $similarityThreshold;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
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

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getChunkConfig(): ?array
    {
        return $this->chunkConfig;
    }

    /**
     * @param array<string, mixed>|null $chunkConfig
     */
    public function setChunkConfig(?array $chunkConfig): void
    {
        $this->chunkConfig = $chunkConfig;
    }

    /**
     * @return Collection<int, Document>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(Document $document): void
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setDataset($this);
        }
    }

    public function removeDocument(Document $document): void
    {
        if ($this->documents->removeElement($document)) {
            if ($document->getDataset() === $this) {
                $document->setDataset(null);
            }
        }
    }

    /**
     * @return Collection<int, ChatAssistant>
     */
    public function getChatAssistants(): Collection
    {
        return $this->chatAssistants;
    }

    public function addChatAssistant(ChatAssistant $chatAssistant): void
    {
        if (!$this->chatAssistants->contains($chatAssistant)) {
            $this->chatAssistants->add($chatAssistant);
            $chatAssistant->setDataset($this);
        }
    }

    public function removeChatAssistant(ChatAssistant $chatAssistant): void
    {
        if ($this->chatAssistants->removeElement($chatAssistant)) {
            if ($chatAssistant->getDataset() === $this) {
                $chatAssistant->setDataset(null);
            }
        }
    }

    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * 获取文档数量
     */
    public function getDocumentCount(): int
    {
        return $this->documents->count();
    }

    /**
     * 获取文档总大小（字节）
     */
    public function getDocumentsTotalSize(): int
    {
        $totalSize = 0;
        foreach ($this->documents as $document) {
            $size = $document->getSize();
            if (null !== $size) {
                $totalSize += $size;
            }
        }

        return $totalSize;
    }

    /**
     * 获取格式化的文档总大小
     */
    public function getDocumentsTotalSizeFormatted(): string
    {
        $bytes = $this->getDocumentsTotalSize();

        if (0 === $bytes) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = log($bytes, 1024);
        $unitIndex = (int) floor($base);
        $size = round($bytes / (1024 ** $unitIndex), 2);

        return $size . ' ' . $units[$unitIndex];
    }

    /**
     * 获取各状态文档数量统计
     *
     * @return array<string, int>
     */
    public function getDocumentStatusStats(): array
    {
        /** @var array<string, int> $stats */
        $stats = [
            'pending' => 0,
            'uploading' => 0,
            'uploaded' => 0,
            'processing' => 0,
            'completed' => 0,
            'failed' => 0,
            'sync_failed' => 0,
        ];

        foreach ($this->documents as $document) {
            $status = $document->getStatus();
            $statusValue = $status->value;
            if (isset($stats[$statusValue])) {
                ++$stats[$statusValue];
            }
        }

        return $stats;
    }

    /**
     * 获取已完成处理的文档数量
     */
    public function getCompletedDocumentCount(): int
    {
        $count = 0;
        foreach ($this->documents as $document) {
            $statusValue = $document->getStatus()->value;
            if (in_array($statusValue, ['completed', 'uploaded'], true)) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * 获取处理中的文档数量
     */
    public function getProcessingDocumentCount(): int
    {
        $count = 0;
        foreach ($this->documents as $document) {
            $statusValue = $document->getStatus()->value;
            if (in_array($statusValue, ['uploading', 'processing'], true)) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * 获取失败的文档数量
     */
    public function getFailedDocumentCount(): int
    {
        $count = 0;
        foreach ($this->documents as $document) {
            $statusValue = $document->getStatus()->value;
            if (in_array($statusValue, ['failed', 'sync_failed'], true)) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * 检查是否有需要重试的文档
     */
    public function hasDocumentsRequiringRetry(): bool
    {
        foreach ($this->documents as $document) {
            if ($document->isUploadRequired()) {
                return true;
            }
        }

        return false;
    }
}
