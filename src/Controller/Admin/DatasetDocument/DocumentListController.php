<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Admin\DatasetDocument;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Service\DocumentOperationService;
use Tourze\RAGFlowApiBundle\Service\DocumentValidator;

/**
 * 数据集文档管理主页面
 */
final class DocumentListController extends AbstractController
{
    public function __construct(
        private readonly DocumentOperationService $operationService,
        private readonly DocumentValidator $validator,
    ) {
    }

    #[Route(path: '/admin/datasets/{datasetId}/documents/', name: 'dataset_documents_index', methods: ['GET'])]
    public function __invoke(int $datasetId, Request $request): Response
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
}
