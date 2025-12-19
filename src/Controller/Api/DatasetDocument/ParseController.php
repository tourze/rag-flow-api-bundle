<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\DatasetDocument;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Service\DocumentService;
use Tourze\RAGFlowApiBundle\Service\DocumentValidationService;

/**
 * 重新解析指定文档
 */
final class ParseController extends AbstractController
{
    public function __construct(
        private readonly DocumentValidationService $validationService,
        private readonly DocumentService $documentService,
    ) {
    }

    #[Route(path: '/api/v1/datasets/{datasetId}/documents/{documentId}/parse', name: 'api_dataset_documents_parse', methods: ['POST'])]
    public function __invoke(int $datasetId, int $documentId, Request $request): JsonResponse
    {
        try {
            $dataset = $this->validationService->validateAndGetDataset($datasetId);
            $document = $this->validationService->validateAndGetDocument($datasetId, $documentId);
            $this->validationService->validateDocumentForParsing($document);

            $options = $this->extractParseOptions($request);
            $result = $this->documentService->parse(
                $dataset->getRemoteId() ?? '',
                $document->getRemoteId() ?? '',
                $options
            );

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Document parsing initiated successfully',
                'data' => [
                    'document' => $this->formatBasicDocumentInfo($document),
                    'dataset' => $this->formatDatasetInfo($dataset),
                    'parse_result' => $result,
                ],
                'timestamp' => date('c'),
            ], Response::HTTP_ACCEPTED);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to initiate document parsing', $e->getMessage());
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

    /**
     * 格式化基础文档信息
     *
     * @return array<string, mixed>
     */
    private function formatBasicDocumentInfo(Document $document): array
    {
        return [
            'id' => $document->getId(),
            'name' => $document->getName(),
            'remoteId' => $document->getRemoteId(),
        ];
    }
}
