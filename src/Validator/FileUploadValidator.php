<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Validator;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tourze\RAGFlowApiBundle\Entity\Document;

/**
 * 文件上传验证器
 *
 * 负责验证上传的文件是否符合要求
 */
final class FileUploadValidator
{
    /**
     * 验证上传文件
     *
     * @throws \InvalidArgumentException 当文件不符合要求时
     */
    public function validateUploadedFile(UploadedFile $uploadedFile): void
    {
        $this->validateFileValidity($uploadedFile);
        $this->validateFileType($uploadedFile);
    }

    /**
     * 验证文件有效性
     *
     * @throws \InvalidArgumentException 当文件无效时
     */
    private function validateFileValidity(UploadedFile $uploadedFile): void
    {
        if (!$uploadedFile->isValid()) {
            throw new \InvalidArgumentException('Invalid file upload');
        }
    }

    /**
     * 验证文件类型
     *
     * @throws \InvalidArgumentException 当文件类型不支持时
     */
    private function validateFileType(UploadedFile $uploadedFile): void
    {
        $this->validateFileExtension($uploadedFile);
        $this->validateFileMimeType($uploadedFile);
    }

    /**
     * 验证文件扩展名
     *
     * @throws \InvalidArgumentException 当扩展名不支持时
     */
    private function validateFileExtension(UploadedFile $uploadedFile): void
    {
        $fileExtension = strtolower($uploadedFile->getClientOriginalExtension());

        if (!$this->isFileExtensionSupported($fileExtension)) {
            throw new \InvalidArgumentException(sprintf('Unsupported file type: %s', $fileExtension));
        }
    }

    /**
     * 验证文件MIME类型
     *
     * @throws \InvalidArgumentException 当MIME类型不支持时
     */
    private function validateFileMimeType(UploadedFile $uploadedFile): void
    {
        $mimeType = $uploadedFile->getMimeType();

        if ($this->shouldSkipMimeTypeValidation($mimeType)) {
            return;
        }

        if (!is_string($mimeType) || !$this->isMimeTypeSupported($mimeType)) {
            throw new \InvalidArgumentException(sprintf('Unsupported MIME type: %s', $mimeType ?? 'null'));
        }
    }

    /**
     * 检查是否应跳过MIME类型验证
     */
    private function shouldSkipMimeTypeValidation(?string $mimeType): bool
    {
        return null === $mimeType || '' === $mimeType;
    }

    /**
     * 检查文件扩展名是否受支持
     */
    private function isFileExtensionSupported(string $fileExtension): bool
    {
        $document = new Document();

        return $document->isFileTypeSupported($fileExtension);
    }

    /**
     * 检查MIME类型是否受支持
     */
    private function isMimeTypeSupported(string $mimeType): bool
    {
        $document = new Document();

        return $document->isMimeTypeSupported($mimeType);
    }
}
