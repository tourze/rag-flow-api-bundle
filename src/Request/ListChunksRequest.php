<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Request;

use HttpClientBundle\Request\AutoRetryRequest;

class ListChunksRequest extends BaseRAGFlowRequest implements AutoRetryRequest
{
    public function __construct(
        private readonly string $datasetId,
        private readonly string $documentId,
        private readonly ?string $keywords = null,
        private readonly int $page = 1,
        private readonly int $pageSize = 1024,
        private readonly ?string $id = null,
    ) {
    }

    public function getRequestPath(): string
    {
        $path = sprintf('/api/v1/datasets/%s/documents/%s/chunks', $this->datasetId, $this->documentId);

        $queryParams = [];
        if (null !== $this->keywords) {
            $queryParams['keywords'] = $this->keywords;
        }
        if ($this->page > 0) {
            $queryParams['page'] = (string) $this->page;
        }
        if ($this->pageSize > 0) {
            $queryParams['page_size'] = (string) $this->pageSize;
        }
        if (null !== $this->id) {
            $queryParams['id'] = $this->id;
        }

        if ([] !== $queryParams) {
            $path .= '?' . http_build_query($queryParams);
        }

        return $path;
    }

    public function getRequestMethod(): ?string
    {
        return 'GET';
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
