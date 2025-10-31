<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Exception;

/**
 * 文档操作异常
 *
 * 用于文档上传、同步、解析等操作中的具体异常处理
 */
class DocumentOperationException extends \Exception
{
    public function __construct(
        string $message = 'Document operation failed',
        int $code = 500,
        ?\Throwable $previous = null,
        private readonly ?string $documentId = null,
        private readonly ?string $operation = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getDocumentId(): ?string
    {
        return $this->documentId;
    }

    public function getOperation(): ?string
    {
        return $this->operation;
    }

    /**
     * 创建上传失败异常
     */
    public static function uploadFailed(
        string $documentName,
        string $reason,
        ?\Throwable $previous = null,
    ): self {
        return new self(
            message: "文档上传失败: {$documentName}. 原因: {$reason}",
            code: 500,
            previous: $previous,
            documentId: null,
            operation: 'upload'
        );
    }

    /**
     * 创建同步失败异常
     */
    public static function syncFailed(
        string $documentId,
        string $reason,
        ?\Throwable $previous = null,
    ): self {
        return new self(
            message: "文档同步失败: ID {$documentId}. 原因: {$reason}",
            code: 500,
            previous: $previous,
            documentId: $documentId,
            operation: 'sync'
        );
    }

    /**
     * 创建解析失败异常
     */
    public static function parseFailed(
        string $documentId,
        string $reason,
        ?\Throwable $previous = null,
    ): self {
        return new self(
            message: "文档解析失败: ID {$documentId}. 原因: {$reason}",
            code: 500,
            previous: $previous,
            documentId: $documentId,
            operation: 'parse'
        );
    }

    /**
     * 创建数据集未找到异常
     */
    public static function datasetNotFound(string $datasetId): self
    {
        return new self(
            message: "数据集不存在: ID {$datasetId}",
            code: 404,
            previous: null,
            documentId: null,
            operation: 'dataset_lookup'
        );
    }

    /**
     * 创建文档未找到异常
     */
    public static function documentNotFound(string $documentId): self
    {
        return new self(
            message: "文档不存在: ID {$documentId}",
            code: 404,
            previous: null,
            documentId: $documentId,
            operation: 'document_lookup'
        );
    }

    /**
     * 创建文件类型不支持异常
     */
    public static function unsupportedFileType(string $fileType): self
    {
        return new self(
            message: "不支持的文件类型: {$fileType}",
            code: 400,
            previous: null,
            documentId: null,
            operation: 'file_validation'
        );
    }
}
