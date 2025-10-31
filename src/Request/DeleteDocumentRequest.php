<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Request;

use HttpClientBundle\Request\AutoRetryRequest;

class DeleteDocumentRequest extends BaseRAGFlowRequest implements AutoRetryRequest
{
    public function __construct(
        private readonly string $datasetId,
        private readonly string $documentId,
    ) {
    }

    public function getRequestPath(): string
    {
        return sprintf('/api/v1/datasets/%s/documents/%s', $this->datasetId, $this->documentId);
    }

    public function getRequestMethod(): ?string
    {
        return 'DELETE';
    }

    public function getRequestOptions(): ?array
    {
        return null;
    }

    public function getMaxRetries(): int
    {
        return 3;
    }
}
