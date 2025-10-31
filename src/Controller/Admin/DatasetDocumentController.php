<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Enum\DocumentStatus;
use Tourze\RAGFlowApiBundle\Repository\DatasetRepository;
use Tourze\RAGFlowApiBundle\Repository\DocumentRepository;
use Tourze\RAGFlowApiBundle\Service\DatasetDocumentSyncService;
use Tourze\RAGFlowApiBundle\Service\DocumentOperationService;
use Tourze\RAGFlowApiBundle\Service\DocumentValidator;
use Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManagerInterface;

/**
 * 数据集文档管理Controller
 *
 * 管理指定数据集下的所有文档，提供上传、删除、解析等功能
 */
#[Route(path: '/admin/datasets/{datasetId}/documents', name: 'dataset_documents_')]
final class DatasetDocumentController extends AbstractController
{
    public function __construct(
        private readonly DatasetDocumentSyncService $syncService,
        private readonly DocumentOperationService $operationService,
        private readonly DocumentValidator $validator,
    ) {
    }

    /**
     * 数据集文档管理主页面
     */
    #[Route(path: '/', name: 'index', methods: ['GET'])]
    public function documentList(int $datasetId, Request $request): Response
    {
        $dataset = $this->validator->findDataset($datasetId);
        if (null === $dataset) {
            $this->addFlash('danger', '数据集不存在');

            return $this->redirectToRoute('admin_rag_flow_dataset_index');
        }

        try {
            $result = $this->operationService->prepareDocumentListData($dataset, $request);

            foreach ($result['flash_messages'] as $type => $message) {
                $this->addFlash($type, $message);
            }

            return $this->redirectToRoute('admin_rag_flow_document_index', [
                'filters[dataset]' => $datasetId,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', sprintf('加载文档列表失败: %s', $e->getMessage()));

            return $this->redirectToRoute('admin_rag_flow_dataset_index');
        }
    }

    /**
     * 文档上传页面
     */
    #[Route(path: '/upload', name: 'upload', methods: ['GET', 'POST'])]
    public function upload(int $datasetId, Request $request): Response
    {
        $dataset = $this->validator->findDataset($datasetId);
        if (null === $dataset) {
            $this->addFlash('danger', '数据集不存在');

            return $this->redirectToRoute('admin_rag_flow_dataset_index');
        }

        try {
            error_log('Upload route accessed. Method: ' . $request->getMethod() . ', DatasetId: ' . $datasetId);

            if ($request->isMethod('POST')) {
                error_log('POST request received for upload. Content-Type: ' . $request->headers->get('Content-Type'));
                error_log('Files count: ' . count($request->files->all()));

                return $this->handleUpload($dataset, $request);
            }

            $returnUrl = $this->generateUrl('dataset_documents_index', ['datasetId' => $dataset->getId()]);

            return $this->render('@RAGFlowApi/admin/dataset_document/upload.html.twig', [
                'dataset' => $dataset,
                'supported_types' => (new Document())->getSupportedFileTypes(),
                'return_url' => $returnUrl,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', sprintf('上传页面加载失败: %s', $e->getMessage()));

            return $this->redirectToRoute('dataset_documents_index', ['datasetId' => $datasetId]);
        }
    }

    /**
     * 批量删除文档
     */
    #[Route(path: '/batch-delete', name: 'batch_delete', methods: ['POST'])]
    public function batchDelete(int $datasetId, Request $request): JsonResponse
    {
        $dataset = $this->validator->findDataset($datasetId);
        if (null === $dataset) {
            return new JsonResponse(['error' => '数据集不存在'], 404);
        }

        try {
            $documentIdsRaw = $request->request->all('document_ids');

            /** @var array<int|string> $documentIds */
            $documentIds = array_filter($documentIdsRaw, fn ($id) => is_int($id) || is_string($id));
            if ([] === $documentIds) {
                return new JsonResponse(['error' => '未选择要删除的文档'], 400);
            }

            $result = $this->operationService->batchDeleteDocuments($dataset, $documentIds);

            return new JsonResponse([
                'success' => true,
                'deleted_count' => $result['deleted_count'],
                'errors' => $result['errors'],
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 重试失败的文档上传
     */
    #[Route(path: '/retry-failed', name: 'retry_failed', methods: ['POST'])]
    public function retryFailed(int $datasetId): JsonResponse
    {
        $dataset = $this->validator->findDataset($datasetId);
        if (null === $dataset) {
            return new JsonResponse(['error' => '数据集不存在'], 404);
        }

        try {
            $result = $this->operationService->retryFailedDocuments($dataset);

            return new JsonResponse([
                'success' => true,
                'retry_count' => $result['retry_count'],
                'errors' => $result['errors'],
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 获取数据集文档统计信息
     */
    #[Route(path: '/stats', name: 'stats', methods: ['GET'])]
    public function getStats(int $datasetId): JsonResponse
    {
        $dataset = $this->validator->findDataset($datasetId);
        if (null === $dataset) {
            return new JsonResponse(['error' => '数据集不存在'], 404);
        }

        try {
            $stats = $this->getDatasetDocumentStats($dataset);

            return new JsonResponse([
                'success' => true,
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 删除单个文档
     */
    #[Route(path: '/{documentId}/delete', name: 'delete', methods: ['POST'])]
    public function delete(int $datasetId, int $documentId): Response
    {
        $dataset = $this->validator->findDataset($datasetId);
        if (null === $dataset) {
            $this->addFlash('danger', '数据集不存在');

            return $this->redirectToRoute('admin_rag_flow_dataset_index');
        }

        $document = $this->validator->findDocumentInDataset($documentId, $dataset);
        if (null === $document) {
            $this->addFlash('danger', '文档不存在或不属于当前数据集');

            return $this->redirectToRoute('dataset_documents_index', ['datasetId' => $datasetId]);
        }

        try {
            $this->deleteDocument($document);
            $this->addFlash('success', sprintf('文档 "%s" 删除成功', $document->getName()));
        } catch (\Exception $e) {
            $this->addFlash('danger', sprintf('删除文档失败: %s', $e->getMessage()));
        }

        return $this->redirectToRoute('dataset_documents_index', ['datasetId' => $datasetId]);
    }

    /**
     * 重传单个文档
     */
    #[Route(path: '/{documentId}/retry', name: 'retry', methods: ['POST'])]
    public function retryDocument(int $datasetId, int $documentId): JsonResponse
    {
        $dataset = $this->validator->findDataset($datasetId);
        if (null === $dataset) {
            return new JsonResponse(['error' => '数据集不存在'], 404);
        }

        $document = $this->validator->findDocumentInDataset($documentId, $dataset);
        if (null === $document) {
            return new JsonResponse(['error' => '文档不存在或不属于当前数据集'], 404);
        }

        try {
            $result = $this->operationService->retryDocumentUpload($document, $dataset);

            return new JsonResponse([
                'success' => $result['success'],
                'message' => $result['message'],
                'status' => $result['status'] ?? null,
                'error' => $result['error'] ?? null,
            ], $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 重新解析文档
     */
    #[Route(path: '/{documentId}/reparse', name: 'reparse', methods: ['POST'])]
    public function reparse(int $datasetId, int $documentId): JsonResponse
    {
        $dataset = $this->validator->findDataset($datasetId);
        if (null === $dataset) {
            return new JsonResponse(['error' => '数据集不存在'], 404);
        }

        $document = $this->validator->findDocumentInDataset($documentId, $dataset);
        if (null === $document) {
            return new JsonResponse(['error' => '文档不存在或不属于当前数据集'], 404);
        }

        try {
            $result = $this->operationService->reparseDocument($document, $dataset);

            return new JsonResponse([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result['data'] ?? null,
                'error' => $result['error'] ?? null,
            ], $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 停止解析文档
     */
    #[Route(path: '/{documentId}/stop-parsing', name: 'stop_parsing', methods: ['POST'])]
    public function stopParsing(int $datasetId, int $documentId): JsonResponse
    {
        $dataset = $this->validator->findDataset($datasetId);
        if (null === $dataset) {
            return new JsonResponse(['error' => '数据集不存在'], 404);
        }

        $document = $this->validator->findDocumentInDataset($documentId, $dataset);
        if (null === $document) {
            return new JsonResponse(['error' => '文档不存在或不属于当前数据集'], 404);
        }

        try {
            $result = $this->operationService->stopDocumentParsing($document, $dataset);

            return new JsonResponse([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result['data'] ?? null,
                'error' => $result['error'] ?? null,
            ], $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 手动同步所有文档的分块数据
     */
    #[Route(path: '/sync-all-chunks', name: 'sync_all_chunks', methods: ['POST'])]
    public function syncAllChunks(int $datasetId): JsonResponse
    {
        $dataset = $this->validator->findDataset($datasetId);
        if (null === $dataset) {
            return new JsonResponse(['error' => '数据集不存在'], 404);
        }

        try {
            $syncResult = $this->processBatchChunkSync($dataset);

            $syncedCount = is_int($syncResult['synced_count']) ? $syncResult['synced_count'] : 0;

            return new JsonResponse([
                'success' => true,
                'synced_count' => $syncedCount,
                'errors' => $syncResult['errors'],
                'message' => sprintf('成功同步 %d 个文档的分块数据', $syncedCount),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 同步文档的chunks到本地数据库
     */
    #[Route(path: '/{documentId}/sync-chunks', name: 'sync_chunks', methods: ['POST'])]
    public function syncChunks(int $datasetId, int $documentId): JsonResponse
    {
        $dataset = $this->validator->findDataset($datasetId);
        if (null === $dataset) {
            return new JsonResponse(['error' => '数据集不存在'], 404);
        }

        $document = $this->validator->findDocumentInDataset($documentId, $dataset);
        if (null === $document) {
            return new JsonResponse(['error' => '文档不存在或不属于当前数据集'], 404);
        }

        $validationError = $this->validator->validateDocumentForChunkSync($document);
        if (null !== $validationError) {
            return $validationError;
        }

        try {
            $syncResult = $this->processSingleDocumentChunkSync($dataset, $document);

            $syncedCount = is_int($syncResult['synced_count']) ? $syncResult['synced_count'] : 0;
            $totalCount = is_int($syncResult['total_count']) ? $syncResult['total_count'] : 0;

            return new JsonResponse([
                'success' => true,
                'message' => sprintf('成功同步 %d 个chunks', $syncedCount),
                'total' => $totalCount,
                'synced' => $syncedCount,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 处理文件上传
     */
    private function handleUpload(Dataset $dataset, Request $request): Response
    {
        try {
            $uploadResult = $this->operationService->handleFileUpload($dataset, $request);

            if ($uploadResult['uploaded_count'] > 0) {
                $this->addFlash('success', sprintf('成功上传 %d 个文件', $uploadResult['uploaded_count']));
            }

            foreach ($uploadResult['errors'] as $error) {
                $this->addFlash('warning', $error);
            }

            return $this->redirectToRoute('dataset_documents_index', ['datasetId' => $dataset->getId()]);
        } catch (\Exception $e) {
            $this->addFlash('danger', sprintf('上传处理失败: %s', $e->getMessage()));

            return $this->redirectToRoute('dataset_documents_upload', ['datasetId' => $dataset->getId()]);
        }
    }

    /**
     * 删除文档（本地+远程+文件）
     */
    private function deleteDocument(Document $document): void
    {
        $this->operationService->deleteDocument($document);
    }

    /**
     * 获取数据集文档统计信息
     *
     * @return array<string, mixed>
     */
    private function getDatasetDocumentStats(Dataset $dataset): array
    {
        return $this->operationService->getDatasetDocumentStats($dataset);
    }

    /**
     * 获取文档解析状态 (AJAX)
     */
    #[Route(path: '/{documentId}/parse-status', name: 'parse_status', methods: ['GET'])]
    public function getParseStatus(int $datasetId, int $documentId): JsonResponse
    {
        $dataset = $this->validator->findDataset($datasetId);
        if (null === $dataset) {
            return new JsonResponse(['error' => '数据集不存在'], 404);
        }

        $document = $this->validator->findDocumentInDataset($documentId, $dataset);
        if (null === $document) {
            return new JsonResponse(['error' => '文档不存在'], 404);
        }

        try {
            $this->updateDocumentStatusFromApi($document, $dataset);

            $progress = $document->getProgress();
            $progressValue = null !== $progress ? $progress / 100 : 0.0;

            return new JsonResponse([
                'progress' => $progressValue,
                'progress_msg' => $document->getProgressMsg() ?? '',
                'status' => $document->getStatus(),
                'chunk_count' => $document->getChunkCount() ?? 0,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 处理批量文档分块同步
     *
     * @return array<string, mixed>
     */
    private function processBatchChunkSync(Dataset $dataset): array
    {
        return $this->syncService->processBatchChunkSync($dataset);
    }

    /**
     * 处理单个文档分块同步
     *
     * @return array<string, mixed>
     */
    private function processSingleDocumentChunkSync(Dataset $dataset, Document $document): array
    {
        return $this->syncService->processSingleDocumentChunkSync($dataset, $document);
    }

    /**
     * 从API更新文档状态
     */
    private function updateDocumentStatusFromApi(Document $document, Dataset $dataset): void
    {
        $this->operationService->updateDocumentStatusFromApi($document, $dataset);
    }

    /**
     * __invoke方法 - 满足测试基类要求
     *
     * 注意：这是一个多方法控制器，不应该通过__invoke调用。
     * 每个具体操作都有对应的路由和方法。
     */
    #[Route(path: '/invoke', name: 'invoke', methods: ['GET'])]
    public function __invoke(int $datasetId): Response
    {
        // 重定向到文档列表页面
        return $this->redirectToRoute('dataset_documents_index', ['datasetId' => $datasetId]);
    }
}
