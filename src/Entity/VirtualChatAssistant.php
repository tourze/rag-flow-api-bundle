<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

/**
 * 虚拟聊天助手实体类
 *
 * 这是一个用于EasyAdmin CRUD界面的虚拟Entity，不对应数据库表
 * 实际的数据操作通过RAGFlow API进行
 */
#[ORM\Entity(readOnly: true)]
#[ORM\Table(name: 'virtual_chat_assistant', options: ['comment' => '虚拟聊天助手表'])]
class VirtualChatAssistant implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\Column(name: 'id', type: Types::STRING, length: 255, options: ['comment' => '主键ID'])]
    private ?string $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255, nullable: true, options: ['comment' => '名称'])]
    #[Assert\Length(max: 255)]
    private ?string $name = null;

    #[Assert\Length(max: 2000)]
    private ?string $description = null;

    /** @var array<string>|null */
    #[Assert\Type(type: 'array')]
    private ?array $datasetIds = null;

    #[Assert\Length(max: 10000)]
    private ?string $systemPrompt = null;

    #[Assert\Length(max: 255)]
    private ?string $model = null;

    #[Assert\Range(min: 0, max: 2)]
    private ?float $temperature = null;

    #[Assert\Positive]
    private ?int $maxTokens = null;

    #[Assert\Range(min: 0, max: 1)]
    private ?float $topP = null;

    #[Assert\PositiveOrZero]
    private ?float $topK = null;

    #[Assert\Length(max: 10)]
    private ?string $language = null;

    #[Assert\Type(type: 'bool')]
    private ?bool $isActive = null;

    #[Assert\PositiveOrZero]
    private ?int $sessionCount = null;

    #[Assert\PositiveOrZero]
    private ?int $messageCount = null;

    #[Assert\Length(max: 50)]
    private ?string $lastUsedAt = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
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

    /**
     * @return array<string>|null
     */
    public function getDatasetIds(): ?array
    {
        return $this->datasetIds;
    }

    /**
     * @param array<string>|null $datasetIds
     */
    public function setDatasetIds(?array $datasetIds): void
    {
        $this->datasetIds = $datasetIds;
    }

    public function getSystemPrompt(): ?string
    {
        return $this->systemPrompt;
    }

    public function setSystemPrompt(?string $systemPrompt): void
    {
        $this->systemPrompt = $systemPrompt;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model): void
    {
        $this->model = $model;
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

    public function getTopP(): ?float
    {
        return $this->topP;
    }

    public function setTopP(?float $topP): void
    {
        $this->topP = $topP;
    }

    public function getTopK(): ?float
    {
        return $this->topK;
    }

    public function setTopK(?float $topK): void
    {
        $this->topK = $topK;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): void
    {
        $this->language = $language;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(?bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function getSessionCount(): ?int
    {
        return $this->sessionCount;
    }

    public function setSessionCount(?int $sessionCount): void
    {
        $this->sessionCount = $sessionCount;
    }

    public function getMessageCount(): ?int
    {
        return $this->messageCount;
    }

    public function setMessageCount(?int $messageCount): void
    {
        $this->messageCount = $messageCount;
    }

    public function getLastUsedAt(): ?string
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(?string $lastUsedAt): void
    {
        $this->lastUsedAt = $lastUsedAt;
    }

    public function __toString(): string
    {
        return $this->name ?? $this->id ?? '(new)';
    }
}
