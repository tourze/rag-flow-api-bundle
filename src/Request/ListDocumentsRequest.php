<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Request;

use HttpClientBundle\Request\AutoRetryRequest;

class ListDocumentsRequest extends BaseRAGFlowRequest implements AutoRetryRequest
{
    public function __construct(
        private readonly string $datasetId,
        /** @var array<string, mixed>|null */
        private readonly ?array $filters = null,
    ) {
    }

    public function getRequestPath(): string
    {
        return sprintf('/api/v1/datasets/%s/documents', $this->datasetId);
    }

    public function getRequestMethod(): ?string
    {
        return 'GET';
    }

    /** @return array<string, mixed>|null */
    public function getRequestOptions(): ?array
    {
        if (null === $this->filters) {
            return null;
        }

        return [
            'query' => $this->filters,
        ];
    }

    public function getMaxRetries(): int
    {
        return 3;
    }
}
