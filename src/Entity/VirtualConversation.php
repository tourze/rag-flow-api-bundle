<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

/**
 * 虚拟会话实体类
 *
 * 这是一个用于EasyAdmin CRUD界面的虚拟Entity，不对应数据库表
 * 实际的数据操作通过RAGFlow API进行
 */
#[ORM\Entity(readOnly: true)]
#[ORM\Table(name: 'virtual_conversation', options: ['comment' => '虚拟会话表'])]
class VirtualConversation implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\Column(name: 'id', type: Types::STRING, length: 255, nullable: true, options: ['comment' => '主键ID'])]
    private ?string $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '聊天ID'])]
    #[Assert\Length(max: 255)]
    private ?string $chatId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '会话ID'])]
    #[Assert\Length(max: 255)]
    private ?string $sessionId = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '用户消息'])]
    #[Assert\Length(max: 65535)]
    private ?string $userMessage = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '助手回复消息'])]
    #[Assert\Length(max: 65535)]
    private ?string $assistantMessage = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, options: ['comment' => '角色'])]
    #[Assert\Length(max: 50)]
    private ?string $role = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '消息数量'])]
    #[Assert\PositiveOrZero]
    private ?int $messageCount = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, options: ['comment' => '状态'])]
    #[Assert\Length(max: 50)]
    private ?string $status = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ['comment' => '响应时间(秒)'])]
    #[Assert\PositiveOrZero]
    private ?float $responseTime = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => 'Token数量'])]
    #[Assert\PositiveOrZero]
    private ?int $tokenCount = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'context', type: Types::JSON, nullable: true, options: ['comment' => '上下文信息'])]
    #[Assert\Type(type: 'array')]
    private ?array $context = null;

    /** @var array<int, array<string, mixed>>|null */
    #[ORM\Column(name: 'references', type: Types::JSON, nullable: true, options: ['comment' => '引用信息'])]
    #[Assert\Type(type: 'array')]
    private ?array $references = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getChatId(): ?string
    {
        return $this->chatId;
    }

    public function setChatId(?string $chatId): void
    {
        $this->chatId = $chatId;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function setSessionId(?string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    public function getUserMessage(): ?string
    {
        return $this->userMessage;
    }

    public function setUserMessage(?string $userMessage): void
    {
        $this->userMessage = $userMessage;
    }

    public function getAssistantMessage(): ?string
    {
        return $this->assistantMessage;
    }

    public function setAssistantMessage(?string $assistantMessage): void
    {
        $this->assistantMessage = $assistantMessage;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): void
    {
        $this->role = $role;
    }

    public function getMessageCount(): ?int
    {
        return $this->messageCount;
    }

    public function setMessageCount(?int $messageCount): void
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

    public function getResponseTime(): ?float
    {
        return $this->responseTime;
    }

    public function setResponseTime(?float $responseTime): void
    {
        $this->responseTime = $responseTime;
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

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getReferences(): ?array
    {
        return $this->references;
    }

    /**
     * @param array<int, array<string, mixed>>|null $references
     */
    public function setReferences(?array $references): void
    {
        $this->references = $references;
    }

    public function __toString(): string
    {
        return $this->id ?? $this->sessionId ?? '(new)';
    }
}
