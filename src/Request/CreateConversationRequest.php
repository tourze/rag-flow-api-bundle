<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Request;

use HttpClientBundle\Request\AutoRetryRequest;

class CreateConversationRequest extends BaseRAGFlowRequest implements AutoRetryRequest
{
    public function __construct(
        private readonly string $name,
        /** @var array<string> */
        private readonly array $datasetIds = [],
        /** @var array<string, mixed>|null */
        private readonly ?array $options = null,
    ) {
    }

    public function getRequestPath(): string
    {
        return '/api/v1/chats';
    }

    /** @return array<string, mixed>|null */
    public function getRequestOptions(): ?array
    {
        $data = [
            'name' => $this->name,
        ];

        if ([] !== $this->datasetIds) {
            $data['dataset_ids'] = $this->datasetIds;
        }

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
