<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\RAGFlowApiBundle\Repository\LlmModelRepository;

/**
 * LLM模型实体类
 *
 * 存储从RAG-Flow API同步的LLM模型信息的本地副本
 */
#[ORM\Entity(repositoryClass: LlmModelRepository::class)]
#[ORM\Table(name: 'rag_flow_llm_model', options: ['comment' => 'RAG-Flow LLM模型本地副本'])]
class LlmModel
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, options: ['comment' => '模型唯一标识符'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[IndexColumn]
    private string $fid;

    #[ORM\Column(length: 255, options: ['comment' => 'LLM模型名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[IndexColumn]
    private string $llmName;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '模型是否可用'])]
    #[Assert\NotNull]
    #[IndexColumn]
    private bool $available = false;

    #[ORM\Column(length: 100, options: ['comment' => '模型类型'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[IndexColumn]
    private string $modelType;

    #[ORM\Column(length: 255, options: ['comment' => '提供商名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[IndexColumn]
    private string $providerName;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '最大token数量'])]
    #[Assert\PositiveOrZero]
    private ?int $maxTokens = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '状态'])]
    #[Assert\PositiveOrZero]
    private ?int $status = null;

    #[ORM\Column(name: 'is_tools', type: Types::BOOLEAN, nullable: true, options: ['comment' => '是否支持工具'])]
    #[Assert\Type(type: 'bool')]
    private ?bool $isTools = null;

    /**
     * @var array<string>|null
     */
    #[ORM\Column(name: 'tags', type: Types::JSON, nullable: true, options: ['comment' => '模型标签'])]
    #[Assert\Type(type: 'array')]
    private ?array $tags = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => 'API创建日期'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $apiCreateDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => 'API创建时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $apiCreateTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => 'API更新日期'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $apiUpdateDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => 'API更新时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $apiUpdateTime = null;

    #[ORM\ManyToOne(targetEntity: RAGFlowInstance::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL', options: ['comment' => '关联的RAG-Flow实例'])]
    private ?RAGFlowInstance $ragFlowInstance;

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

    public function getFid(): string
    {
        return $this->fid;
    }

    public function setFid(string $fid): void
    {
        $this->fid = $fid;
    }

    public function getLlmName(): string
    {
        return $this->llmName;
    }

    public function setLlmName(string $llmName): void
    {
        $this->llmName = $llmName;
    }

    public function getAvailable(): bool
    {
        return $this->available;
    }

    public function setAvailable(bool $available): void
    {
        $this->available = $available;
    }

    public function getModelType(): string
    {
        return $this->modelType;
    }

    public function setModelType(string $modelType): void
    {
        $this->modelType = $modelType;
    }

    public function getProviderName(): string
    {
        return $this->providerName;
    }

    public function setProviderName(string $providerName): void
    {
        $this->providerName = $providerName;
    }

    public function getMaxTokens(): ?int
    {
        return $this->maxTokens;
    }

    public function setMaxTokens(?int $maxTokens): void
    {
        $this->maxTokens = $maxTokens;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): void
    {
        $this->status = $status;
    }

    public function getIsTools(): ?bool
    {
        return $this->isTools;
    }

    public function setIsTools(?bool $isTools): void
    {
        $this->isTools = $isTools;
    }

    /**
     * @return array<string>|null
     */
    public function getTags(): ?array
    {
        return $this->tags;
    }

    /**
     * @param array<string>|null $tags
     */
    public function setTags(?array $tags): void
    {
        $this->tags = $tags;
    }

    public function getApiCreateDate(): ?\DateTimeImmutable
    {
        return $this->apiCreateDate;
    }

    public function setApiCreateDate(?\DateTimeImmutable $apiCreateDate): void
    {
        $this->apiCreateDate = $apiCreateDate;
    }

    public function getApiCreateTime(): ?\DateTimeImmutable
    {
        return $this->apiCreateTime;
    }

    public function setApiCreateTime(?\DateTimeImmutable $apiCreateTime): void
    {
        $this->apiCreateTime = $apiCreateTime;
    }

    public function getApiUpdateDate(): ?\DateTimeImmutable
    {
        return $this->apiUpdateDate;
    }

    public function setApiUpdateDate(?\DateTimeImmutable $apiUpdateDate): void
    {
        $this->apiUpdateDate = $apiUpdateDate;
    }

    public function getApiUpdateTime(): ?\DateTimeImmutable
    {
        return $this->apiUpdateTime;
    }

    public function setApiUpdateTime(?\DateTimeImmutable $apiUpdateTime): void
    {
        $this->apiUpdateTime = $apiUpdateTime;
    }

    public function getRagFlowInstance(): ?RAGFlowInstance
    {
        return $this->ragFlowInstance;
    }

    public function setRagFlowInstance(?RAGFlowInstance $ragFlowInstance): void
    {
        $this->ragFlowInstance = $ragFlowInstance;
    }

    /**
     * 获取显示名称
     */
    public function getDisplayName(): string
    {
        return sprintf('%s (%s)', $this->llmName, $this->providerName);
    }

    /**
     * 检查模型是否为聊天类型
     */
    public function isChatModel(): bool
    {
        return 'chat' === $this->modelType;
    }

    /**
     * 检查模型是否为嵌入类型
     */
    public function isEmbeddingModel(): bool
    {
        return 'embedding' === $this->modelType;
    }

    /**
     * 检查模型是否为重排序类型
     */
    public function isRerankModel(): bool
    {
        return 'rerank' === $this->modelType;
    }

    public function __toString(): string
    {
        return $this->getDisplayName();
    }
}
