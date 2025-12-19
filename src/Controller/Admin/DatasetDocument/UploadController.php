<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Admin\DatasetDocument;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Service\DocumentOperationService;
use Tourze\RAGFlowApiBundle\Service\DocumentValidator;

/**
 * 文档上传页面
 */
final class UploadController extends AbstractController
{
    public function __construct(
        private readonly DocumentOperationService $operationService,
        private readonly DocumentValidator $validator,
    ) {
    }

    #[Route(path: '/admin/datasets/{datasetId}/documents/upload', name: 'dataset_documents_upload', methods: ['GET', 'POST'])]
    public function __invoke(int $datasetId, Request $request): Response
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
}
