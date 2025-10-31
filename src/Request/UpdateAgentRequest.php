<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Request;

class UpdateAgentRequest extends BaseRAGFlowRequest
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly string $agentId,
        private readonly array $data,
    ) {
    }

    public function getRequestPath(): string
    {
        return "/api/v1/agents/{$this->agentId}";
    }

    public function getRequestMethod(): ?string
    {
        return 'PUT';
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
