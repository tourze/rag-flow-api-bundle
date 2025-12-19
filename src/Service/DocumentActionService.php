<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Tourze\RAGFlowApiBundle\Context\DocumentRequestContext;
use Tourze\RAGFlowApiBundle\DTO\ActionResult;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Enum\DocumentStatus;
use Tourze\RAGFlowApiBundle\Repository\DocumentRepository;

/**
 * 文档动作服务
 * 负责处理文档相关的业务动作
 */
final class DocumentActionService
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
        private readonly DocumentService $documentService,
        private readonly DocumentRequestContext $requestContext,
    ) {
    }

    /**
     * 执行文档解析
     */
    public function executeParsing(string $entityId): ActionResult
    {
        try {
            $document = $this->findDocument($entityId);

            $validationError = $this->validateDocumentForParsing($document);
            if (null !== $validationError) {
                return ActionResult::error($validationError);
            }

            $this->performDocumentParsing($document);

            // 添加上下文信息用于重定向
            $filters = $this->requestContext->getFiltersForRedirect();
            $message = sprintf('文档 "%s" 开始解析，稍后刷新查看进度', $document->getName());
            if ([] !== $filters) {
                $message .= ' (使用当前过滤器视图)';
            }

            return ActionResult::success($message);
        } catch (\Exception $e) {
            return ActionResult::error(sprintf('解析文档时发生错误: %s', $e->getMessage()));
        }
    }

    /**
     * 显示解析状态
     */
    public function showParseStatus(string $entityId): ActionResult
    {
        try {
            $document = $this->findDocument($entityId);

            return ActionResult::info(sprintf('文档 "%s" 当前状态：%s', $document->getName(), $document->getStatus()->value));
        } catch (\Exception $e) {
            return ActionResult::error(sprintf('获取解析状态时发生错误: %s', $e->getMessage()));
        }
    }

    /**
     * 下载文档
     */
    public function downloadDocument(string $entityId): ActionResult
    {
        try {
            $document = $this->findDocument($entityId);

            if (null === $document->getFilename()) {
                return ActionResult::error('文档文件不可下载');
            }

            return ActionResult::info(sprintf('文档 "%s" 下载功能暂未实现', $document->getName()));
        } catch (\Exception $e) {
            return ActionResult::error(sprintf('下载文档时发生错误: %s', $e->getMessage()));
        }
    }

    private function findDocument(string $entityId): Document
    {
        $document = $this->documentRepository->find($entityId);
        if (null === $document) {
            throw new \RuntimeException('Document not found');
        }

        return $document;
    }

    /**
     * 验证文档是否可以解析
     */
    private function validateDocumentForParsing(Document $document): ?string
    {
        $dataset = $document->getDataset();
        if (null === $dataset || null === $dataset->getRemoteId()) {
            return '文档所属数据集不存在或未同步';
        }

        if (null === $document->getRemoteId()) {
            return '文档未上传到远程服务';
        }

        return null;
    }

    /**
     * 执行文档解析
     */
    private function performDocumentParsing(Document $document): void
    {
        $dataset = $document->getDataset();
        assert(null !== $dataset);
        assert(null !== $dataset->getRemoteId());
        assert(null !== $document->getRemoteId());

        $this->documentService->parseChunks(
            $dataset->getRemoteId(),
            [$document->getRemoteId()]
        );
    }
}
