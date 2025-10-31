<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Request;

use HttpClientBundle\Request\AutoRetryRequest;

class StopParsingRequest extends BaseRAGFlowRequest implements AutoRetryRequest
{
    /**
     * @param string $datasetId
     * @param string[] $documentIds
     */
    public function __construct(
        private readonly string $datasetId,
        private readonly array $documentIds,
    ) {
    }

    public function getRequestPath(): string
    {
        return sprintf('/api/v1/datasets/%s/chunks', $this->datasetId);
    }

    public function getRequestMethod(): ?string
    {
        return 'DELETE';
    }

    /** @return array<string, mixed> */
    public function getRequestOptions(): array
    {
        return [
            'json' => [
                'document_ids' => $this->documentIds,
            ],
        ];
    }

    public function getMaxRetries(): int
    {
        return 3;
    }
}
