<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Tourze\RAGFlowApiBundle\Client\RAGFlowApiClient;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Repository\DatasetRepository;
use Tourze\RAGFlowApiBundle\Request\DeleteDocumentRequest;
use Tourze\RAGFlowApiBundle\Request\GetParseStatusRequest;
use Tourze\RAGFlowApiBundle\Request\ListChunksRequest;
use Tourze\RAGFlowApiBundle\Request\ListDocumentsRequest;
use Tourze\RAGFlowApiBundle\Request\ParseChunksRequest;
use Tourze\RAGFlowApiBundle\Request\ParseDocumentRequest;
use Tourze\RAGFlowApiBundle\Request\StopParsingRequest;
use Tourze\RAGFlowApiBundle\Request\UploadDocumentRequest;
use Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManagerInterface;

class DocumentService
{
    public function __construct(
        private readonly RAGFlowInstanceManagerInterface $instanceManager,
        private readonly LocalDataSyncService $localDataSyncService,
        private readonly DatasetRepository $datasetRepository,
        private readonly CurlUploadService $curlUploadService,
    ) {
    }

    private function getClient(): RAGFlowApiClient
    {
        $client = $this->instanceManager->getDefaultClient();
        assert($client instanceof RAGFlowApiClient);

        return $client;
    }

    /**
     * @param array<string, string> $files 文件路径数组
     * @param array<string, string>|null $displayNames 可选的显示名称数组
     * @return array<string, mixed>
     */
    public function upload(string $datasetId, array $files, ?array $displayNames = null): array
    {
        $instance = $this->getClient()->getInstance();
        $isBatch = count($files) > 1;
        $results = [];

        foreach ($files as $key => $filePath) {
            $filename = $this->resolveFilename($displayNames, $key, $filePath);
            $document = $this->processSingleUpload($instance, $datasetId, $filePath, $filename, $isBatch);

            if (null !== $document) {
                $results[] = $document;
            }
        }

        return [
            'retcode' => 0,
            'retmsg' => 'success',
            'data' => $results,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return Document[]
     */
    public function list(string $datasetId, array $filters = []): array
    {
        $request = new ListDocumentsRequest($datasetId, $filters);
        $apiResponse = $this->getClient()->request($request);

        $dataset = $this->getLocalDataset($datasetId);
        $documentList = $this->extractDocumentList($apiResponse);

        return $this->syncDocumentsFromApiResponse($dataset, $documentList);
    }

    /**
     * 从API响应中提取文档列表
     *
     * @param mixed $apiResponse
     * @return array<mixed>
     */
    private function extractDocumentList(mixed $apiResponse): array
    {
        if (!is_array($apiResponse)) {
            return [];
        }

        if (isset($apiResponse['docs']) && is_array($apiResponse['docs'])) {
            return $apiResponse['docs'];
        }

        if (isset($apiResponse['data']) && is_array($apiResponse['data'])) {
            return $apiResponse['data'];
        }

        return $apiResponse;
    }

    /**
     * 同步文档数据到本地
     *
     * @param array<mixed> $documentList
     * @return Document[]
     */
    private function syncDocumentsFromApiResponse(?Dataset $dataset, array $documentList): array
    {
        if (null === $dataset) {
            return [];
        }

        $documents = [];
        foreach ($documentList as $documentData) {
            if (!is_array($documentData)) {
                continue;
            }

            /** @var array<string, mixed> $typedDocumentData */
            $typedDocumentData = $documentData;
            $documents[] = $this->localDataSyncService->syncDocumentFromApi(
                $dataset,
                $typedDocumentData
            );
        }

        return $documents;
    }

    /**
     * 获取所有数据集的文档列表
     *
     * @return Document[]
     */
    public function listAll(): array
    {
        $instance = $this->getClient()->getInstance();
        $datasets = $this->datasetRepository->findBy(['ragFlowInstance' => $instance]);

        $allDocuments = [];
        foreach ($datasets as $dataset) {
            $remoteId = $dataset->getRemoteId();
            if (null === $remoteId) {
                continue;
            }

            try {
                $documents = $this->list($remoteId);
                $allDocuments = array_merge($allDocuments, $documents);
            } catch (\Exception $e) {
                // 记录错误但继续处理其他数据集
                error_log(sprintf('Failed to list documents for dataset %s: %s', $remoteId, $e->getMessage()));
            }
        }

        return $allDocuments;
    }

    public function delete(string $datasetId, string $documentId): bool
    {
        $request = new DeleteDocumentRequest($datasetId, $documentId);
        $this->getClient()->request($request);

        return true;
    }

    /**
     * @param array<string, mixed>|null $options
     * @return array<string, mixed>
     */
    public function parse(string $datasetId, string $documentId, ?array $options = null): array
    {
        $request = new ParseDocumentRequest($datasetId, $documentId, $options);
        $result = $this->getClient()->request($request);
        if (!is_array($result)) {
            return [];
        }

        /** @var array<string, mixed> $result */
        return $result;
    }

    /**
     * 使用RAGFlow chunks API解析文档
     * @param string $datasetId
     * @param string[] $documentIds
     * @param array<string, mixed>|null $parserConfig
     * @return array<string, mixed>
     */
    public function parseChunks(string $datasetId, array $documentIds, ?array $parserConfig = null): array
    {
        $request = new ParseChunksRequest($datasetId, $documentIds, $parserConfig);
        $result = $this->getClient()->request($request);
        if (!is_array($result)) {
            return [];
        }

        /** @var array<string, mixed> $result */
        return $result;
    }

    /**
     * 使用RAGFlow chunks API停止解析文档
     * @param string $datasetId
     * @param string[] $documentIds
     * @return array<string, mixed>
     */
    public function stopParsing(string $datasetId, array $documentIds): array
    {
        $request = new StopParsingRequest($datasetId, $documentIds);
        $result = $this->getClient()->request($request);
        if (!is_array($result)) {
            return [];
        }

        /** @var array<string, mixed> $result */
        return $result;
    }

    /** @return array<string, mixed> */
    public function getParseStatus(string $datasetId, string $documentId): array
    {
        $request = new GetParseStatusRequest($datasetId, $documentId);
        $result = $this->getClient()->request($request);
        if (!is_array($result)) {
            return [];
        }

        /** @var array<string, mixed> $result */
        return $result;
    }

    /**
     * 获取文档的所有分块
     * @param string $datasetId
     * @param string $documentId
     * @param string|null $keywords
     * @param int $page
     * @param int $pageSize
     * @return array<string, mixed>
     */
    public function listChunks(string $datasetId, string $documentId, ?string $keywords = null, int $page = 1, int $pageSize = 100): array
    {
        $request = new ListChunksRequest($datasetId, $documentId, $keywords, $page, $pageSize);
        $result = $this->getClient()->request($request);
        if (!is_array($result)) {
            return [];
        }

        /** @var array<string, mixed> $result */
        return $result;
    }

    private function getLocalDataset(string $remoteDatasetId): Dataset
    {
        $dataset = $this->datasetRepository->findOneBy([
            'remoteId' => $remoteDatasetId,
            'ragFlowInstance' => $this->getClient()->getInstance(),
        ]);

        if (null === $dataset) {
            throw new \RuntimeException(sprintf('Local dataset not found for remote ID: %s', $remoteDatasetId));
        }

        return $dataset;
    }

    /**
     * @param array<string, string>|null $displayNames
     */
    private function resolveFilename(?array $displayNames, int|string $key, string $filePath): string
    {
        if (null !== $displayNames && isset($displayNames[$key])) {
            return $displayNames[$key];
        }

        return basename($filePath);
    }

    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function normalizeUploadResponse(array $result): array
    {
        if (!isset($result['data'])) {
            return $result;
        }

        if (!is_array($result['data'])) {
            return $result;
        }

        if (0 === count($result['data'])) {
            return $result;
        }

        $firstElement = $result['data'][0];
        if (!is_array($firstElement)) {
            return $result;
        }

        /** @var array<string, mixed> $firstElement */
        return $firstElement;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function processSingleUpload(
        RAGFlowInstance $instance,
        string $datasetId,
        string $filePath,
        string $filename,
        bool $isBatch,
    ): ?array {
        try {
            $result = $this->curlUploadService->uploadDocument(
                $instance,
                $datasetId,
                $filePath,
                $filename
            );

            return $this->normalizeUploadResponse($result);
        } catch (\Exception $e) {
            if ($isBatch) {
                error_log("文件上传失败 [{$filename}]: " . $e->getMessage());

                return null;
            }

            throw $e;
        }
    }
}
