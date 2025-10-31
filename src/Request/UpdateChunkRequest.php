<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Request;

use HttpClientBundle\Request\AutoRetryRequest;

class UpdateChunkRequest extends BaseRAGFlowRequest implements AutoRetryRequest
{
    public function __construct(
        private readonly string $datasetId,
        private readonly string $chunkId,
        /** @var array<string, mixed> */
        private readonly array $content,
    ) {
    }

    public function getRequestPath(): string
    {
        return sprintf('/api/v1/datasets/%s/chunks/%s', $this->datasetId, $this->chunkId);
    }

    public function getRequestMethod(): ?string
    {
        return 'PUT';
    }

    /** @return array<string, mixed>|null */
    public function getRequestOptions(): ?array
    {
        return [
            'json' => $this->content,
        ];
    }

    public function getMaxRetries(): int
    {
        return 3;
    }
}
