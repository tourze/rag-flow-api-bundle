<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\RAGFlowApiBundle\Repository\RAGFlowAgentRepository;

/**
 * RAGFlow智能体实体
 */
#[ORM\Entity(repositoryClass: RAGFlowAgentRepository::class)]
#[ORM\Table(name: 'rag_flow_agent', options: ['comment' => 'RAGFlow智能体表'])]
class RAGFlowAgent
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, options: ['comment' => '智能体标题'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '智能体描述'])]
    #[Assert\Length(max: 65535)]
    private ?string $description = null;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON, options: ['comment' => 'Canvas DSL配置'])]
    #[Assert\NotNull]
    private array $dsl = [];

    #[ORM\Column(length: 255, nullable: true, options: ['comment' => '远程智能体ID'])]
    #[Assert\Length(max: 255)]
    private ?string $remoteId = null;

    #[ORM\ManyToOne(targetEntity: RAGFlowInstance::class)]
    #[ORM\JoinColumn(nullable: false, options: ['comment' => '关联的RAG-Flow实例'])]
    #[Assert\NotNull]
    private RAGFlowInstance $ragFlowInstance;

    #[ORM\Column(length: 20, options: ['default' => 'draft', 'comment' => '智能体状态'])]
    #[Assert\Choice(choices: ['draft', 'published', 'archived', 'sync_failed'])]
    private string $status = 'draft';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '远程创建时间'])]
    #[Assert\Type(type: \DateTimeImmutable::class)]
    private ?\DateTimeImmutable $remoteCreateTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '远程更新时间'])]
    #[Assert\Type(type: \DateTimeImmutable::class)]
    private ?\DateTimeImmutable $remoteUpdateTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '最后同步时间'])]
    #[Assert\Type(type: \DateTimeImmutable::class)]
    private ?\DateTimeImmutable $lastSyncTime = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '同步错误信息'])]
    #[Assert\Length(max: 65535)]
    private ?string $syncErrorMessage = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * 设置ID（仅供测试或特殊场景使用，正常情况下由Doctrine管理）
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDsl(): array
    {
        return $this->dsl;
    }

    /**
     * @param array<string, mixed> $dsl
     */
    public function setDsl(array $dsl): void
    {
        $this->dsl = $dsl;
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
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

    public function getSyncErrorMessage(): ?string
    {
        return $this->syncErrorMessage;
    }

    public function setSyncErrorMessage(?string $syncErrorMessage): void
    {
        $this->syncErrorMessage = $syncErrorMessage;
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
