<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Admin\DatasetDocument;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Service\DocumentOperationService;
use Tourze\RAGFlowApiBundle\Service\DocumentValidator;

/**
 * 删除单个文档
 */
final class DeleteController extends AbstractController
{
    public function __construct(
        private readonly DocumentOperationService $operationService,
        private readonly DocumentValidator $validator,
    ) {
    }

    #[Route(path: '/admin/datasets/{datasetId}/documents/{documentId}/delete', name: 'dataset_documents_delete', methods: ['POST'])]
    public function __invoke(int $datasetId, int $documentId): Response
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
     * 删除文档(本地+远程+文件)
     */
    private function deleteDocument(Document $document): void
    {
        $this->operationService->deleteDocument($document);
    }
}
