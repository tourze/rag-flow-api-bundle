<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\RAGFlowApiBundle\Repository\ConversationRepository;

/**
 * 对话实体类
 *
 * 存储从RAG-Flow API同步的对话信息的本地副本
 */
#[ORM\Entity(repositoryClass: ConversationRepository::class)]
#[ORM\Table(name: 'rag_flow_conversation', options: ['comment' => 'RAG-Flow对话本地副本'])]
class Conversation
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, options: ['comment' => '远程RAG-Flow对话ID'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[IndexColumn]
    private string $remoteId;

    #[ORM\ManyToOne(targetEntity: ChatAssistant::class, inversedBy: 'conversations')]
    #[ORM\JoinColumn(nullable: true, options: ['comment' => '关联的聊天助手'])]
    private ?ChatAssistant $chatAssistant = null;

    #[ORM\ManyToOne(targetEntity: RAGFlowInstance::class)]
    #[ORM\JoinColumn(nullable: false, options: ['comment' => '关联的RAG-Flow实例'])]
    private ?RAGFlowInstance $ragFlowInstance = null;

    #[ORM\Column(length: 255, nullable: true, options: ['comment' => '用户ID'])]
    #[Assert\Length(max: 255)]
    #[IndexColumn]
    private ?string $userId = null;

    #[ORM\Column(length: 255, options: ['comment' => '对话标题'])]
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 255)]
    private string $title;

    /**
     * @var array<int|string, mixed>|null
     */
    #[ORM\Column(name: 'messages', type: Types::JSON, nullable: true, options: ['comment' => '对话消息历史(JSON格式)'])]
    #[Assert\Type(type: 'array')]
    private ?array $messages = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => '消息数量'])]
    #[Assert\PositiveOrZero]
    private int $messageCount = 0;

    #[ORM\Column(length: 50, nullable: true, options: ['comment' => '对话状态'])]
    #[Assert\Length(max: 50)]
    private ?string $status = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(name: 'context', type: Types::JSON, nullable: true, options: ['comment' => '对话上下文(JSON格式)'])]
    #[Assert\Type(type: 'array')]
    private ?array $context = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '最后活动时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $lastActivityTime = null;

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
     * @var array<string, mixed>|null
     */
    #[ORM\Column(name: 'dialog', type: Types::JSON, nullable: true, options: ['comment' => '对话内容(JSON格式)'])]
    #[Assert\Type(type: 'array')]
    private ?array $dialog = null;

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

    public function getChatAssistant(): ?ChatAssistant
    {
        return $this->chatAssistant;
    }

    public function setChatAssistant(?ChatAssistant $chatAssistant): void
    {
        $this->chatAssistant = $chatAssistant;
    }

    public function getRagFlowInstance(): ?RAGFlowInstance
    {
        return $this->ragFlowInstance;
    }

    public function setRagFlowInstance(?RAGFlowInstance $ragFlowInstance): void
    {
        $this->ragFlowInstance = $ragFlowInstance;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId): void
    {
        $this->userId = $userId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return array<int|string, mixed>|null
     */
    public function getMessages(): ?array
    {
        return $this->messages;
    }

    /**
     * @param array<int|string, mixed>|null $messages
     */
    public function setMessages(?array $messages): void
    {
        $this->messages = $messages;
        $this->messageCount = is_array($messages) ? count($messages) : 0;
    }

    public function getMessageCount(): int
    {
        return $this->messageCount;
    }

    public function setMessageCount(int $messageCount): void
    {
        $this->messageCount = $messageCount;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getContext(): ?array
    {
        return $this->context;
    }

    /**
     * @param array<string, mixed>|null $context
     */
    public function setContext(?array $context): void
    {
        $this->context = $context;
    }

    public function getLastActivityTime(): ?\DateTimeImmutable
    {
        return $this->lastActivityTime;
    }

    public function setLastActivityTime(?\DateTimeImmutable $lastActivityTime): void
    {
        $this->lastActivityTime = $lastActivityTime;
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
     * @return array<string, mixed>|null
     */
    public function getDialog(): ?array
    {
        return $this->dialog;
    }

    /**
     * @param array<string, mixed>|null $dialog
     */
    public function setDialog(?array $dialog): void
    {
        $this->dialog = $dialog;
    }

    public function getName(): string
    {
        return $this->title;
    }

    public function setName(string $name): void
    {
        $this->title = $name;
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
