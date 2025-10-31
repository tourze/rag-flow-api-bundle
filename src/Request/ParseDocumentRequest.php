<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Request;

use HttpClientBundle\Request\AutoRetryRequest;

class ParseDocumentRequest extends BaseRAGFlowRequest implements AutoRetryRequest
{
    public function __construct(
        private readonly string $datasetId,
        private readonly string $documentId,
        /** @var array<string, mixed>|null */
        private readonly ?array $options = null,
    ) {
    }

    public function getRequestPath(): string
    {
        return sprintf('/api/v1/datasets/%s/documents/%s/parse', $this->datasetId, $this->documentId);
    }

    /** @return array<string, mixed>|null */
    public function getRequestOptions(): ?array
    {
        if (null === $this->options) {
            return null;
        }

        return [
            'json' => $this->options,
        ];
    }

    public function getMaxRetries(): int
    {
        return 3;
    }
}
