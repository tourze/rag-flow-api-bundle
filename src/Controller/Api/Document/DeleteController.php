<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\Document;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\FileStorageBundle\Service\FileService;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Repository\DocumentRepository;
use Tourze\RAGFlowApiBundle\Service\DocumentSyncService;

/**
 * 删除文档
 */
final class DeleteController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DocumentRepository $documentRepository,
        private readonly DocumentSyncService $documentSyncService,
        private readonly FileService $fileService,
    ) {
    }

    #[Route(path: '/api/v1/documents/{documentId}', name: 'api_documents_delete', methods: ['DELETE'])]
    public function __invoke(int $documentId): JsonResponse
    {
        try {
            $document = $this->validateAndGetDocument($documentId);
            $remoteId = $document->getRemoteId();
            $dataset = $document->getDataset();
            assert($dataset instanceof Dataset);

            // 从远程API删除
            $this->documentSyncService->deleteFromRemote($document, $dataset);
            // 删除本地文件
            $this->deleteLocalFile($document);

            $this->entityManager->remove($document);
            $this->entityManager->flush();

            return $this->successResponse('Document deleted successfully', [
                'id' => $documentId,
                'remoteId' => $remoteId,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete document', $e->getMessage());
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
     * 删除本地文件
     */
    private function deleteLocalFile(Document $document): void
    {
        $fileId = $this->extractFileId($document);
        if (null === $fileId) {
            return;
        }

        $this->performFileDelete($fileId);
    }

    /**
     * 提取文件ID
     */
    private function extractFileId(Document $document): ?int
    {
        $filePath = $document->getFilePath();
        if (!$this->isValidFilePathForId($filePath)) {
            return null;
        }

        return (int) $filePath;
    }

    /**
     * 检查是否是有效的文件路径(用于ID)
     */
    private function isValidFilePathForId(?string $filePath): bool
    {
        return null !== $filePath && '' !== $filePath && is_numeric($filePath);
    }

    /**
     * 执行文件删除
     */
    private function performFileDelete(int $fileId): void
    {
        try {
            $file = $this->fileService->getFile($fileId);
            if (null !== $file) {
                $this->fileService->deleteFile($file, true);
            }
        } catch (\Exception $e) {
            error_log(sprintf('Failed to delete local file: %s', $e->getMessage()));
        }
    }
}
