<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Request;

use HttpClientBundle\Request\AutoRetryRequest;
use HttpClientBundle\Request\CacheRequest;

class CreateDatasetRequest extends BaseRAGFlowRequest implements CacheRequest
{
    public function __construct(
        /** @var array<string, mixed> */
        private readonly array $config,
    ) {
    }

    public function getRequestPath(): string
    {
        return '/api/v1/datasets';
    }

    /** @return array<string, mixed>|null */
    public function getRequestOptions(): ?array
    {
        return [
            'json' => $this->config,
        ];
    }

    public function getCacheKey(): string
    {
        $json = json_encode($this->config);

        return 'ragflow-create-dataset-' . md5(false !== $json ? $json : '{}');
    }

    public function getCacheDuration(): int
    {
        return 0;
    }
}
