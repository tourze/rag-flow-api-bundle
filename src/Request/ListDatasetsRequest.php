<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Request;

use HttpClientBundle\Request\CacheRequest;

class ListDatasetsRequest extends BaseRAGFlowRequest implements CacheRequest
{
    public function __construct(
        /** @var array<string, mixed> */
        private readonly array $filters = [],
    ) {
    }

    public function getRequestPath(): string
    {
        return '/api/v1/datasets';
    }

    public function getRequestMethod(): ?string
    {
        return 'GET';
    }

    /** @return array<string, mixed>|null */
    public function getRequestOptions(): ?array
    {
        return [
            'query' => $this->filters,
        ];
    }

    public function getCacheKey(): string
    {
        $json = json_encode($this->filters);

        return 'ragflow-list-datasets-' . md5(false !== $json ? $json : '{}');
    }

    public function getCacheDuration(): int
    {
        return 300;
    }
}
