<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Request;

use HttpClientBundle\Request\AutoRetryRequest;

class RetrieveChunksRequest extends BaseRAGFlowRequest implements AutoRetryRequest
{
    public function __construct(
        private readonly string $docId,
        /** @var array<string, mixed>|null */
        private readonly ?array $options = null,
    ) {
    }

    public function getRequestPath(): string
    {
        return '/api/v1/list_chunks';
    }

    public function getRequestMethod(): ?string
    {
        return 'GET';
    }

    /** @return array<string, mixed>|null */
    public function getRequestOptions(): ?array
    {
        $queryParams = [
            'doc_id' => $this->docId,
        ];

        if (null !== $this->options) {
            $queryParams = array_merge($queryParams, $this->options);
        }

        return [
            'query' => $queryParams,
        ];
    }

    public function getMaxRetries(): int
    {
        return 3;
    }
}
