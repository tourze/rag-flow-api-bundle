<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Request;

use HttpClientBundle\Request\ApiRequest;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;

class UploadDocumentRequest extends BaseRAGFlowRequest
{
    public function __construct(
        private readonly string $datasetId,
        /** @var array<int, string> */
        private readonly array $files,
        /** @var array<int, string>|null */
        private readonly ?array $displayNames = null,
        private readonly ?RAGFlowInstance $instance = null,
    ) {
    }

    public function getRequestPath(): string
    {
        return sprintf('/api/v1/datasets/%s/documents', $this->datasetId);
    }

    /** @return array<string, mixed>|null */
    public function getRequestOptions(): ?array
    {
        $multipart = [];

        foreach ($this->files as $index => $file) {
            if (!is_file($file)) {
                throw new \InvalidArgumentException(sprintf('File not found: %s', $file));
            }

            $filename = (null !== $this->displayNames && isset($this->displayNames[$index]))
                ? $this->displayNames[$index]
                : basename($file);

            $multipart[] = [
                'name' => sprintf('file[%d]', $index),
                'filename' => $filename,
                'contents' => fopen($file, 'r'),
            ];
        }

        $options = ['multipart' => $multipart];

        if (null !== $this->instance) {
            $options['headers'] = [
                'Authorization' => 'Bearer ' . $this->instance->decryptApiKey($this->instance->getApiKey()),
            ];
        }

        return $options;
    }

    public function generateLogData(): ?array
    {
        return [
            '_className' => self::class,
            'path' => $this->getRequestPath(),
            'method' => $this->getRequestMethod(),
            'files' => array_values($this->files),
            'displayNames' => $this->displayNames,
            'fileCount' => count($this->files),
        ];
    }
}
