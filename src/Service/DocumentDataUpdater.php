<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Tourze\RAGFlowApiBundle\Entity\Document;

/**
 * 文档数据更新器
 *
 * 使用建造者模式链式调用更新文档字段
 */
final class DocumentDataUpdater
{
    public function __construct(
        private readonly Document $document,
    ) {
    }

    /**
     * 更新名称
     */
    public function updateName(?string $name): self
    {
        if (null !== $name) {
            $this->document->setName($name);
        }

        return $this;
    }

    /**
     * 更新摘要
     */
    public function updateSummary(?string $summary): self
    {
        if (null !== $summary) {
            $this->document->setSummary($summary);
        }

        return $this;
    }

    /**
     * 更新语言
     */
    public function updateLanguage(?string $language): self
    {
        if (null !== $language) {
            $this->document->setLanguage($language);
        }

        return $this;
    }
}
