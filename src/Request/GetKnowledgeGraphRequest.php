<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Request;

class GetKnowledgeGraphRequest extends BaseRAGFlowRequest
{
    public function __construct(
        private readonly string $datasetId,
    ) {
    }

    public function getRequestPath(): string
    {
        return sprintf('/api/v1/datasets/%s/knowledge_graph', $this->datasetId);
    }

    public function getRequestMethod(): ?string
    {
        return 'GET';
    }

    public function getRequestOptions(): ?array
    {
        return null;
    }
}
