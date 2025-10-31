<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Tourze\RAGFlowApiBundle\Service\ChunkService;

/**
 * 知识块API Controller
 *
 * 提供知识块增删改查、检索的RESTful API接口
 */
#[Route(path: '/api/v1/datasets/{datasetId}/chunks', name: 'api_chunks_')]
final class ChunkController extends AbstractController
{
    public function __construct(
        private readonly ChunkService $chunkService,
    ) {
    }

    /**
     * 检索知识块
     */
    #[Route(path: '/retrieve', name: 'retrieve', methods: ['POST'])]
    public function retrieve(string $datasetId, Request $request): JsonResponse
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

            if (!isset($data['query']) || '' === $data['query']) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Query is required',
                    'timestamp' => date('c'),
                ], Response::HTTP_BAD_REQUEST);
            }

            $query = $data['query'];
            if (!is_string($query)) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Query must be a string',
                    'timestamp' => date('c'),
                ], Response::HTTP_BAD_REQUEST);
            }

            unset($data['query']);

            /** @var array<string, mixed> $options */
            $options = $data;
            $result = $this->chunkService->retrieve($datasetId, $query, $options);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Chunks retrieved successfully',
                'data' => $result,
                'timestamp' => date('c'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to retrieve chunks',
                'error' => $e->getMessage(),
                'timestamp' => date('c'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 添加知识块
     */
    #[Route(path: '', name: 'add', methods: ['POST'])]
    public function add(string $datasetId, Request $request): JsonResponse
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

    /**
     * 更新知识块
     */
    #[Route(path: '/{chunkId}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(string $datasetId, string $chunkId, Request $request): JsonResponse
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

            /** @var array<string, mixed> $updateData */
            $updateData = $data;
            $result = $this->chunkService->update($datasetId, $chunkId, $updateData);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Chunk updated successfully',
                'data' => $result,
                'timestamp' => date('c'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to update chunk',
                'error' => $e->getMessage(),
                'timestamp' => date('c'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 删除知识块
     */
    #[Route(path: '/{chunkId}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $datasetId, string $chunkId): JsonResponse
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
