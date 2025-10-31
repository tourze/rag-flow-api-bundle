<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Helper\Document;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Exception\DocumentOperationException;
use Tourze\RAGFlowApiBundle\Service\DatasetDocumentManagementService;
use Tourze\RAGFlowApiBundle\Service\DocumentService;

/**
 * 文档上传处理器
 *
 * 负责处理文件上传的核心逻辑
 */
final readonly class DocumentUploadHandler
{
    public function __construct(
        private DocumentService $documentService,
        private DatasetDocumentManagementService $managementService,
    ) {
    }

    /**
     * 从请求中提取上传的文件
     *
     * @return array<UploadedFile>
     */
    public function extractFiles(Request $request): array
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
     * 验证数据集远程ID
     */
    public function validateDatasetRemoteId(Dataset $dataset): string
    {
        $datasetRemoteId = $dataset->getRemoteId();
        if (null === $datasetRemoteId) {
            throw DocumentOperationException::datasetNotFound((string) $dataset->getId());
        }

        return $datasetRemoteId;
    }

    /**
     * 处理批量文件上传
     *
     * @param array<UploadedFile> $uploadedFiles
     * @return array{uploaded_count: int, errors: array<string>}
     */
    public function processUploads(Dataset $dataset, string $datasetRemoteId, array $uploadedFiles): array
    {
        $uploadedCount = 0;
        $errors = [];

        foreach ($uploadedFiles as $file) {
            if (!$file->isValid()) {
                $errors[] = sprintf('文件 %s 上传失败: %s', $file->getClientOriginalName(), $file->getErrorMessage());
                continue;
            }

            try {
                if ($this->uploadFile($dataset, $datasetRemoteId, $file)) {
                    ++$uploadedCount;
                } else {
                    $errors[] = sprintf('文件 %s 上传后处理失败', $file->getClientOriginalName());
                }
            } catch (\Exception $e) {
                $errors[] = sprintf('文件 %s 上传失败: %s', $file->getClientOriginalName(), $e->getMessage());
            }
        }

        return [
            'uploaded_count' => $uploadedCount,
            'errors' => $errors,
        ];
    }

    /**
     * 上传单个文件
     */
    private function uploadFile(Dataset $dataset, string $datasetRemoteId, UploadedFile $file): bool
    {
        $filePath = $file->getPathname();
        $fileName = $file->getClientOriginalName();

        $result = $this->documentService->upload(
            $datasetRemoteId,
            [$fileName => $filePath],
            [$fileName => $fileName]
        );

        if (isset($result['data']) && is_array($result['data']) && [] !== $result['data']) {
            $this->managementService->handleDocumentUpload($dataset, $result);

            return true;
        }

        return false;
    }
}
