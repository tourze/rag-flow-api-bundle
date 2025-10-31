<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Request;

use HttpClientBundle\Request\AutoRetryRequest;

class UpdateChatAssistantRequest extends BaseRAGFlowRequest implements AutoRetryRequest
{
    /**
     * @param string $chatId
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly string $chatId,
        private readonly array $data,
    ) {
    }

    public function getRequestPath(): string
    {
        return sprintf('/api/v1/chats/%s', $this->chatId);
    }

    public function getRequestMethod(): ?string
    {
        return 'PUT';
    }

    /** @return array<string, mixed> */
    public function getRequestOptions(): array
    {
        return [
            'json' => $this->data,
        ];
    }

    public function getMaxRetries(): int
    {
        return 3;
    }
}
