<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\Chunk;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Service\ChunkService;

/**
 * 删除知识块
 */
final class DeleteController extends AbstractController
{
    public function __construct(
        private readonly ChunkService $chunkService,
    ) {
    }

    #[Route(path: '/api/v1/datasets/{datasetId}/chunks/{chunkId}', name: 'api_chunks_delete', methods: ['DELETE'])]
    public function __invoke(string $datasetId, string $chunkId): JsonResponse
    {
        try {
            $this->chunkService->delete($datasetId, $chunkId);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Chunk deleted successfully',
                'timestamp' => date('c'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to delete chunk',
                'error' => $e->getMessage(),
                'timestamp' => date('c'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
