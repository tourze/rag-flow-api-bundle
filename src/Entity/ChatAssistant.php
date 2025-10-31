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
use Tourze\RAGFlowApiBundle\Repository\ChatAssistantRepository;

/**
 * 聊天助手实体类
 *
 * 存储从RAG-Flow API同步的聊天助手信息的本地副本
 */
#[ORM\Entity(repositoryClass: ChatAssistantRepository::class)]
#[ORM\Table(name: 'rag_flow_chat_assistant', options: ['comment' => 'RAG-Flow聊天助手本地副本'])]
class ChatAssistant
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\Column(name: 'remote_id', length: 255, nullable: true, options: ['comment' => '远程RAG-Flow助手ID'])]
    #[Assert\Length(max: 255)]
    #[IndexColumn]
    private ?string $remoteId = null;

    #[ORM\ManyToOne(targetEntity: Dataset::class, inversedBy: 'chatAssistants')]
    #[ORM\JoinColumn(nullable: true, options: ['comment' => '关联的数据集'])]
    private ?Dataset $dataset = null;

    /**
     * @var array<int, string>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '关联的数据集ID列表'])]
    #[Assert\Type(type: 'array')]
    private ?array $datasetIds = null;

    #[ORM\Column(length: 255, options: ['comment' => '助手名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 255)]
    #[IndexColumn]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '助手描述'])]
    #[Assert\Length(max: 2000)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '系统提示词'])]
    #[Assert\Length(max: 10000)]
    private ?string $systemPrompt = null;

    #[ORM\Column(length: 255, nullable: true, options: ['comment' => '使用的语言模型'])]
    #[Assert\Length(max: 255)]
    private ?string $llmModel = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ['comment' => '温度参数'])]
    #[Assert\Range(min: 0, max: 2)]
    private ?float $temperature = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ['comment' => 'top_p参数'])]
    #[Assert\Range(min: 0, max: 1)]
    private ?float $topP = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '最大tokens'])]
    #[Assert\Positive]
    private ?int $maxTokens = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ['comment' => '存在惩罚参数'])]
    #[Assert\Range(min: 0, max: 2)]
    private ?float $presencePenalty = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ['comment' => '频率惩罚参数'])]
    #[Assert\Range(min: 0, max: 2)]
    private ?float $frequencyPenalty = null;

    #[ORM\Column(length: 500, nullable: true, options: ['comment' => '头像URL'])]
    #[Assert\Length(max: 500)]
    private ?string $avatar = null;

    #[ORM\Column(length: 10, nullable: true, options: ['comment' => '语言'])]
    #[Assert\Length(max: 10)]
    private ?string $language = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true, 'comment' => '是否启用'])]
    #[Assert\NotNull]
    private bool $enabled = true;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '配置参数(JSON格式)'])]
    #[Assert\Type(type: 'array')]
    private ?array $config = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '开场白'])]
    #[Assert\Length(max: 2000)]
    private ?string $opener = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '空响应消息'])]
    #[Assert\Length(max: 2000)]
    private ?string $emptyResponse = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true, 'comment' => '是否显示引用'])]
    #[Assert\NotNull]
    private bool $showQuote = true;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ['comment' => '相似度阈值'])]
    #[Assert\Range(min: 0, max: 1)]
    private ?float $similarityThreshold = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ['comment' => '关键词相似度权重'])]
    #[Assert\Range(min: 0, max: 1)]
    private ?float $keywordsSimilarityWeight = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '返回top N个结果'])]
    #[Assert\Positive]
    private ?int $topN = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '提示词变量配置'])]
    #[Assert\Type(type: 'array')]
    private ?array $variables = null;

    #[ORM\Column(length: 255, nullable: true, options: ['comment' => '重排序模型'])]
    #[Assert\Length(max: 255)]
    private ?string $rerankModel = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => 'top_k参数'])]
    #[Assert\Positive]
    private ?int $topK = null;

    #[ORM\Column(length: 10, nullable: true, options: ['comment' => '状态'])]
    #[Assert\Length(max: 10)]
    private ?string $status = null;

    #[ORM\Column(length: 50, nullable: true, options: ['comment' => '提示词类型'])]
    #[Assert\Length(max: 50)]
    private ?string $promptType = null;

    #[ORM\Column(length: 10, nullable: true, options: ['comment' => '是否引用'])]
    #[Assert\Length(max: 10)]
    private ?string $doRefer = null;

    #[ORM\Column(length: 255, nullable: true, options: ['comment' => '租户ID'])]
    #[Assert\Length(max: 255)]
    private ?string $tenantId = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '远程创建时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $remoteCreateTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '远程更新时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $remoteUpdateTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '最后同步时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $lastSyncTime = null;

    /**
     * @var Collection<int, Conversation>
     */
    #[ORM\OneToMany(mappedBy: 'chatAssistant', targetEntity: Conversation::class, cascade: ['persist', 'remove'])]
    private Collection $conversations;

    public function __construct()
    {
        $this->conversations = new ArrayCollection();
    }

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

    public function getRemoteId(): ?string
    {
        return $this->remoteId;
    }

    public function setRemoteId(?string $remoteId): void
    {
        $this->remoteId = $remoteId;
    }

    public function getDataset(): ?Dataset
    {
        return $this->dataset;
    }

    public function setDataset(?Dataset $dataset): void
    {
        $this->dataset = $dataset;
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

    public function getSystemPrompt(): ?string
    {
        return $this->systemPrompt;
    }

    public function setSystemPrompt(?string $systemPrompt): void
    {
        $this->systemPrompt = $systemPrompt;
    }

    public function getLlmModel(): ?string
    {
        return $this->llmModel;
    }

    public function setLlmModel(?string $llmModel): void
    {
        $this->llmModel = $llmModel;
    }

    public function getTemperature(): ?float
    {
        return $this->temperature;
    }

    public function setTemperature(?float $temperature): void
    {
        $this->temperature = $temperature;
    }

    public function getMaxTokens(): ?int
    {
        return $this->maxTokens;
    }

    public function setMaxTokens(?int $maxTokens): void
    {
        $this->maxTokens = $maxTokens;
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
    public function getConfig(): ?array
    {
        return $this->config;
    }

    /**
     * @param array<string, mixed>|null $config
     */
    public function setConfig(?array $config): void
    {
        $this->config = $config;
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

    /**
     * @return Collection<int, Conversation>
     */
    public function getConversations(): Collection
    {
        return $this->conversations;
    }

    public function addConversation(Conversation $conversation): void
    {
        if (!$this->conversations->contains($conversation)) {
            $this->conversations->add($conversation);
            $conversation->setChatAssistant($this);
        }
    }

    public function removeConversation(Conversation $conversation): void
    {
        if ($this->conversations->removeElement($conversation)) {
            if ($conversation->getChatAssistant() === $this) {
                $conversation->setChatAssistant(null);
            }
        }
    }

    /**
     * @return array<int, string>|null
     */
    public function getDatasetIds(): ?array
    {
        return $this->datasetIds;
    }

    /**
     * @param array<int, string>|null $datasetIds
     */
    public function setDatasetIds(?array $datasetIds): void
    {
        $this->datasetIds = $datasetIds;
    }

    public function getTopP(): ?float
    {
        return $this->topP;
    }

    public function setTopP(?float $topP): void
    {
        $this->topP = $topP;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): void
    {
        $this->avatar = $avatar;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): void
    {
        $this->language = $language;
    }

    public function getOpener(): ?string
    {
        return $this->opener;
    }

    public function setOpener(?string $opener): void
    {
        $this->opener = $opener;
    }

    public function getEmptyResponse(): ?string
    {
        return $this->emptyResponse;
    }

    public function setEmptyResponse(?string $emptyResponse): void
    {
        $this->emptyResponse = $emptyResponse;
    }

    public function getShowQuote(): bool
    {
        return $this->showQuote;
    }

    public function setShowQuote(bool $showQuote): void
    {
        $this->showQuote = $showQuote;
    }

    public function getPresencePenalty(): ?float
    {
        return $this->presencePenalty;
    }

    public function setPresencePenalty(?float $presencePenalty): void
    {
        $this->presencePenalty = $presencePenalty;
    }

    public function getFrequencyPenalty(): ?float
    {
        return $this->frequencyPenalty;
    }

    public function setFrequencyPenalty(?float $frequencyPenalty): void
    {
        $this->frequencyPenalty = $frequencyPenalty;
    }

    public function getSimilarityThreshold(): ?float
    {
        return $this->similarityThreshold;
    }

    public function setSimilarityThreshold(?float $similarityThreshold): void
    {
        $this->similarityThreshold = $similarityThreshold;
    }

    public function getKeywordsSimilarityWeight(): ?float
    {
        return $this->keywordsSimilarityWeight;
    }

    public function setKeywordsSimilarityWeight(?float $keywordsSimilarityWeight): void
    {
        $this->keywordsSimilarityWeight = $keywordsSimilarityWeight;
    }

    public function getTopN(): ?int
    {
        return $this->topN;
    }

    public function setTopN(?int $topN): void
    {
        $this->topN = $topN;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getVariables(): ?array
    {
        return $this->variables;
    }

    /**
     * @param array<string, mixed>|null $variables
     */
    public function setVariables(?array $variables): void
    {
        $this->variables = $variables;
    }

    public function getRerankModel(): ?string
    {
        return $this->rerankModel;
    }

    public function setRerankModel(?string $rerankModel): void
    {
        $this->rerankModel = $rerankModel;
    }

    public function getTopK(): ?int
    {
        return $this->topK;
    }

    public function setTopK(?int $topK): void
    {
        $this->topK = $topK;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    public function getPromptType(): ?string
    {
        return $this->promptType;
    }

    public function setPromptType(?string $promptType): void
    {
        $this->promptType = $promptType;
    }

    public function getDoRefer(): ?string
    {
        return $this->doRefer;
    }

    public function setDoRefer(?string $doRefer): void
    {
        $this->doRefer = $doRefer;
    }

    public function getTenantId(): ?string
    {
        return $this->tenantId;
    }

    public function setTenantId(?string $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
