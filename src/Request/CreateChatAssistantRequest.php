<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Request;

use HttpClientBundle\Request\AutoRetryRequest;

class CreateChatAssistantRequest extends BaseRAGFlowRequest implements AutoRetryRequest
{
    /**
     * @param array<string> $datasetIds
     * @param array<string, mixed>|null $llm
     * @param array<string, mixed>|null $prompt
     */
    public function __construct(
        private readonly string $name,
        private readonly array $datasetIds,
        private readonly ?string $avatar = null,
        private readonly ?array $llm = null,
        private readonly ?array $prompt = null,
    ) {
    }

    public function getRequestPath(): string
    {
        return '/api/v1/chats';
    }

    public function getRequestMethod(): ?string
    {
        return 'POST';
    }

    /** @return array<string, mixed> */
    public function getRequestOptions(): array
    {
        $data = [
            'name' => $this->name,
            'dataset_ids' => $this->datasetIds,
        ];

        if (null !== $this->avatar) {
            $data['avatar'] = $this->avatar;
        }

        if (null !== $this->llm) {
            $data['llm'] = $this->llm;
        }

        if (null !== $this->prompt) {
            $data['prompt'] = $this->prompt;
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
