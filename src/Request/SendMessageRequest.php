<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Request;

use HttpClientBundle\Request\AutoRetryRequest;

class SendMessageRequest extends BaseRAGFlowRequest implements AutoRetryRequest
{
    public function __construct(
        private readonly string $chatId,
        private readonly string $question,
        /** @var array<string, mixed>|null */
        private readonly ?array $options = null,
    ) {
    }

    public function getRequestPath(): string
    {
        return sprintf('/api/v1/chats/%s/completions', $this->chatId);
    }

    /** @return array<string, mixed>|null */
    public function getRequestOptions(): ?array
    {
        $data = [
            'question' => $this->question,
        ];

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
