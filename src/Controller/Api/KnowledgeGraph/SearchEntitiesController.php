<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\KnowledgeGraph;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Service\KnowledgeGraphService;

/**
 * 搜索知识图谱中的实体
 */
final class SearchEntitiesController extends AbstractController
{
    public function __construct(
        private readonly KnowledgeGraphService $knowledgeGraphService,
    ) {
    }

    #[Route(path: '/api/v1/knowledge-graph/datasets/{datasetId}/entities/search', name: 'api_knowledge_graph_search_entities', methods: ['POST'])]
    public function __invoke(string $datasetId, Request $request): JsonResponse
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
