<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\Document;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Repository\DocumentRepository;
use Tourze\RAGFlowApiBundle\Service\DocumentService;

/**
 * 解析文档
 */
final class ParseController extends AbstractController
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
        private readonly DocumentService $documentService,
    ) {
    }

    #[Route(path: '/api/v1/documents/{documentId}/parse', name: 'api_documents_parse', methods: ['POST'])]
    public function __invoke(int $documentId, Request $request): JsonResponse
    {
        try {
            $document = $this->validateAndGetDocument($documentId);
            $this->validateDocumentForParsing($document);

            [$datasetRemoteId, $documentRemoteId] = $this->requireRemoteIds($document);

            $options = $this->extractParseOptions($request);
            $result = $this->documentService->parse(
                $datasetRemoteId,
                $documentRemoteId,
                $options
            );

            return $this->successResponse('Document parsing initiated successfully', $result, Response::HTTP_ACCEPTED);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to initiate document parsing', $e->getMessage());
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
     * 验证文档是否可以解析
     */
    private function validateDocumentForParsing(Document $document): void
    {
        $remoteId = $document->getRemoteId();
        if (null === $remoteId || '' === $remoteId) {
            throw new \InvalidArgumentException('Document not uploaded to RAGFlow yet');
        }
    }

    /**
     * 获取并验证文档的远端ID(Dataset和Document的RemoteId)
     *
     * @return array{string, string} [datasetRemoteId, documentRemoteId]
     */
    private function requireRemoteIds(Document $document): array
    {
        $dataset = $document->getDataset();
        assert($dataset instanceof Dataset, 'Document must have a valid dataset');

        $datasetRemoteId = $dataset->getRemoteId();
        $documentRemoteId = $document->getRemoteId();

        if (null === $datasetRemoteId || null === $documentRemoteId) {
            throw new \InvalidArgumentException('Missing required remote IDs');
        }

        return [$datasetRemoteId, $documentRemoteId];
    }

    /**
     * 提取解析选项
     *
     * @return array<string, mixed>
     */
    private function extractParseOptions(Request $request): array
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return [];
        }

        /** @var array<string, mixed> $data */
        return $data;
    }
}
