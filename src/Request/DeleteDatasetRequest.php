<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Request;

class DeleteDatasetRequest extends BaseRAGFlowRequest
{
    public function __construct(
        private readonly string $datasetId,
    ) {
    }

    public function getRequestPath(): string
    {
        return sprintf('/api/v1/datasets/%s', $this->datasetId);
    }

    public function getRequestMethod(): ?string
    {
        return 'DELETE';
    }

    public function getRequestOptions(): ?array
    {
        return null;
    }
}
