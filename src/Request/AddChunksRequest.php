<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Request;

use HttpClientBundle\Request\AutoRetryRequest;

class AddChunksRequest extends BaseRAGFlowRequest implements AutoRetryRequest
{
    public function __construct(
        private readonly string $datasetId,
        /** @var array<int, array<string, mixed>> */
        private readonly array $chunks,
        /** @var array<string, mixed>|null */
        private readonly ?array $options = null,
    ) {
    }

    public function getRequestPath(): string
    {
        return sprintf('/api/v1/datasets/%s/chunks', $this->datasetId);
    }

    /** @return array<string, mixed>|null */
    public function getRequestOptions(): ?array
    {
        $data = [
            'chunks' => $this->chunks,
        ];

        if (null !== $this->options) {
            $data = array_merge($data, $this->options);
        }

        return [
            'json' => $data,
        ];
    }

    public function getMaxRetries(): int
    {
        return 3;
    }
}
