<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Request;

class HealthCheckRequest extends BaseRAGFlowRequest
{
    public function getRequestPath(): string
    {
        return '/api/v1/health';
    }

    public function getRequestMethod(): ?string
    {
        return 'GET';
    }

    public function getRequestOptions(): ?array
    {
        return null;
    }
}
