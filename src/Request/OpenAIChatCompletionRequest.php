<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Request;

use HttpClientBundle\Request\AutoRetryRequest;

class OpenAIChatCompletionRequest extends BaseRAGFlowRequest implements AutoRetryRequest
{
    public function __construct(
        private readonly string $chatId,
        private readonly string $model,
        /** @var array<int, array<string, mixed>> */
        private readonly array $messages,
        private readonly bool $stream = false,
        /** @var array<string, mixed>|null */
        private readonly ?array $options = null,
    ) {
    }

    public function getRequestPath(): string
    {
        return sprintf('/api/v1/chats_openai/%s/chat/completions', $this->chatId);
    }

    /** @return array<string, mixed>|null */
    public function getRequestOptions(): ?array
    {
        $data = [
            'model' => $this->model,
            'messages' => $this->messages,
            'stream' => $this->stream,
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
