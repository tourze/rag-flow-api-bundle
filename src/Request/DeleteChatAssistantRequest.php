<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Request;

use HttpClientBundle\Request\AutoRetryRequest;

class DeleteChatAssistantRequest extends BaseRAGFlowRequest implements AutoRetryRequest
{
    public function __construct(
        private readonly string $chatId,
    ) {
    }

    public function getRequestPath(): string
    {
        return sprintf('/api/v1/chats/%s', $this->chatId);
    }

    public function getRequestMethod(): ?string
    {
        return 'DELETE';
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
