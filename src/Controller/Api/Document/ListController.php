<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\Document;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Repository\DocumentRepository;

/**
 * 获取文档列表
 */
final class ListController extends AbstractController
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
    ) {
    }

    #[Route(path: '/api/v1/documents/list', name: 'api_documents_list', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            [$page, $limit, $filters] = $this->extractListParameters($request);
            $result = $this->documentRepository->findWithFilters($filters, $page, $limit);

            $data = $this->formatDocumentList($result['items']);

            return $this->successResponse('Documents retrieved successfully', [
                'documents' => $data,
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
    private function successResponse(string $message, array $data = [], int $statusCode = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c'),
        ], $statusCode);
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
     * 格式化文档列表
     *
     * @param Document[] $documents
     * @return array<int, array<string, mixed>>
     */
    private function formatDocumentList(array $documents): array
    {
        $result = [];
        foreach ($documents as $document) {
            $result[] = $this->formatDocumentListItem($document);
        }

        return $result;
    }

    /**
     * 格式化文档列表项
     *
     * @return array<string, mixed>
     */
    private function formatDocumentListItem(Document $document): array
    {
        $detail = $this->formatDocumentDetail($document);
        $dataset = $document->getDataset();
        assert($dataset instanceof Dataset, 'Document must have a valid dataset');

        // 移除文件路径以减少响应大小
        unset($detail['filePath']);
        // 简化数据集信息
        $detail['dataset'] = [
            'id' => $dataset->getId(),
            'name' => $dataset->getName(),
        ];

        return $detail;
    }

    /**
     * 格式化文档详情
     *
     * @return array<string, mixed>
     */
    private function formatDocumentDetail(Document $document): array
    {
        $dataset = $document->getDataset();
        assert($dataset instanceof Dataset, 'Document must have a valid dataset');

        return [
            'id' => $document->getId(),
            'remoteId' => $document->getRemoteId(),
            'name' => $document->getName(),
            'filename' => $document->getFilename(),
            'filePath' => $document->getFilePath(),
            'type' => $document->getType(),
            'mimeType' => $document->getMimeType(),
            'size' => $document->getSize(),
            'status' => $document->getStatus(),
            'parseStatus' => $document->getParseStatus(),
            'language' => $document->getLanguage(),
            'chunkCount' => $document->getChunkCount(),
            'summary' => $document->getSummary(),
            'dataset' => [
                'id' => $dataset->getId(),
                'name' => $dataset->getName(),
                'remoteId' => $dataset->getRemoteId(),
            ],
            'remoteCreateTime' => $document->getRemoteCreateTime()?->format('c'),
            'remoteUpdateTime' => $document->getRemoteUpdateTime()?->format('c'),
            'lastSyncTime' => $document->getLastSyncTime()?->format('c'),
            'createTime' => $document->getCreateTime()?->format('c'),
            'updateTime' => $document->getUpdateTime()?->format('c'),
        ];
    }

    /**
     * 提取列表查询参数
     *
     * @return array{int, int, array<string, mixed>}
     */
    private function extractListParameters(Request $request): array
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);

        $filters = [];
        $filters = $this->addOptionalFilter($request, $filters, 'name');
        $filters = $this->addOptionalFilter($request, $filters, 'status');
        $filters = $this->addOptionalFilter($request, $filters, 'type');
        $filters = $this->addOptionalFilter($request, $filters, 'dataset_id');

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
