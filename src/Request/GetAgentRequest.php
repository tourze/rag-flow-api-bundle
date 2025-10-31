<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Request;

class GetAgentRequest extends BaseRAGFlowRequest
{
    public function __construct(
        private readonly string $agentId,
    ) {
    }

    public function getRequestPath(): string
    {
        return "/api/v1/agents/{$this->agentId}";
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
        return [];
    }
}
