<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\KnowledgeGraph;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Service\DatasetService;

/**
 * 获取数据集的知识图谱
 */
final class GetByDatasetController extends AbstractController
{
    public function __construct(
        private readonly DatasetService $datasetService,
    ) {
    }

    #[Route(path: '/api/v1/knowledge-graph/datasets/{datasetId}', name: 'api_knowledge_graph_get_by_dataset', methods: ['GET'])]
    public function __invoke(string $datasetId, Request $request): JsonResponse
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
}
