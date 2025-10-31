<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Request;

use HttpClientBundle\Request\AutoRetryRequest;

class ParseChunksRequest extends BaseRAGFlowRequest implements AutoRetryRequest
{
    /**
     * @param string $datasetId
     * @param string[] $documentIds
     * @param array<string, mixed>|null $parserConfig
     */
    public function __construct(
        private readonly string $datasetId,
        private readonly array $documentIds,
        private readonly ?array $parserConfig = null,
    ) {
    }

    public function getRequestPath(): string
    {
        return sprintf('/api/v1/datasets/%s/chunks', $this->datasetId);
    }

    /** @return array<string, mixed> */
    public function getRequestOptions(): array
    {
        $requestData = [
            'document_ids' => $this->documentIds,
        ];

        if (null !== $this->parserConfig) {
            $requestData['parser_config'] = $this->parserConfig;
        }

        return [
            'json' => $requestData,
        ];
    }

    public function getMaxRetries(): int
    {
        return 3;
    }
}
