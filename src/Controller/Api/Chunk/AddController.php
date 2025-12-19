<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\Chunk;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Service\ChunkService;

/**
 * 添加知识块
 */
final class AddController extends AbstractController
{
    public function __construct(
        private readonly ChunkService $chunkService,
    ) {
    }

    #[Route(path: '/api/v1/datasets/{datasetId}/chunks', name: 'api_chunks_add', methods: ['POST'])]
    public function __invoke(string $datasetId, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!is_array($data)) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Invalid JSON data',
                    'timestamp' => date('c'),
                ], Response::HTTP_BAD_REQUEST);
            }

            if (!isset($data['chunks']) || !is_array($data['chunks'])) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Chunks array is required',
                    'timestamp' => date('c'),
                ], Response::HTTP_BAD_REQUEST);
            }

            /** @var array<int, array<string, mixed>> $chunks */
            $chunks = $data['chunks'];
            $result = $this->chunkService->add($datasetId, $chunks);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Chunks added successfully',
                'data' => $result,
                'timestamp' => date('c'),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to add chunks',
                'error' => $e->getMessage(),
                'timestamp' => date('c'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
