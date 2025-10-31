<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Request;

class CreateAgentRequest extends BaseRAGFlowRequest
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly array $data,
    ) {
    }

    public function getRequestPath(): string
    {
        return '/api/v1/agents';
    }

    public function getRequestMethod(): ?string
    {
        return 'POST';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRequestOptions(): ?array
    {
        return [
            'json' => $this->data,
        ];
    }
}
