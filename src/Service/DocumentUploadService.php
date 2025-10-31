<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;

/**
 * 文档上传服务
 */
final readonly class DocumentUploadService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DocumentService $documentService,
    ) {
    }

    /**
     * 提取上传的文件
     *
     * @return array<UploadedFile>
     */
    public function extractUploadedFiles(Request $request): array
    {
        $uploadedFiles = $this->tryExtractFiles($request);

        if ([] === $uploadedFiles) {
            throw new \InvalidArgumentException('No files uploaded');
        }

        return $uploadedFiles;
    }

    /**
     * 处理文件上传
     *
     * @param UploadedFile[] $uploadedFiles
     * @return array{array<int, array<string, mixed>>, array<int, string>}
     */
    public function processFileUploads(Dataset $dataset, array $uploadedFiles): array
    {
        $uploadedDocuments = [];
        $errors = [];

        foreach ($uploadedFiles as $uploadedFile) {
            $result = $this->processSingleFile($dataset, $uploadedFile);
            if (null !== $result['document']) {
                $uploadedDocuments[] = $result['document'];
            }
            if (null !== $result['error']) {
                $errors[] = $result['error'];
            }
        }

        return [$uploadedDocuments, $errors];
    }

    /**
     * 尝试提取文件
     *
     * @return array<UploadedFile>
     */
    private function tryExtractFiles(Request $request): array
    {
        $uploadedFiles = $this->extractMultipleFiles($request);

        return [] !== $uploadedFiles ? $uploadedFiles : $this->extractSingleFile($request);
    }

    /**
     * 提取多个文件
     *
     * @return array<UploadedFile>
     */
    private function extractMultipleFiles(Request $request): array
    {
        $filesData = $request->files->get('files', []);
        $uploadedFiles = [];

        if (is_array($filesData)) {
            foreach ($filesData as $file) {
                if ($file instanceof UploadedFile) {
                    $uploadedFiles[] = $file;
                }
            }
        }

        return $uploadedFiles;
    }

    /**
     * 提取单个文件
     *
     * @return array<UploadedFile>
     */
    private function extractSingleFile(Request $request): array
    {
        $uploadedFile = $request->files->get('file');

        return $uploadedFile instanceof UploadedFile ? [$uploadedFile] : [];
    }

    /**
     * 处理单个上传文件
     *
     * @return array{document: array<string, mixed>|null, error: string|null}
     */
    private function processSingleFile(Dataset $dataset, mixed $uploadedFile): array
    {
        if (!$uploadedFile instanceof UploadedFile || !$uploadedFile->isValid()) {
            return ['document' => null, 'error' => 'Invalid file upload'];
        }

        try {
            $document = $this->processFileUpload($dataset, $uploadedFile);

            return ['document' => $this->formatUploadedDocument($document), 'error' => null];
        } catch (\Exception $e) {
            return ['document' => null, 'error' => sprintf('Upload file %s failed: %s', $uploadedFile->getClientOriginalName(), $e->getMessage())];
        }
    }

    /**
     * 处理单个文件上传
     */
    private function processFileUpload(Dataset $dataset, UploadedFile $uploadedFile): Document
    {
        $this->validateUploadedFile($uploadedFile);

        $document = $this->createDocumentFromUpload($dataset, $uploadedFile);

        $this->syncDocumentToRemote($dataset, $document);

        return $document;
    }

    /**
     * 验证上传文件
     */
    private function validateUploadedFile(UploadedFile $uploadedFile): void
    {
        $this->validateFileExtension($uploadedFile);
        $this->validateMimeType($uploadedFile);
    }

    /**
     * 验证文件扩展名
     */
    private function validateFileExtension(UploadedFile $uploadedFile): void
    {
        $fileExtension = strtolower($uploadedFile->getClientOriginalExtension());
        $document = new Document();
        if (!$document->isFileTypeSupported($fileExtension)) {
            throw new \InvalidArgumentException(sprintf('Unsupported file type: %s', $fileExtension));
        }
    }

    /**
     * 验证MIME类型
     */
    private function validateMimeType(UploadedFile $uploadedFile): void
    {
        $mimeType = $uploadedFile->getMimeType();
        if ($this->shouldSkipMimeTypeValidation($mimeType)) {
            return;
        }

        assert(is_string($mimeType));
        $this->ensureMimeTypeSupported($mimeType);
    }

    /**
     * 是否应跳过MIME类型验证
     */
    private function shouldSkipMimeTypeValidation(?string $mimeType): bool
    {
        return null === $mimeType || '' === $mimeType;
    }

    /**
     * 确保MIME类型受支持
     */
    private function ensureMimeTypeSupported(string $mimeType): void
    {
        $document = new Document();
        if (!$document->isMimeTypeSupported($mimeType)) {
            throw new \InvalidArgumentException(sprintf('Unsupported MIME type: %s', $mimeType));
        }
    }

    /**
     * 从上传文件创建文档实体
     */
    private function createDocumentFromUpload(Dataset $dataset, UploadedFile $uploadedFile): Document
    {
        $fileExtension = strtolower($uploadedFile->getClientOriginalExtension());
        $mimeType = $uploadedFile->getMimeType();
        $fileName = $uploadedFile->getClientOriginalName();

        // 临时文件信息，TODO: 使用FileStorageBundle保存文件
        $filePath = '/tmp/' . $fileName;

        $document = new Document();
        $document->setName($fileName);
        $document->setFilename($fileName);
        $document->setFilePath($filePath);
        $document->setType($fileExtension);
        $document->setMimeType($mimeType);
        $document->setSize($uploadedFile->getSize());
        $document->setDataset($dataset);
        $document->setStatus('pending');

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        return $document;
    }

    /**
     * 同步文档到远程API
     */
    private function syncDocumentToRemote(Dataset $dataset, Document $document): void
    {
        try {
            $this->setUploadingStatus($document);
            $this->validateSyncRequirements($dataset, $document);
            $this->performRemoteUpload($dataset, $document);
        } catch (\Exception $e) {
            $this->handleSyncFailure($document, $e);
        }
    }

    /**
     * 设置上传中状态
     */
    private function setUploadingStatus(Document $document): void
    {
        $document->setStatus('uploading');
        $this->entityManager->flush();
    }

    /**
     * 验证同步需求
     */
    private function validateSyncRequirements(Dataset $dataset, Document $document): void
    {
        $this->ensureDatasetHasRemoteId($dataset);
        $this->ensureDocumentHasFilePath($document);
    }

    /**
     * 确保数据集有远程ID
     */
    private function ensureDatasetHasRemoteId(Dataset $dataset): void
    {
        if (null === $dataset->getRemoteId()) {
            throw new \RuntimeException('Dataset remote ID is missing');
        }
    }

    /**
     * 确保文档有文件路径
     */
    private function ensureDocumentHasFilePath(Document $document): void
    {
        if (null === $document->getFilePath()) {
            throw new \RuntimeException('Document file path is missing');
        }
    }

    /**
     * 执行远程上传
     */
    private function performRemoteUpload(Dataset $dataset, Document $document): void
    {
        $datasetRemoteId = $dataset->getRemoteId();
        assert(null !== $datasetRemoteId);

        $fileName = $document->getName();
        $filePath = $document->getFilePath();
        assert(null !== $filePath);

        $result = $this->documentService->upload(
            $datasetRemoteId,
            [$fileName => $filePath],
            [$fileName => $fileName]
        );

        $this->updateDocumentFromApiResult($document, $result);
    }

    /**
     * 从API结果更新文档
     *
     * @param array<string, mixed> $result
     */
    private function updateDocumentFromApiResult(Document $document, array $result): void
    {
        $this->extractAndSetRemoteId($document, $result);
        $this->markDocumentUploaded($document);
    }

    /**
     * 提取并设置远程ID
     *
     * @param array<string, mixed> $result
     */
    private function extractAndSetRemoteId(Document $document, array $result): void
    {
        $remoteId = $this->extractRemoteIdFromResult($result);
        if (null === $remoteId) {
            return;
        }

        $document->setRemoteId($remoteId);
    }

    /**
     * 从结果中提取远程ID
     *
     * @param array<string, mixed> $result
     */
    private function extractRemoteIdFromResult(array $result): ?string
    {
        if (!$this->hasValidDataArray($result)) {
            return null;
        }

        // Assert the array type before accessing offset
        assert(is_array($result['data']));
        $apiDocument = $result['data'][0];
        if (!$this->isValidApiDocument($apiDocument)) {
            return null;
        }

        // Assert the type before accessing nested offset
        assert(is_array($apiDocument));

        return is_string($apiDocument['id']) ? $apiDocument['id'] : null;
    }

    /**
     * 检查是否有有效的数据数组
     *
     * @param array<string, mixed> $result
     */
    private function hasValidDataArray(array $result): bool
    {
        return isset($result['data']) && is_array($result['data']) && [] !== $result['data'];
    }

    /**
     * 检查是否是有效的API文档
     */
    private function isValidApiDocument(mixed $apiDocument): bool
    {
        return is_array($apiDocument) && isset($apiDocument['id']);
    }

    /**
     * 标记文档已上传
     */
    private function markDocumentUploaded(Document $document): void
    {
        $document->setStatus('uploaded');
        $document->setLastSyncTime(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    /**
     * 处理同步失败
     */
    private function handleSyncFailure(Document $document, \Exception $e): void
    {
        $document->setStatus('sync_failed');
        $this->entityManager->flush();

        error_log(sprintf('Failed to upload document to RAGFlow API: %s', $e->getMessage()));
        throw new \RuntimeException(sprintf('Uploaded to local but failed to sync to RAGFlow: %s', $e->getMessage()));
    }

    /**
     * 格式化已上传的文档
     *
     * @return array<string, mixed>
     */
    private function formatUploadedDocument(Document $document): array
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
