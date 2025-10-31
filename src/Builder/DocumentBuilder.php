<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Builder;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Tourze\FileStorageBundle\Entity\File;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Enum\DocumentStatus;

/**
 * 文档构建器
 *
 * 负责根据上传的文件和请求数据构建文档实体
 */
final class DocumentBuilder
{
    public function __construct(
        private readonly Document $document = new Document(),
    ) {
    }

    /**
     * 从上传文件构建文档
     */
    public static function fromUpload(
        Dataset $dataset,
        UploadedFile $uploadedFile,
        File $file,
        Request $request,
    ): self {
        $builder = new self();
        $displayName = $builder->extractDisplayName($request, $uploadedFile);
        $description = $builder->extractDescription($request);

        $builder->populateFromFile($uploadedFile, $file, $displayName);
        $builder->setDataset($dataset);
        $builder->setStatus(DocumentStatus::PENDING);
        $builder->setDescription($description);

        return $builder;
    }

    /**
     * 填充文档基本信息
     */
    private function populateFromFile(UploadedFile $uploadedFile, File $file, string $displayName): void
    {
        $this->document->setName($displayName);
        $this->document->setFilename($uploadedFile->getClientOriginalName());
        $this->document->setType(strtolower($uploadedFile->getClientOriginalExtension()));
        $this->document->setMimeType($uploadedFile->getMimeType());
        $this->document->setSize($uploadedFile->getSize());

        $this->setFilePath($file);
    }

    /**
     * 设置文件路径
     */
    private function setFilePath(File $file): void
    {
        $fileId = $file->getId();
        if (null !== $fileId) {
            $this->document->setFilePath((string) ((int) $fileId));
        }
    }

    /**
     * 设置数据集
     */
    private function setDataset(Dataset $dataset): void
    {
        $this->document->setDataset($dataset);
    }

    /**
     * 设置状态
     */
    private function setStatus(DocumentStatus $status): void
    {
        $this->document->setStatus($status);
    }

    /**
     * 设置描述
     */
    private function setDescription(?string $description): void
    {
        if (null !== $description && '' !== $description) {
            $this->document->setSummary($description);
        }
    }

    /**
     * 提取显示名称
     */
    private function extractDisplayName(Request $request, UploadedFile $uploadedFile): string
    {
        $displayNameParam = $request->request->get('display_name');

        return (is_string($displayNameParam) && '' !== $displayNameParam)
            ? $displayNameParam
            : $uploadedFile->getClientOriginalName();
    }

    /**
     * 提取描述
     */
    private function extractDescription(Request $request): ?string
    {
        $descriptionParam = $request->request->get('description');

        return is_string($descriptionParam) ? $descriptionParam : null;
    }

    /**
     * 获取构建的文档
     */
    public function getDocument(): Document
    {
        return $this->document;
    }
}
