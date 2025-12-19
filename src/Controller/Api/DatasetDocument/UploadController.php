<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\DatasetDocument;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Service\DocumentUploadService;
use Tourze\RAGFlowApiBundle\Service\DocumentValidationService;

/**
 * 上传文档到指定数据集
 */
final class UploadController extends AbstractController
{
    public function __construct(
        private readonly DocumentValidationService $validationService,
        private readonly DocumentUploadService $uploadService,
    ) {
    }

    #[Route(path: '/api/v1/datasets/{datasetId}/documents/upload', name: 'api_dataset_documents_upload', methods: ['POST'])]
    public function __invoke(int $datasetId, Request $request): JsonResponse
    {
        try {
            $dataset = $this->validationService->validateAndGetDataset($datasetId);
            $uploadedFiles = $this->uploadService->extractUploadedFiles($request);

            [$uploadedDocuments, $errors] = $this->uploadService->processFileUploads($dataset, $uploadedFiles);

            return new JsonResponse([
                'status' => 'success',
                'message' => sprintf('Successfully uploaded %d documents', count($uploadedDocuments)),
                'data' => [
                    'uploaded' => $uploadedDocuments,
                    'errors' => $errors,
                    'dataset' => $this->formatDatasetInfo($dataset),
                ],
                'timestamp' => date('c'),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to upload documents', $e->getMessage());
        }
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
}
