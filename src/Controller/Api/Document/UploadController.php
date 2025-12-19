<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\Document;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\FileStorageBundle\Service\FileService;
use Tourze\RAGFlowApiBundle\Builder\DocumentBuilder;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Repository\DatasetRepository;
use Tourze\RAGFlowApiBundle\Service\DocumentSyncService;
use Tourze\RAGFlowApiBundle\Validator\FileUploadValidator;

/**
 * 上传文档
 */
final class UploadController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DatasetRepository $datasetRepository,
        private readonly FileService $fileService,
        private readonly DocumentSyncService $documentSyncService,
        private readonly FileUploadValidator $fileUploadValidator,
    ) {
    }

    #[Route(path: '/api/v1/documents/upload', name: 'api_documents_upload', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            [$dataset, $uploadedFile] = $this->validateDatasetAndFile($request);
            $document = $this->createAndPersistDocument($dataset, $uploadedFile, $request);
            $this->documentSyncService->syncDocumentToRemote($document, $dataset);

            return $this->successResponse('Document uploaded successfully',
                $this->formatUploadResponse($document),
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to upload document', $e->getMessage());
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
     * 验证并获取数据集
     */
    private function validateAndGetDataset(int $datasetId): Dataset
    {
        $dataset = $this->datasetRepository->find($datasetId);
        if (!$dataset instanceof Dataset) {
            throw new \InvalidArgumentException('Dataset not found');
        }

        return $dataset;
    }

    /**
     * 验证数据集和文件
     *
     * @return array{Dataset, UploadedFile}
     */
    private function validateDatasetAndFile(Request $request): array
    {
        $datasetId = $this->extractDatasetId($request);
        $dataset = $this->validateAndGetDataset($datasetId);
        $uploadedFile = $this->extractAndValidateFile($request);

        return [$dataset, $uploadedFile];
    }

    /**
     * 提取数据集ID
     */
    private function extractDatasetId(Request $request): int
    {
        $datasetId = $request->request->getInt('dataset_id');
        if (0 === $datasetId) {
            throw new \InvalidArgumentException('Dataset ID is required');
        }

        return $datasetId;
    }

    /**
     * 提取并验证文件
     */
    private function extractAndValidateFile(Request $request): UploadedFile
    {
        $uploadedFile = $request->files->get('file');
        if (!$uploadedFile instanceof UploadedFile) {
            throw new \InvalidArgumentException('File is required');
        }

        $this->fileUploadValidator->validateUploadedFile($uploadedFile);

        return $uploadedFile;
    }

    /**
     * 创建并持久化文档
     */
    private function createAndPersistDocument(Dataset $dataset, UploadedFile $uploadedFile, Request $request): Document
    {
        $file = $this->fileService->uploadFile($uploadedFile, $this->getUser());
        $document = DocumentBuilder::fromUpload($dataset, $uploadedFile, $file, $request)
            ->getDocument()
        ;

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        return $document;
    }

    /**
     * 格式化上传响应
     *
     * @return array<string, mixed>
     */
    private function formatUploadResponse(Document $document): array
    {
        return [
            'id' => $document->getId(),
            'remoteId' => $document->getRemoteId(),
            'name' => $document->getName(),
            'filename' => $document->getFilename(),
            'type' => $document->getType(),
            'size' => $document->getSize(),
            'status' => $document->getStatus(),
            'createTime' => $document->getCreateTime()?->format('c'),
        ];
    }
}
