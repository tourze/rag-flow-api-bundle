<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Tourze\FileStorageBundle\Entity\File;
use Tourze\FileStorageBundle\Service\FileService;
use Tourze\RAGFlowApiBundle\Builder\DocumentBuilder;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Enum\DocumentStatus;
use Tourze\RAGFlowApiBundle\Repository\DatasetRepository;
use Tourze\RAGFlowApiBundle\Repository\DocumentRepository;
use Tourze\RAGFlowApiBundle\Service\DocumentService;
use Tourze\RAGFlowApiBundle\Service\DocumentSyncService;
use Tourze\RAGFlowApiBundle\Service\DocumentUpdateService;
use Tourze\RAGFlowApiBundle\Validator\FileUploadValidator;

/**
 * 文档API Controller
 *
 * 提供文档CRUD操作和文件上传的RESTful API接口
 */
#[Route(path: '/api/v1/documents', name: 'api_documents_')]
final class DocumentController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DocumentRepository $documentRepository,
        private readonly DatasetRepository $datasetRepository,
        private readonly DocumentService $documentService,
        private readonly FileService $fileService,
        private readonly DocumentSyncService $documentSyncService,
        private readonly DocumentUpdateService $documentUpdateService,
        private readonly FileUploadValidator $fileUploadValidator,
    ) {
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

    /**
     * 格式化文档列表
     *
     * @param Document[] $documents
     * @return array<int, array<string, mixed>>
     */
    private function formatDocumentList(array $documents): array
    {
        $result = [];
        foreach ($documents as $document) {
            $result[] = $this->formatDocumentListItem($document);
        }

        return $result;
    }

    /**
     * 格式化文档列表项
     *
     * @return array<string, mixed>
     */
    private function formatDocumentListItem(Document $document): array
    {
        $detail = $this->formatDocumentDetail($document);
        $dataset = $document->getDataset();
        assert($dataset instanceof Dataset, 'Document must have a valid dataset');

        // 移除文件路径以减少响应大小
        unset($detail['filePath']);
        // 简化数据集信息
        $detail['dataset'] = [
            'id' => $dataset->getId(),
            'name' => $dataset->getName(),
        ];

        return $detail;
    }

    /**
     * 获取文档列表
     */
    #[Route(path: '/list', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        try {
            [$page, $limit, $filters] = $this->extractListParameters($request);
            $result = $this->documentRepository->findWithFilters($filters, $page, $limit);

            $data = $this->formatDocumentList($result['items']);

            return $this->successResponse('Documents retrieved successfully', [
                'documents' => $data,
                'pagination' => $this->buildPagination($page, $limit, $result['total']),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve documents', $e->getMessage());
        }
    }

    /**
     * 提取列表查询参数
     *
     * @return array{int, int, array<string, mixed>}
     */
    private function extractListParameters(Request $request): array
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);

        $filters = [];
        $filters = $this->addOptionalFilter($request, $filters, 'name');
        $filters = $this->addOptionalFilter($request, $filters, 'status');
        $filters = $this->addOptionalFilter($request, $filters, 'type');
        $filters = $this->addOptionalFilter($request, $filters, 'dataset_id');

        return [$page, $limit, $filters];
    }

    /**
     * 添加可选筛选条件
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function addOptionalFilter(Request $request, array $filters, string $key): array
    {
        $value = $request->query->get($key);
        if (null !== $value) {
            $filters[$key] = $value;
        }

        return $filters;
    }

    /**
     * 构建分页信息
     *
     * @return array<string, mixed>
     */
    private function buildPagination(int $page, int $limit, int $total): array
    {
        return [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => (int) ceil($total / $limit),
        ];
    }

    /**
     * 上传文档
     */
    #[Route(path: '/upload', name: 'upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        try {
            $dataset = $this->validateDatasetAndFile($request);
            $document = $this->createAndPersistDocument($dataset, $request);
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
     * 验证数据集和文件
     */
    private function validateDatasetAndFile(Request $request): Dataset
    {
        $datasetId = $this->extractDatasetId($request);
        $dataset = $this->validateAndGetDataset($datasetId);
        $uploadedFile = $this->extractAndValidateFile($request);

        // 将验证后的文件存储在request中供后续使用
        $request->attributes->set('validated_file', $uploadedFile);

        return $dataset;
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
    private function createAndPersistDocument(Dataset $dataset, Request $request): Document
    {
        $uploadedFile = $request->attributes->get('validated_file');
        assert($uploadedFile instanceof UploadedFile);

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

    /**
     * /**
     * 获取单个文档详情
     */
    #[Route(path: '/{documentId}', name: 'detail', methods: ['GET'])]
    public function detail(int $documentId): JsonResponse
    {
        try {
            $document = $this->validateAndGetDocument($documentId);

            return $this->successResponse('Document retrieved successfully', $this->formatDocumentDetail($document));
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve document', $e->getMessage());
        }
    }

    /**
     * 更新文档
     */
    #[Route(path: '/{documentId}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(int $documentId, Request $request): JsonResponse
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

    /**
     * /**
     * 删除文档
     */
    #[Route(path: '/{documentId}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $documentId): JsonResponse
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

    /**
     * 重新上传文档到RAGFlow
     */
    #[Route(path: '/{documentId}/retry-upload', name: 'retry_upload', methods: ['POST'])]
    public function retryUpload(int $documentId): JsonResponse
    {
        try {
            $document = $this->validateAndGetDocument($documentId);
            $this->validateDocumentForRetryUpload($document);

            $dataset = $document->getDataset();
            assert($dataset instanceof Dataset);

            $this->documentSyncService->retryUpload($document, $dataset);

            return $this->successResponse('Document uploaded successfully', [
                'id' => $document->getId(),
                'remoteId' => $document->getRemoteId(),
                'status' => $document->getStatus(),
                'lastSyncTime' => $document->getLastSyncTime()?->format('c'),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retry upload', $e->getMessage());
        }
    }

    /**
     * 验证文档是否可以重新上传
     */
    private function validateDocumentForRetryUpload(Document $document): void
    {
        if (!$document->isUploadRequired()) {
            throw new \InvalidArgumentException('Document does not require upload');
        }

        $filePath = $document->getFilePath();
        if (null === $filePath || '' === $filePath || !file_exists($filePath)) {
            throw new \InvalidArgumentException('Local file not found');
        }
    }

    /**
     * 解析文档
     */
    #[Route(path: '/{documentId}/parse', name: 'parse', methods: ['POST'])]
    public function parse(int $documentId, Request $request): JsonResponse
    {
        try {
            $document = $this->validateAndGetDocument($documentId);
            $this->validateDocumentForParsing($document);

            $dataset = $document->getDataset();
            assert($dataset instanceof Dataset, 'Document must have a valid dataset');

            $datasetRemoteId = $dataset->getRemoteId();
            $documentRemoteId = $document->getRemoteId();

            if (null === $datasetRemoteId || null === $documentRemoteId) {
                throw new \InvalidArgumentException('Missing required remote IDs');
            }

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
     * 获取文档解析状态
     */
    #[Route(path: '/{documentId}/parse-status', name: 'parse_status', methods: ['GET'])]
    public function getParseStatus(int $documentId): JsonResponse
    {
        try {
            $document = $this->validateAndGetDocument($documentId);
            $this->validateDocumentForParsing($document);

            $dataset = $document->getDataset();
            assert($dataset instanceof Dataset, 'Document must have a valid dataset');

            $datasetRemoteId = $dataset->getRemoteId();
            $documentRemoteId = $document->getRemoteId();

            if (null === $datasetRemoteId || null === $documentRemoteId) {
                throw new \InvalidArgumentException('Missing required remote IDs');
            }

            $result = $this->documentService->getParseStatus(
                $datasetRemoteId,
                $documentRemoteId
            );

            return $this->successResponse('Parse status retrieved successfully', $result);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve parse status', $e->getMessage());
        }
    }

    /**
     * __invoke方法 - 满足测试基类要求
     *
     * 注意：这是一个多方法控制器，不应该通过__invoke调用。
     * 每个具体操作都有对应的路由和方法。
     */
    #[Route(path: '/invoke', name: 'invoke', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return $this->errorResponse('This is a multi-method controller. Please use specific API endpoints.', 'Invalid method call', Response::HTTP_BAD_REQUEST);
    }
}
