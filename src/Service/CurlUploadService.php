<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;

/**
 * 使用原生curl处理文件上传的服务
 */
class CurlUploadService
{
    /**
     * @return array<string, mixed>
     */
    public function uploadDocument(
        RAGFlowInstance $instance,
        string $datasetId,
        string $filePath,
        string $filename,
    ): array {
        $this->validateFile($filePath);
        $url = $this->buildUploadUrl($instance, $datasetId);
        $apiKey = $instance->decryptApiKey($instance->getApiKey());

        $ch = $this->prepareCurlRequest($url, $filePath, $filename, $apiKey);
        $response = $this->executeCurlRequest($ch);

        return $this->parseAndValidateResponse($response);
    }

    private function validateFile(string $filePath): void
    {
        if (!is_file($filePath)) {
            throw new \InvalidArgumentException("文件不存在: {$filePath}");
        }
    }

    private function buildUploadUrl(RAGFlowInstance $instance, string $datasetId): string
    {
        $baseUrl = rtrim($instance->getApiUrl(), '/');

        return "{$baseUrl}/api/v1/datasets/{$datasetId}/documents";
    }

    /**
     * @return \CurlHandle
     */
    private function prepareCurlRequest(string $url, string $filePath, string $filename, string $apiKey): \CurlHandle
    {
        $mimeType = mime_content_type($filePath);
        $curlFile = new \CURLFile(
            $filePath,
            false !== $mimeType ? $mimeType : 'application/octet-stream',
            $filename
        );

        $ch = curl_init();
        if (false === $ch) {
            throw new \RuntimeException('Failed to initialize cURL');
        }

        assert('' !== $url);
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => ['file' => $curlFile],
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiKey],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ];

        curl_setopt_array($ch, $options);

        return $ch;
    }

    /**
     * @param \CurlHandle $ch
     * @return array{response: string, httpCode: int}
     */
    private function executeCurlRequest(\CurlHandle $ch): array
    {
        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if (false === $response || '' !== $error) {
            throw new \RuntimeException("Curl请求失败: {$error}");
        }

        if ($httpCode >= 400) {
            $this->handleHttpError($response, $httpCode);
        }

        return ['response' => is_string($response) ? $response : '', 'httpCode' => $httpCode];
    }

    /**
     * @param mixed $response
     */
    private function handleHttpError($response, int $httpCode): void
    {
        $responseStr = is_string($response) ? $response : '';
        $responseData = json_decode($responseStr, true);
        $errorMsg = is_array($responseData) && isset($responseData['message']) && is_string($responseData['message'])
            ? $responseData['message']
            : "HTTP {$httpCode}";
        throw new \RuntimeException("上传失败: {$errorMsg}");
    }

    /**
     * @param array{response: string, httpCode: int} $response
     * @return array<string, mixed>
     */
    private function parseAndValidateResponse(array $response): array
    {
        $responseData = json_decode($response['response'], true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \RuntimeException('响应JSON解析失败: ' . json_last_error_msg());
        }

        if (!is_array($responseData)) {
            throw new \RuntimeException('响应格式错误：期望JSON对象');
        }

        if (!isset($responseData['code']) || 0 !== $responseData['code']) {
            $errorMsg = isset($responseData['message']) && is_string($responseData['message'])
                ? $responseData['message']
                : '上传失败';
            throw new \RuntimeException("RAGFlow API错误: {$errorMsg}");
        }

        /** @var array<string, mixed> $responseData */
        return $responseData;
    }
}
