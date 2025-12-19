<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\Document;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Repository\DocumentRepository;

/**
 * 获取单个文档详情
 */
final class DetailController extends AbstractController
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
    ) {
    }

    #[Route(path: '/api/v1/documents/{documentId}', name: 'api_documents_detail', methods: ['GET'])]
    public function __invoke(int $documentId): JsonResponse
    {
        try {
            $document = $this->validateAndGetDocument($documentId);

            return $this->successResponse('Document retrieved successfully', $this->formatDocumentDetail($document));
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve document', $e->getMessage());
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
}
