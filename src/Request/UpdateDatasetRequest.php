<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Request;

class UpdateDatasetRequest extends BaseRAGFlowRequest
{
    public function __construct(
        private readonly string $datasetId,
        /** @var array<string, mixed> */
        private readonly array $config,
    ) {
    }

    public function getRequestPath(): string
    {
        return sprintf('/api/v1/datasets/%s', $this->datasetId);
    }

    public function getRequestMethod(): ?string
    {
        return 'PUT';
    }

    /** @return array<string, mixed>|null */
    public function getRequestOptions(): ?array
    {
        return [
            'json' => $this->config,
        ];
    }
}
