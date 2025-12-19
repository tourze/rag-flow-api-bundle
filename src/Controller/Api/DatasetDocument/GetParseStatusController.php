<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\DatasetDocument;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Service\DocumentService;
use Tourze\RAGFlowApiBundle\Service\DocumentValidationService;

/**
 * 获取文档解析状态
 */
final class GetParseStatusController extends AbstractController
{
    public function __construct(
        private readonly DocumentValidationService $validationService,
        private readonly DocumentService $documentService,
    ) {
    }

    #[Route(path: '/api/v1/datasets/{datasetId}/documents/{documentId}/parse-status', name: 'api_dataset_documents_parse_status', methods: ['GET'])]
    public function __invoke(int $datasetId, int $documentId): JsonResponse
    {
        try {
            $dataset = $this->validationService->validateAndGetDataset($datasetId);
            $document = $this->validationService->validateAndGetDocument($datasetId, $documentId);
            $this->validationService->validateDocumentForParsing($document);

            $result = $this->documentService->getParseStatus(
                $dataset->getRemoteId() ?? '',
                $document->getRemoteId() ?? ''
            );

            return $this->successResponse('Parse status retrieved successfully', [
                'document' => $this->formatDocumentWithStatus($document),
                'dataset' => $this->formatDatasetInfo($dataset),
                'parse_status' => $result,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve parse status', $e->getMessage());
        }
    }

    /**
     * 创建成功响应
     *
     * @param array<string, mixed> $data
     */
    private function successResponse(string $message, array $data = []): JsonResponse
    {
        return new JsonResponse([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c'),
        ]);
    }

    /**
     * 创建错误响应
     */
    private function errorResponse(string $message, string $error = ''): JsonResponse
    {
        $data = [
            'status' => 'error',
            'message' => $message,
            'timestamp' => date('c'),
        ];

        if ('' !== $error) {
            $data['error'] = $error;
        }

        return new JsonResponse($data);
    }

    /**
     * 格式化数据集信息
     *
     * @return array<string, mixed>
     */
    private function formatDatasetInfo(Dataset $dataset): array
    {
        return [
            'id' => $dataset->getId(),
            'name' => $dataset->getName(),
            'remoteId' => $dataset->getRemoteId(),
        ];
    }

    /**
     * 格式化带状态的文档信息
     *
     * @return array<string, mixed>
     */
    private function formatDocumentWithStatus(Document $document): array
    {
        return [
            'id' => $document->getId(),
            'name' => $document->getName(),
            'remoteId' => $document->getRemoteId(),
            'status' => $document->getStatus(),
            'parseStatus' => $document->getParseStatus(),
        ];
    }
}
