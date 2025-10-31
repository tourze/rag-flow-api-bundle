<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Enum\DocumentStatus;
use Tourze\RAGFlowApiBundle\Exception\DocumentOperationException;
use Tourze\RAGFlowApiBundle\Repository\DocumentRepository;

/**
 * 文档操作服务
 */
final readonly class DocumentOperationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DocumentRepository $documentRepository,
        private DocumentService $documentService,
        private DatasetDocumentManagementService $managementService,
    ) {
    }

    /**
     * 重试失败的文档上传
     *
     * @return array{retry_count: int, errors: array<string>}
     */
    public function retryFailedDocuments(Dataset $dataset): array
    {
        $failedDocuments = $this->getFailedDocuments($dataset);

        $retryCount = 0;
        $errors = [];

        foreach ($failedDocuments as $document) {
            try {
                if ($this->shouldRetryDocument($document)) {
                    $this->processDocumentRetry($document, $dataset);
                    ++$retryCount;
                }
            } catch (\Exception $e) {
                $errors = $this->handleRetryError($document, $e, $errors);
            }
        }

        return [
            'retry_count' => $retryCount,
            'errors' => $errors,
        ];
    }

    /**
     * 获取失败的文档
     *
     * @return Document[]
     */
    private function getFailedDocuments(Dataset $dataset): array
    {
        return $this->documentRepository->findBy([
            'dataset' => $dataset,
            'status' => [DocumentStatus::SYNC_FAILED, DocumentStatus::FAILED],
        ]);
    }

    /**
     * 检查文档是否需要重试
     */
    private function shouldRetryDocument(Document $document): bool
    {
        $filePath = $document->getFilePath();

        return $document->isUploadRequired()
            && null !== $filePath
            && '' !== $filePath
            && file_exists($filePath);
    }

    /**
     * 处理文档重试
     */
    private function processDocumentRetry(Document $document, Dataset $dataset): void
    {
        $document->setStatus(DocumentStatus::UPLOADING);
        $this->entityManager->flush();

        $filePath = $document->getFilePath();
        if (null === $filePath) {
            throw DocumentOperationException::uploadFailed($document->getName(), 'File path is null');
        }

        $datasetRemoteId = $dataset->getRemoteId();
        if (null === $datasetRemoteId) {
            throw DocumentOperationException::datasetNotFound((string) $dataset->getId());
        }

        $result = $this->documentService->upload(
            $datasetRemoteId,
            ['file' => $filePath],
            ['file' => $document->getName()]
        );

        $this->updateDocumentAfterRetry($document, $result);
    }

    /**
     * 重试后更新文档
     *
     * @param array<string, mixed> $result
     */
    private function updateDocumentAfterRetry(Document $document, array $result): void
    {
        if (isset($result['data']) && is_array($result['data']) && [] !== $result['data']) {
            $firstItem = $result['data'][0] ?? null;
            if (is_array($firstItem) && isset($firstItem['id']) && is_string($firstItem['id'])) {
                $document->setRemoteId($firstItem['id']);
            }
        }

        $document->setStatus(DocumentStatus::UPLOADED);
        $document->setLastSyncTime(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    /**
     * 处理重试错误
     *
     * @param array<string> $errors
     * @return array<string>
     */
    private function handleRetryError(Document $document, \Exception $e, array $errors): array
    {
        $document->setStatus(DocumentStatus::SYNC_FAILED);
        $this->entityManager->flush();
        $errors[] = sprintf('重试文档 %s 失败: %s', $document->getName(), $e->getMessage());

        return $errors;
    }

    /**
     * 准备文档列表数据
     *
     * @return array{dataset: Dataset, flash_messages: array<string, string>}
     */
    public function prepareDocumentListData(Dataset $dataset, Request $request): array
    {
        $flashMessages = [];

        try {
            // 同步远程文档到本地数据库
            if (null !== $dataset->getRemoteId()) {
                $this->documentService->list($dataset->getRemoteId());
                // $flashMessages['success'] = '文档数据已同步';
            }
        } catch (\Exception $e) {
            $flashMessages['warning'] = sprintf('同步文档数据失败: %s', $e->getMessage());
        }

        return [
            'dataset' => $dataset,
            'flash_messages' => $flashMessages,
        ];
    }

    /**
     * 批量删除文档
     *
     * @param array<int|string> $documentIds
     * @return array{deleted_count: int, errors: array<string>}
     */
    public function batchDeleteDocuments(Dataset $dataset, array $documentIds): array
    {
        $deletedCount = 0;
        $errors = [];

        $this->entityManager->beginTransaction();

        try {
            foreach ($documentIds as $documentId) {
                $result = $this->deleteDocumentSafely($dataset, $documentId);
                $deletedCount += $result['deleted'];
                $errors = array_merge($errors, $result['errors']);
            }

            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw DocumentOperationException::syncFailed('batch_delete', $e->getMessage(), $e);
        }

        return [
            'deleted_count' => $deletedCount,
            'errors' => $errors,
        ];
    }

    /**
     * 安全删除单个文档
     *
     * @param int|string $documentId
     * @return array{deleted: int, errors: array<string>}
     */
    private function deleteDocumentSafely(Dataset $dataset, int|string $documentId): array
    {
        $document = $this->documentRepository->find($documentId);
        if (!$document instanceof Document || $document->getDataset() !== $dataset) {
            return [
                'deleted' => 0,
                'errors' => [sprintf('文档ID %s 不存在或不属于当前数据集', $documentId)],
            ];
        }

        try {
            $this->managementService->deleteDocument($document);

            return [
                'deleted' => 1,
                'errors' => [],
            ];
        } catch (\Exception $e) {
            return [
                'deleted' => 0,
                'errors' => [sprintf('删除文档 %s 失败: %s', $document->getName(), $e->getMessage())],
            ];
        }
    }

    /**
     * 重试单个文档上传
     *
     * @return array{success: bool, message: string, status?: string, error?: string}
     */
    public function retryDocumentUpload(Document $document, Dataset $dataset): array
    {
        try {
            if (!$this->shouldRetryDocument($document)) {
                return [
                    'success' => false,
                    'message' => '文档不需要重试或文件不存在',
                    'status' => $document->getStatus()->value,
                ];
            }

            $this->processDocumentRetry($document, $dataset);

            return [
                'success' => true,
                'message' => sprintf('文档 %s 重新上传成功', $document->getName()),
                'status' => $document->getStatus()->value,
            ];
        } catch (\Exception $e) {
            $errors = [];
            $this->handleRetryError($document, $e, $errors);

            return [
                'success' => false,
                'message' => sprintf('重试文档 %s 失败', $document->getName()),
                'error' => $e->getMessage(),
                'status' => $document->getStatus()->value,
            ];
        }
    }

    /**
     * 重新解析文档
     *
     * @return array{success: bool, message: string, data?: array<string, mixed>, error?: string}
     */
    public function reparseDocument(Document $document, Dataset $dataset): array
    {
        return $this->handleParsingAction(
            $document,
            $dataset,
            'reparse',
            static fn ($service, $datasetRemoteId, $remoteId) => $service->parseChunks($datasetRemoteId, [$remoteId]),
            static function (Document $doc): void {
                $doc->setStatus(DocumentStatus::PROCESSING);
                $doc->setProgress(0.0);
                $doc->setProgressMsg('重新解析中...');
            },
            '重新解析已启动',
            '重新解析',
            '无法解析'
        );
    }

    /**
     * 停止文档解析
     *
     * @return array{success: bool, message: string, data?: array<string, mixed>, error?: string}
     */
    public function stopDocumentParsing(Document $document, Dataset $dataset): array
    {
        return $this->handleParsingAction(
            $document,
            $dataset,
            'stop',
            static fn ($service, $datasetRemoteId, $remoteId) => $service->stopParsing($datasetRemoteId, [$remoteId]),
            static function (Document $doc): void {
                $doc->setStatus(DocumentStatus::PENDING);
                $doc->setProgress(null);
                $doc->setProgressMsg('解析已停止');
            },
            '解析已停止',
            '停止解析',
            '无法停止解析'
        );
    }

    /**
     * 处理解析动作的通用模板方法
     *
     * @param callable(DocumentService, string, string): array<string, mixed> $apiCall
     * @param callable(Document): void $updateStatus
     * @return array{success: bool, message: string, data?: array<string, mixed>, error?: string}
     */
    private function handleParsingAction(
        Document $document,
        Dataset $dataset,
        string $action,
        callable $apiCall,
        callable $updateStatus,
        string $successHint,
        string $failureVerb,
        string $validationHint,
    ): array {
        $remoteId = $document->getRemoteId();
        if (null === $remoteId || '' === $remoteId) {
            return [
                'success' => false,
                'message' => sprintf('文档尚未上传，%s', $validationHint),
            ];
        }

        $datasetRemoteId = $dataset->getRemoteId();
        if (null === $datasetRemoteId) {
            throw DocumentOperationException::datasetNotFound((string) $dataset->getId());
        }

        try {
            $result = $apiCall($this->documentService, $datasetRemoteId, $remoteId);
            $updateStatus($document);
            $this->entityManager->flush();

            return [
                'success' => true,
                'message' => sprintf('文档 %s %s', $document->getName(), $successHint),
                'data' => $result,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => sprintf('%s文档 %s 失败', $failureVerb, $document->getName()),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 获取数据集文档统计信息
     *
     * @return array<string, mixed>
     */
    public function getDatasetDocumentStats(Dataset $dataset): array
    {
        return $this->managementService->getDatasetDocumentStats($dataset);
    }

    /**
     * 处理文件上传
     *
     * @return array{uploaded_count: int, errors: array<string>}
     */
    public function handleFileUpload(Dataset $dataset, Request $request): array
    {
        $uploadedFiles = $this->extractUploadedFiles($request);

        if ([] === $uploadedFiles) {
            return $this->buildEmptyUploadResult();
        }

        $datasetRemoteId = $this->validateDatasetRemoteId($dataset);

        return $this->processFileUploads($dataset, $datasetRemoteId, $uploadedFiles);
    }

    /**
     * @return array<UploadedFile>
     */
    private function extractUploadedFiles(Request $request): array
    {
        $filesArray = $request->files->get('files', []);
        $uploadedFiles = [];

        if (is_array($filesArray)) {
            foreach ($filesArray as $file) {
                if (null !== $file && $file instanceof UploadedFile) {
                    $uploadedFiles[] = $file;
                }
            }
        }

        return $uploadedFiles;
    }

    /**
     * @return array{uploaded_count: int, errors: array<string>}
     */
    private function buildEmptyUploadResult(): array
    {
        return [
            'uploaded_count' => 0,
            'errors' => ['未选择要上传的文件'],
        ];
    }

    private function validateDatasetRemoteId(Dataset $dataset): string
    {
        $datasetRemoteId = $dataset->getRemoteId();
        if (null === $datasetRemoteId) {
            throw DocumentOperationException::datasetNotFound((string) $dataset->getId());
        }

        return $datasetRemoteId;
    }

    /**
     * @param array<UploadedFile> $uploadedFiles
     * @return array{uploaded_count: int, errors: array<string>}
     */
    private function processFileUploads(Dataset $dataset, string $datasetRemoteId, array $uploadedFiles): array
    {
        $uploadedCount = 0;
        $errors = [];

        foreach ($uploadedFiles as $file) {
            $result = $this->processSingleFileUpload($dataset, $datasetRemoteId, $file);
            $uploadedCount += $result['uploaded'];
            $errors = array_merge($errors, $result['errors']);
        }

        return [
            'uploaded_count' => $uploadedCount,
            'errors' => $errors,
        ];
    }

    /**
     * 处理单个文件上传
     *
     * @return array{uploaded: int, errors: array<string>}
     */
    private function processSingleFileUpload(Dataset $dataset, string $datasetRemoteId, UploadedFile $file): array
    {
        if (!$file->isValid()) {
            return [
                'uploaded' => 0,
                'errors' => [sprintf('文件 %s 上传失败: %s', $file->getClientOriginalName(), $file->getErrorMessage())],
            ];
        }

        try {
            $this->uploadSingleFile($dataset, $datasetRemoteId, $file);

            return [
                'uploaded' => 1,
                'errors' => [],
            ];
        } catch (\Exception $e) {
            return [
                'uploaded' => 0,
                'errors' => [sprintf('文件 %s 上传失败: %s', $file->getClientOriginalName(), $e->getMessage())],
            ];
        }
    }

    private function uploadSingleFile(Dataset $dataset, string $datasetRemoteId, UploadedFile $file): void
    {
        $filePath = $file->getPathname();
        $fileName = $file->getClientOriginalName();

        $result = $this->documentService->upload(
            $datasetRemoteId,
            [$fileName => $filePath],
            [$fileName => $fileName]
        );

        if (!isset($result['data']) || !is_array($result['data']) || [] === $result['data']) {
            throw DocumentOperationException::uploadFailed($fileName, '上传后处理失败');
        }

        $this->managementService->handleDocumentUpload($dataset, $result);
    }

    /**
     * 删除文档
     */
    public function deleteDocument(Document $document): void
    {
        $this->managementService->deleteDocument($document);
    }

    /**
     * 从API更新文档状态
     */
    public function updateDocumentStatusFromApi(Document $document, Dataset $dataset): void
    {
        try {
            $remoteId = $document->getRemoteId();
            $datasetRemoteId = $dataset->getRemoteId();

            if (null === $remoteId || null === $datasetRemoteId) {
                return;
            }

            $status = $this->managementService->getParseStatus($dataset, $remoteId);

            $progress = $this->extractNumeric($status, 'progress');
            if (null !== $progress) {
                $document->setProgress($progress * 100); // 转换为百分比
            }

            $progressMsg = $this->extractString($status, 'progress_msg');
            if (null !== $progressMsg) {
                $document->setProgressMsg($progressMsg);
            }

            $chunkNum = $this->extractNumeric($status, 'chunk_num');
            if (null !== $chunkNum) {
                $document->setChunkCount((int) $chunkNum);
            }

            $this->entityManager->flush();
        } catch (\Exception $e) {
            // 静默处理状态更新失败，不影响主流程
        }
    }

    /**
     * 从数组中提取数值
     *
     * @param array<string, mixed> $data
     */
    private function extractNumeric(array $data, string $key): ?float
    {
        if (isset($data[$key]) && is_numeric($data[$key])) {
            return (float) $data[$key];
        }

        return null;
    }

    /**
     * 从数组中提取字符串
     *
     * @param array<string, mixed> $data
     */
    private function extractString(array $data, string $key): ?string
    {
        if (isset($data[$key]) && is_string($data[$key])) {
            return $data[$key];
        }

        return null;
    }
}
