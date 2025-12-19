<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\Document;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Repository\DocumentRepository;
use Tourze\RAGFlowApiBundle\Service\DocumentUpdateService;

/**
 * 更新文档
 */
final class UpdateController extends AbstractController
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
        private readonly DocumentUpdateService $documentUpdateService,
    ) {
    }

    #[Route(path: '/api/v1/documents/{documentId}', name: 'api_documents_update', methods: ['PUT', 'PATCH'])]
    public function __invoke(int $documentId, Request $request): JsonResponse
    {
        try {
            $data = $this->validateAndDecodeRequestData($request);
            $document = $this->validateAndGetDocument($documentId);

            $this->documentUpdateService->updateDocumentFromData($document, $data);

            return $this->successResponse('Document updated successfully',
                $this->formatUpdateResponse($document)
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update document', $e->getMessage());
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
     * 验证并获取文档
     */
    private function validateAndGetDocument(int $documentId): Document
    {
        $document = $this->documentRepository->find($documentId);
        if (!$document instanceof Document) {
            throw new \InvalidArgumentException('Document not found');
        }

        return $document;
    }

    /**
     * 验证并解码请求数据
     *
     * @return array<string, mixed>
     */
    private function validateAndDecodeRequestData(Request $request): array
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Invalid JSON data');
        }

        // 确保所有键为字符串类型
        $result = [];
        foreach ($data as $key => $value) {
            $result[(string) $key] = $value;
        }

        return $result;
    }

    /**
     * 格式化更新响应
     *
     * @return array<string, mixed>
     */
    private function formatUpdateResponse(Document $document): array
    {
        return [
            'id' => $document->getId(),
            'remoteId' => $document->getRemoteId(),
            'name' => $document->getName(),
            'status' => $document->getStatus(),
            'updateTime' => $document->getUpdateTime()?->format('c'),
            'lastSyncTime' => $document->getLastSyncTime()?->format('c'),
        ];
    }
}
