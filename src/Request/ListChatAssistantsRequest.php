<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Request;

use HttpClientBundle\Request\AutoRetryRequest;

class ListChatAssistantsRequest extends BaseRAGFlowRequest implements AutoRetryRequest
{
    public function __construct(
        private readonly int $page = 1,
        private readonly int $pageSize = 30,
        private readonly ?string $orderby = null,
        private readonly bool $desc = true,
        private readonly ?string $id = null,
        private readonly ?string $name = null,
    ) {
    }

    public function getRequestPath(): string
    {
        $path = '/api/v1/chats';

        $queryParams = [];
        if ($this->page > 1) {
            $queryParams['page'] = (string) $this->page;
        }
        if (30 !== $this->pageSize) {
            $queryParams['page_size'] = (string) $this->pageSize;
        }
        if (null !== $this->orderby) {
            $queryParams['orderby'] = $this->orderby;
        }
        if (!$this->desc) {
            $queryParams['desc'] = 'false';
        }
        if (null !== $this->id) {
            $queryParams['id'] = $this->id;
        }
        if (null !== $this->name) {
            $queryParams['name'] = $this->name;
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
