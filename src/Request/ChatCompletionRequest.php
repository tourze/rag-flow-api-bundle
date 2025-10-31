<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Request;

use HttpClientBundle\Request\AutoRetryRequest;

class ChatCompletionRequest extends BaseRAGFlowRequest implements AutoRetryRequest
{
    public function __construct(
        private readonly string $chatId,
        /** @var array<int, array<string, mixed>> */
        private readonly array $messages,
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
            'question' => $this->extractQuestionFromMessages(),
        ];

        if (null !== $this->options) {
            $data = array_merge($data, $this->options);
        }

        return [
            'json' => $data,
        ];
    }

    private function extractQuestionFromMessages(): string
    {
        foreach ($this->messages as $message) {
            if (isset($message['role']) && 'user' === $message['role'] && isset($message['content'])) {
                if (!is_string($message['content'])) {
                    continue;
                }

                return $message['content'];
            }
        }

        return '';
    }

    public function getMaxRetries(): int
    {
        return 3;
    }
}
