<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\DatasetDocument;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Repository\DocumentRepository;
use Tourze\RAGFlowApiBundle\Service\DocumentValidationService;

/**
 * 获取数据集中的文档列表
 */
final class ListController extends AbstractController
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
        private readonly DocumentValidationService $validationService,
    ) {
    }

    #[Route(path: '/api/v1/datasets/{datasetId}/documents', name: 'api_dataset_documents_list', methods: ['GET'])]
    public function __invoke(int $datasetId, Request $request): JsonResponse
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
    private function errorResponse(string $message, string $error = ''): JsonResponse
    {
        $data = [
            'status' => 'error',
            'message' => $message,
            'timestamp' => date('c'),
        ];

        if ('' !== $error) {
            $data['error'] = $error;
        }

        return new JsonResponse($data);
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
}
