<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Tourze\RAGFlowApiBundle\Service\DatasetService;
use Tourze\RAGFlowApiBundle\Service\KnowledgeGraphService;

/**
 * 知识图谱API Controller
 *
 * 提供知识图谱查询的RESTful API接口
 */
#[Route(path: '/api/v1/knowledge-graph', name: 'api_knowledge_graph_')]
final class KnowledgeGraphController extends AbstractController
{
    public function __construct(
        private readonly DatasetService $datasetService,
        private readonly KnowledgeGraphService $knowledgeGraphService,
    ) {
    }

    /**
     * 获取数据集的知识图谱
     */
    #[Route(path: '/datasets/{datasetId}', name: 'get_by_dataset', methods: ['GET'])]
    public function getByDataset(string $datasetId, Request $request): JsonResponse
    {
        try {
            // 可选的查询参数
            $filters = [
                'depth' => $request->query->getInt('depth'),
                'limit' => $request->query->getInt('limit'),
                'entity_types' => $request->query->get('entity_types'),
                'relation_types' => $request->query->get('relation_types'),
            ];

            // 清除空值
            $filters = array_filter($filters, fn ($value) => null !== $value && '' !== $value);

            $result = $this->datasetService->getKnowledgeGraph($datasetId);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Knowledge graph retrieved successfully',
                'data' => $result,
                'filters' => $filters,
                'dataset_id' => $datasetId,
                'timestamp' => date('c'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to retrieve knowledge graph',
                'error' => $e->getMessage(),
                'dataset_id' => $datasetId,
                'timestamp' => date('c'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 搜索知识图谱中的实体
     */
    #[Route(path: '/datasets/{datasetId}/entities/search', name: 'search_entities', methods: ['POST'])]
    public function searchEntities(string $datasetId, Request $request): JsonResponse
    {
        try {
            $data = $this->parseJsonRequest($request);

            if (!$this->validateSearchRequest($data)) {
                return $this->errorResponse('Invalid JSON data', Response::HTTP_BAD_REQUEST);
            }

            if (!isset($data['query']) || '' === $data['query']) {
                return $this->errorResponse('Search query is required', Response::HTTP_BAD_REQUEST);
            }

            $filteredEntities = $this->knowledgeGraphService->searchEntities($datasetId, $data);

            return $this->createEntitySearchResponse($filteredEntities, $data, $datasetId);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to search entities', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage(), $datasetId);
        }
    }

    /**
     * 获取实体的关系图
     */
    #[Route(path: '/datasets/{datasetId}/entities/{entityId}/relations', name: 'get_entity_relations', methods: ['GET'])]
    public function getEntityRelations(string $datasetId, string $entityId, Request $request): JsonResponse
    {
        try {
            $depth = $request->query->getInt('depth', 1);
            $maxRelations = $request->query->getInt('max_relations', 50);

            $relationData = $this->knowledgeGraphService->getEntityRelations($datasetId, $entityId, $maxRelations);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Entity relations retrieved successfully',
                'data' => [
                    'entity_id' => $entityId,
                    'relations' => $relationData['relations'],
                    'related_entities' => $relationData['entities'],
                    'total_relations' => count($relationData['relations']),
                    'depth' => $depth,
                ],
                'dataset_id' => $datasetId,
                'timestamp' => date('c'),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve entity relations',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                $e->getMessage(),
                $datasetId
            );
        }
    }

    /**
     * 获取知识图谱统计信息
     */
    #[Route(path: '/datasets/{datasetId}/stats', name: 'get_stats', methods: ['GET'])]
    public function getStats(string $datasetId): JsonResponse
    {
        try {
            $stats = $this->knowledgeGraphService->calculateStats($datasetId);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Knowledge graph statistics retrieved successfully',
                'data' => $stats,
                'dataset_id' => $datasetId,
                'timestamp' => date('c'),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve knowledge graph statistics',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                $e->getMessage(),
                $datasetId
            );
        }
    }

    /**
     * 解析JSON请求
     *
     * @return array<string, mixed>
     */
    private function parseJsonRequest(Request $request): array
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return [];
        }

        /** @var array<string, mixed> $data */
        return $data;
    }

    /**
     * 验证搜索请求数据
     *
     * @param array<string, mixed> $data
     */
    private function validateSearchRequest(array $data): bool
    {
        return [] !== $data;
    }

    /**
     * 创建实体搜索响应
     *
     * @param array<int, array<string, mixed>> $entities
     * @param array<string, mixed> $searchData
     */
    private function createEntitySearchResponse(array $entities, array $searchData, string $datasetId): JsonResponse
    {
        return new JsonResponse([
            'status' => 'success',
            'message' => 'Entity search completed successfully',
            'data' => [
                'entities' => $entities,
                'total_found' => count($entities),
                'query' => $searchData['query'],
                'entity_type' => $searchData['entity_type'] ?? null,
            ],
            'dataset_id' => $datasetId,
            'timestamp' => date('c'),
        ]);
    }

    /**
     * 创建错误响应
     */
    private function errorResponse(string $message, int $statusCode, ?string $error = null, ?string $datasetId = null): JsonResponse
    {
        $response = [
            'status' => 'error',
            'message' => $message,
            'timestamp' => date('c'),
        ];

        if (null !== $error) {
            $response['error'] = $error;
        }

        if (null !== $datasetId) {
            $response['dataset_id'] = $datasetId;
        }

        return new JsonResponse($response, $statusCode);
    }
}
