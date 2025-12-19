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
 * 获取实体的关系图
 */
final class GetEntityRelationsController extends AbstractController
{
    public function __construct(
        private readonly KnowledgeGraphService $knowledgeGraphService,
    ) {
    }

    #[Route(path: '/api/v1/knowledge-graph/datasets/{datasetId}/entities/{entityId}/relations', name: 'api_knowledge_graph_get_entity_relations', methods: ['GET'])]
    public function __invoke(string $datasetId, string $entityId, Request $request): JsonResponse
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
