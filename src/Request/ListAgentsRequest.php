<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Request;

class ListAgentsRequest extends BaseRAGFlowRequest
{
    public function __construct(
        private readonly int $page = 1,
        private readonly int $size = 20,
    ) {
    }

    public function getRequestPath(): string
    {
        return '/api/v1/agents';
    }

    public function getRequestMethod(): ?string
    {
        return 'GET';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRequestOptions(): ?array
    {
        return [
            'query' => [
                'page' => $this->page,
                'size' => $this->size,
            ],
        ];
    }
}
