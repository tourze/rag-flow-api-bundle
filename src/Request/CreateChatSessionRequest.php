<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Request;

use HttpClientBundle\Request\AutoRetryRequest;

class CreateChatSessionRequest extends BaseRAGFlowRequest implements AutoRetryRequest
{
    public function __construct(
        private readonly string $chatId,
        /** @var array<string, mixed>|null */
        private readonly ?array $options = null,
    ) {
    }

    public function getRequestPath(): string
    {
        return sprintf('/api/v1/chats/%s/sessions', $this->chatId);
    }

    /** @return array<string, mixed>|null */
    public function getRequestOptions(): ?array
    {
        $data = [];

        if (null !== $this->options) {
            $data = array_merge($data, $this->options);
        }

        return [
            'json' => $data,
        ];
    }

    public function getMaxRetries(): int
    {
        return 3;
    }
}
