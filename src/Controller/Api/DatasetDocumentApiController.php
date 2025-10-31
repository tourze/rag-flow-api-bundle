<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Repository\DocumentRepository;
use Tourze\RAGFlowApiBundle\Service\DocumentDeletionService;
use Tourze\RAGFlowApiBundle\Service\DocumentService;
use Tourze\RAGFlowApiBundle\Service\DocumentUploadService;
use Tourze\RAGFlowApiBundle\Service\DocumentValidationService;

/**
 * 数据集专用文档API Controller
 *
 * 提供符合RAGFlow API结构的数据集文档操作接口
 * 路径格式: /api/v1/datasets/{datasetId}/documents
 */
#[Route(path: '/api/v1/datasets/{datasetId}/documents', name: 'api_dataset_documents_')]
final class DatasetDocumentApiController extends AbstractController
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
        private readonly DocumentService $documentService,
        private readonly DocumentValidationService $validationService,
        private readonly DocumentUploadService $uploadService,
        private readonly DocumentDeletionService $deletionService,
    ) {
    }

    /**
     * 创建成功响应
     *
     * @param array<string, mixed> $data
     */
    private function successResponse(string $message, array $data = []): JsonResponse
    {
        return new JsonResponse([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c'),
        ]);
    }

    /**
     * 创建错误响应
     */
    private function errorResponse(string $message, string $error = '', int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR): JsonResponse
    {
        $data = [
            'status' => 'error',
            'message' => $message,
            'timestamp' => date('c'),
        ];

        if ('' !== $error) {
            $data['error'] = $error;
        }

        return new JsonResponse($data, $statusCode);
    }

    /**
     * 提取列表查询参数
     *
     * @return array{int, int, array<string, mixed>}
     */
    private function extractListParameters(Request $request, int $datasetId): array
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);

        $filters = ['dataset_id' => $datasetId];
        $filters = $this->addOptionalFilter($request, $filters, 'name');
        $filters = $this->addOptionalFilter($request, $filters, 'status');
        $filters = $this->addOptionalFilter($request, $filters, 'type');

        return [$page, $limit, $filters];
    }

    /**
     * 添加可选筛选条件
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function addOptionalFilter(Request $request, array $filters, string $key): array
    {
        $value = $request->query->get($key);
        if (null !== $value) {
            $filters[$key] = $value;
        }

        return $filters;
    }

    /**
     * 格式化文档数组
     *
     * @param Document[] $documents
     * @return array<int, array<string, mixed>>
     */
    private function formatDocuments(array $documents): array
    {
        $data = [];
        foreach ($documents as $document) {
            $data[] = $this->formatDocument($document);
        }

        return $data;
    }

    /**
     * 格式化单个文档
     *
     * @return array<string, mixed>
     */
    private function formatDocument(Document $document): array
    {
        return [
            'id' => $document->getId(),
            'remoteId' => $document->getRemoteId(),
            'name' => $document->getName(),
            'filename' => $document->getFilename(),
            'type' => $document->getType(),
            'size' => $document->getSize(),
            'status' => $document->getStatus(),
            'parseStatus' => $document->getParseStatus(),
            'language' => $document->getLanguage(),
            'chunkCount' => $document->getChunkCount(),
            'summary' => $document->getSummary(),
            'remoteCreateTime' => $document->getRemoteCreateTime()?->format('c'),
            'remoteUpdateTime' => $document->getRemoteUpdateTime()?->format('c'),
            'lastSyncTime' => $document->getLastSyncTime()?->format('c'),
            'createTime' => $document->getCreateTime()?->format('c'),
            'updateTime' => $document->getUpdateTime()?->format('c'),
        ];
    }

    /**
     * 格式化数据集信息
     *
     * @return array<string, mixed>
     */
    private function formatDatasetInfo(Dataset $dataset): array
    {
        return [
            'id' => $dataset->getId(),
            'name' => $dataset->getName(),
            'remoteId' => $dataset->getRemoteId(),
        ];
    }

    /**
     * 构建分页信息
     *
     * @return array<string, mixed>
     */
    private function buildPagination(int $page, int $limit, int $total): array
    {
        return [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => (int) ceil($total / $limit),
        ];
    }

    /**
     * 获取数据集中的文档列表
     */
    #[Route(path: '', name: 'list', methods: ['GET'])]
    public function list(int $datasetId, Request $request): JsonResponse
    {
        try {
            $dataset = $this->validationService->validateAndGetDataset($datasetId);

            [$page, $limit, $filters] = $this->extractListParameters($request, $datasetId);
            $result = $this->documentRepository->findWithFilters($filters, $page, $limit);

            $data = $this->formatDocuments($result['items']);

            return $this->successResponse('Documents retrieved successfully', [
                'documents' => $data,
                'dataset' => $this->formatDatasetInfo($dataset),
                'pagination' => $this->buildPagination($page, $limit, $result['total']),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve documents', $e->getMessage());
        }
    }

    /**
     * 上传文档到指定数据集
     */
    #[Route(path: '/upload', name: 'upload', methods: ['POST'])]
    public function upload(int $datasetId, Request $request): JsonResponse
    {
        try {
            $dataset = $this->validationService->validateAndGetDataset($datasetId);
            $uploadedFiles = $this->uploadService->extractUploadedFiles($request);

            [$uploadedDocuments, $errors] = $this->uploadService->processFileUploads($dataset, $uploadedFiles);

            return new JsonResponse([
                'status' => 'success',
                'message' => sprintf('Successfully uploaded %d documents', count($uploadedDocuments)),
                'data' => [
                    'uploaded' => $uploadedDocuments,
                    'errors' => $errors,
                    'dataset' => $this->formatDatasetInfo($dataset),
                ],
                'timestamp' => date('c'),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to upload documents', $e->getMessage());
        }
    }

    /**
     * 批量删除数据集中的文档
     */
    #[Route(path: '/batch-delete', name: 'batch_delete', methods: ['DELETE', 'POST'])]
    public function batchDelete(int $datasetId, Request $request): JsonResponse
    {
        try {
            $dataset = $this->validationService->validateAndGetDataset($datasetId);
            $documentIds = $this->deletionService->extractDocumentIds($request);

            [$deletedCount, $errors] = $this->deletionService->performBatchDelete($datasetId, $documentIds);

            return $this->successResponse(
                sprintf('Successfully deleted %d documents', $deletedCount),
                [
                    'deleted_count' => $deletedCount,
                    'errors' => $errors,
                    'dataset' => $this->formatDatasetInfo($dataset),
                ]
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to batch delete documents', $e->getMessage());
        }
    }

    /**
     * 获取数据集文档统计信息
     */
    #[Route(path: '/stats', name: 'stats', methods: ['GET'])]
    public function getStats(int $datasetId): JsonResponse
    {
        try {
            $dataset = $this->validationService->validateAndGetDataset($datasetId);
            $stats = $this->buildDatasetStats($dataset);

            return $this->successResponse('Dataset document statistics retrieved successfully', [
                'dataset' => $this->formatDatasetInfo($dataset),
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve dataset statistics', $e->getMessage());
        }
    }

    /**
     * 构建数据集统计信息
     *
     * @return array<string, mixed>
     */
    private function buildDatasetStats(Dataset $dataset): array
    {
        return [
            'total_count' => $dataset->getDocumentCount(),
            'total_size' => $dataset->getDocumentsTotalSize(),
            'total_size_formatted' => $dataset->getDocumentsTotalSizeFormatted(),
            'completed_count' => $dataset->getCompletedDocumentCount(),
            'processing_count' => $dataset->getProcessingDocumentCount(),
            'failed_count' => $dataset->getFailedDocumentCount(),
            'status_counts' => $dataset->getDocumentStatusStats(),
            'has_retry_required' => $dataset->hasDocumentsRequiringRetry(),
        ];
    }

    /**
     * 删除指定文档
     */
    #[Route(path: '/{documentId}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $datasetId, int $documentId): JsonResponse
    {
        try {
            $dataset = $this->validationService->validateAndGetDataset($datasetId);
            $document = $this->validationService->validateAndGetDocument($datasetId, $documentId);

            $documentInfo = $this->captureDocumentInfo($document);
            $this->deletionService->deleteDocument($document);

            return $this->successResponse('Document deleted successfully', [
                'id' => $documentId,
                'name' => $documentInfo['name'],
                'remoteId' => $documentInfo['remoteId'],
                'dataset' => $this->formatDatasetInfo($dataset),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete document', $e->getMessage());
        }
    }

    /**
     * 捕获文档信息
     *
     * @return array<string, mixed>
     */
    private function captureDocumentInfo(Document $document): array
    {
        return [
            'name' => $document->getName(),
            'remoteId' => $document->getRemoteId(),
        ];
    }

    /**
     * 重新解析指定文档
     */
    #[Route(path: '/{documentId}/parse', name: 'parse', methods: ['POST'])]
    public function parse(int $datasetId, int $documentId, Request $request): JsonResponse
    {
        try {
            $dataset = $this->validationService->validateAndGetDataset($datasetId);
            $document = $this->validationService->validateAndGetDocument($datasetId, $documentId);
            $this->validationService->validateDocumentForParsing($document);

            $options = $this->extractParseOptions($request);
            $result = $this->documentService->parse(
                $dataset->getRemoteId() ?? '',
                $document->getRemoteId() ?? '',
                $options
            );

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Document parsing initiated successfully',
                'data' => [
                    'document' => $this->formatBasicDocumentInfo($document),
                    'dataset' => $this->formatDatasetInfo($dataset),
                    'parse_result' => $result,
                ],
                'timestamp' => date('c'),
            ], Response::HTTP_ACCEPTED);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to initiate document parsing', $e->getMessage());
        }
    }

    /**
     * 提取解析选项
     *
     * @return array<string, mixed>
     */
    private function extractParseOptions(Request $request): array
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return [];
        }

        /** @var array<string, mixed> $data */
        return $data;
    }

    /**
     * 格式化基础文档信息
     *
     * @return array<string, mixed>
     */
    private function formatBasicDocumentInfo(Document $document): array
    {
        return [
            'id' => $document->getId(),
            'name' => $document->getName(),
            'remoteId' => $document->getRemoteId(),
        ];
    }

    /**
     * 获取文档解析状态
     */
    #[Route(path: '/{documentId}/parse-status', name: 'parse_status', methods: ['GET'])]
    public function getParseStatus(int $datasetId, int $documentId): JsonResponse
    {
        try {
            $dataset = $this->validationService->validateAndGetDataset($datasetId);
            $document = $this->validationService->validateAndGetDocument($datasetId, $documentId);
            $this->validationService->validateDocumentForParsing($document);

            $result = $this->documentService->getParseStatus(
                $dataset->getRemoteId() ?? '',
                $document->getRemoteId() ?? ''
            );

            return $this->successResponse('Parse status retrieved successfully', [
                'document' => $this->formatDocumentWithStatus($document),
                'dataset' => $this->formatDatasetInfo($dataset),
                'parse_status' => $result,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve parse status', $e->getMessage());
        }
    }

    /**
     * 格式化带状态的文档信息
     *
     * @return array<string, mixed>
     */
    private function formatDocumentWithStatus(Document $document): array
    {
        return [
            'id' => $document->getId(),
            'name' => $document->getName(),
            'remoteId' => $document->getRemoteId(),
            'status' => $document->getStatus(),
            'parseStatus' => $document->getParseStatus(),
        ];
    }
}
