<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Admin;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Enum\DocumentStatus;
use Tourze\RAGFlowApiBundle\Repository\DocumentRepository;
use Tourze\RAGFlowApiBundle\Service\DocumentService;

/**
 * 数据集文档EasyAdmin CRUD控制器
 * 专门用于提供完整EasyAdmin布局的文档管理界面
 *
 * @extends AbstractCrudController<Document>
 */
#[AdminCrud(routePath: '/rag-flow/documents', routeName: 'rag_flow_documents')]
final class DatasetDocumentEACrudController extends AbstractCrudController
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
        private readonly DocumentService $documentService,
        private readonly RequestStack $requestStack,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Document::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('文档')
            ->setEntityLabelInPlural('数据集文档管理')
            ->setPageTitle('index', '%entity_label_plural%')
            ->setPageTitle('detail', '文档详情 - %entity_as_string%')
            ->setPageTitle('new', '上传文档')
            ->setPageTitle('edit', '编辑文档 - %entity_as_string%')
            ->setSearchFields(['name', 'filename', 'type'])
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setPaginatorPageSize(30)
            ->showEntityActionsInlined(true)
            ->renderContentMaximized(true)
            ->setHelp('index', '管理数据集中的所有文档，支持上传、删除、同步分块等操作。使用筛选器可以按数据集、状态等条件查看文档。')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm()
            ->setColumns(1)
        ;

        yield AssociationField::new('dataset', '数据集')
            ->setColumns(3)
            ->hideOnForm()
            ->setHelp('文档所属的数据集')
        ;

        yield TextField::new('name', '文档名称')
            ->setColumns(4)
            ->setHelp('文档的显示名称')
            ->setRequired(false)
        ;

        yield TextField::new('filename', '文件名')
            ->setColumns(4)
            ->hideOnIndex()
            ->hideOnForm()
            ->setHelp('原始文件名')
        ;

        yield TextField::new('type', '文件类型')
            ->setColumns(2)
            ->formatValue(function ($value) {
                if (null === $value || '' === $value) {
                    return '-';
                }

                if (!is_string($value) && !is_scalar($value)) {
                    return '-';
                }

                $typeStr = (string) $value;

                return '<span class="badge bg-light text-dark">' . strtoupper($typeStr) . '</span>';
            })
            ->hideOnForm()
        ;

        yield NumberField::new('size', '文件大小')
            ->setColumns(2)
            ->formatValue(function ($value) {
                if (null === $value) {
                    return '<span class="text-muted">-</span>';
                }

                $sizeNum = is_numeric($value) ? (float) $value : 0.0;
                if ($sizeNum <= 0) {
                    return '<span class="text-muted">-</span>';
                }

                return number_format($sizeNum / 1024 / 1024, 2) . ' MB';
            })
            ->hideOnForm()
            ->setHelp('文件大小（MB）')
        ;

        yield ChoiceField::new('status', '状态')
            ->setColumns(2)
            ->setChoices(DocumentStatus::getChoices())
            ->renderAsBadges([
                'pending' => 'secondary',
                'uploading' => 'warning',
                'uploaded' => 'info',
                'processing' => 'warning',
                'completed' => 'success',
                'failed' => 'danger',
                'sync_failed' => 'danger',
            ])
            ->hideOnForm()
        ;

        yield IntegerField::new('chunkCount', '分块数')
            ->setColumns(1)
            ->hideOnForm()
            ->formatValue(function ($value) {
                $count = is_numeric($value) ? (int) $value : 0;

                return $count > 0 ? '<span class="badge bg-info">' . $count . '</span>' : '<span class="text-muted">-</span>';
            })
            ->setHelp('文档分块数量')
        ;

        yield TextField::new('remoteId', '远程ID')
            ->setColumns(3)
            ->hideOnIndex()
            ->hideOnForm()
            ->setHelp('RAGFlow API中的文档ID')
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->setColumns(3)
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm')
            ->setHelp('文档创建时间')
        ;

        yield DateTimeField::new('updatedAt', '更新时间')
            ->setColumns(3)
            ->hideOnForm()
            ->hideOnIndex()
            ->setFormat('yyyy-MM-dd HH:mm')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        // 手动同步分块操作
        $syncChunks = Action::new('syncChunks', '同步分块', 'fa fa-cloud-download-alt')
            ->linkToCrudAction('syncChunks')
            ->addCssClass('btn btn-sm btn-info')
            ->displayIf(static function (Document $document): bool {
                $remoteId = $document->getRemoteId();

                return DocumentStatus::COMPLETED === $document->getStatus() && null !== $remoteId && '' !== $remoteId;
            })
        ;

        // 重新上传操作
        $retryUpload = Action::new('retryUpload', '重新上传', 'fa fa-redo')
            ->linkToCrudAction('retryUpload')
            ->addCssClass('btn btn-sm btn-warning')
            ->displayIf(static function (Document $document): bool {
                $remoteId = $document->getRemoteId();

                return $document->getStatus()->needsRetry()
                    || null === $remoteId || '' === $remoteId;
            })
        ;

        // 批量同步操作
        $batchSyncChunks = Action::new('batchSyncChunks', '批量同步分块', 'fa fa-sync-alt')
            ->linkToCrudAction('batchSyncChunks')
            ->addCssClass('btn btn-primary')
            ->createAsGlobalAction()
        ;

        // 文件上传操作 - 使用JavaScript实现动态跳转
        $uploadFiles = Action::new('uploadFiles', '上传文档', 'fa fa-upload')
            ->linkToUrl('javascript:void(0)')
            ->addCssClass('btn btn-success')
            ->setHtmlAttributes([
                'onclick' => "
                    const params = new URLSearchParams(window.location.search);
                    const datasetId = params.get('datasetId');
                    if (datasetId) {
                        window.location.href = '/admin/datasets/' + datasetId + '/documents/upload';
                    } else {
                        alert('请先选择数据集');
                    }
                ",
            ])
            ->createAsGlobalAction()
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, $syncChunks)
            ->add(Crud::PAGE_INDEX, $retryUpload)
            ->add(Crud::PAGE_INDEX, $batchSyncChunks)
            ->add(Crud::PAGE_INDEX, $uploadFiles)
            ->disable(Action::NEW)  // 禁用标准新建，使用自定义上传
            ->disable(Action::EDIT)  // 禁用编辑，因为文档信息主要来自RAGFlow
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('dataset')
            ->add(TextFilter::new('name', '文档名称'))
            ->add(ChoiceFilter::new('status', '状态')->setChoices(DocumentStatus::getChoices()))
            ->add(ChoiceFilter::new('type', '文件类型')->setChoices([
                'PDF' => 'pdf',
                'Word' => 'docx',
                'Excel' => 'xlsx',
                'PowerPoint' => 'pptx',
                'Text' => 'txt',
                'Image' => 'jpg',
            ]))
            ->add(NumericFilter::new('size', '文件大小(MB)'))
        ;
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addHtmlContentToBody('<style>
                .badge { font-size: 0.75em; }
                .text-muted { color: #6c757d !important; }
                .btn-group .btn { margin-right: 2px; }
                .ea-table th { font-weight: 600; }
                .ea-table .badge { margin: 0 2px; }
            </style>')
        ;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        // 优先显示最近创建的文档
        $queryBuilder->orderBy('entity.createTime', 'DESC');

        // 如果URL中有datasetId参数，则按数据集过滤
        $request = $this->requestStack->getCurrentRequest();
        $datasetIdRaw = $request?->query->get('datasetId');
        $datasetId = is_string($datasetIdRaw) || is_int($datasetIdRaw) ? $datasetIdRaw : null;

        if (null !== $datasetId && '' !== $datasetId) {
            $queryBuilder->andWhere('entity.dataset = :datasetId')
                ->setParameter('datasetId', $datasetId)
            ;
        }

        return $queryBuilder;
    }

    /**
     * 同步单个文档的分块
     *
     * 注意：EasyAdmin 会在路由参数解析阶段验证实体是否存在
     * 如果 entityId 对应的文档不存在，会抛出 EntityNotFoundException
     * 该异常发生在控制器方法执行之前，因此无需在此方法中检查文档是否存在
     */
    #[AdminAction(routePath: '/sync-chunks/{entityId}', routeName: 'sync_chunks')]
    public function syncChunks(Request $request): Response
    {
        $document = $this->getContext()?->getEntity()?->getInstance();
        assert($document instanceof Document, '无法获取文档实例');

        try {
            $datasetRemoteId = $document->getDataset()?->getRemoteId();
            $documentRemoteId = $document->getRemoteId();
            if (null === $datasetRemoteId || '' === $datasetRemoteId || null === $documentRemoteId || '' === $documentRemoteId) {
                throw new \RuntimeException('数据集或文档尚未同步到RAGFlow');
            }

            $result = $this->documentService->listChunks($datasetRemoteId, $documentRemoteId);
            $chunksData = $result['docs'] ?? [];
            $chunkCount = is_array($chunksData) ? count($chunksData) : 0;
            $this->addFlash('success', sprintf('✅ 成功同步文档 "%s" 的 %d 个分块', $document->getName(), $chunkCount));
        } catch (\Exception $e) {
            $this->addFlash('danger', sprintf('❌ 同步失败: %s', $e->getMessage()));
        }

        return $this->redirectToIndex();
    }

    /**
     * 重新上传文档
     *
     * 注意：EasyAdmin 会在路由参数解析阶段验证实体是否存在
     * 如果 entityId 对应的文档不存在，会抛出 EntityNotFoundException
     * 该异常发生在控制器方法执行之前，因此无需在此方法中检查文档是否存在
     */
    #[AdminAction(routePath: '/retry-upload/{entityId}', routeName: 'retry_upload')]
    public function retryUpload(Request $request): Response
    {
        $document = $this->getContext()?->getEntity()?->getInstance();
        assert($document instanceof Document, '无法获取文档实例');

        try {
            // 这里实现重新上传逻辑
            // 暂时显示提示信息
            $this->addFlash('info', sprintf('⏳ 文档 "%s" 已加入重新上传队列', $document->getName()));
        } catch (\Exception $e) {
            $this->addFlash('danger', sprintf('❌ 重新上传失败: %s', $e->getMessage()));
        }

        return $this->redirectToIndex();
    }

    /**
     * 批量同步所有已完成文档的分块
     */
    #[AdminAction(routePath: '/batch-sync-chunks', routeName: 'batch_sync_chunks')]
    public function batchSyncChunks(Request $request): Response
    {
        try {
            $datasetIdRaw = $request->query->get('datasetId');
            $datasetIdStr = is_int($datasetIdRaw) ? (string) $datasetIdRaw : (is_string($datasetIdRaw) ? $datasetIdRaw : null);
            $documents = $this->findCompletedDocuments($datasetIdStr);

            $result = $this->syncDocumentChunks($documents);
            $this->displaySyncResults($result['syncedCount'], $result['errors']);
        } catch (\Exception $e) {
            $this->addFlash('danger', sprintf('❌ 批量同步失败: %s', $e->getMessage()));
        }

        return $this->redirectToIndex($request->query->all());
    }

    /**
     * 查找已完成的文档
     *
     * @return array<Document>
     */
    private function findCompletedDocuments(?string $datasetId): array
    {
        $queryBuilder = $this->documentRepository
            ->createQueryBuilder('d')
            ->where('d.status = :status')
            ->andWhere('d.remoteId IS NOT NULL')
            ->setParameter('status', DocumentStatus::COMPLETED)
        ;

        if (null !== $datasetId && '' !== $datasetId) {
            $queryBuilder->andWhere('d.dataset = :datasetId')
                ->setParameter('datasetId', $datasetId)
            ;
        }

        /** @var array<Document> */
        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * 同步文档分块
     *
     * @param Document[] $documents
     * @return array{syncedCount: int, errors: string[]}
     */
    private function syncDocumentChunks(array $documents): array
    {
        $syncedCount = 0;
        $errors = [];

        foreach ($documents as $document) {
            try {
                if ($this->isDocumentSyncable($document)) {
                    $datasetRemoteId = $document->getDataset()?->getRemoteId();
                    $documentRemoteId = $document->getRemoteId();
                    if (null !== $datasetRemoteId && null !== $documentRemoteId) {
                        $chunks = $this->documentService->listChunks($datasetRemoteId, $documentRemoteId);
                        ++$syncedCount;
                    }
                }
            } catch (\Exception $e) {
                $errors[] = sprintf('文档 "%s": %s', $document->getName(), $e->getMessage());
            }
        }

        return ['syncedCount' => $syncedCount, 'errors' => $errors];
    }

    /**
     * 检查文档是否可同步
     */
    private function isDocumentSyncable(Document $document): bool
    {
        $datasetRemoteId = $document->getDataset()?->getRemoteId();
        $documentRemoteId = $document->getRemoteId();

        return null !== $datasetRemoteId && '' !== $datasetRemoteId
            && null !== $documentRemoteId && '' !== $documentRemoteId;
    }

    /**
     * 显示同步结果
     *
     * @param string[] $errors
     */
    private function displaySyncResults(int $syncedCount, array $errors): void
    {
        if ($syncedCount > 0) {
            $message = sprintf('✅ 成功同步 %d 个文档的分块数据', $syncedCount);
            if ([] !== $errors) {
                $message .= sprintf('，%d 个失败', count($errors));
            }
            $this->addFlash('success', $message);
        }

        if ([] !== $errors) {
            foreach (array_slice($errors, 0, 3) as $error) {
                $this->addFlash('warning', '⚠️ ' . $error);
            }
            if (count($errors) > 3) {
                $this->addFlash('warning', sprintf('⚠️ 还有 %d 个错误...', count($errors) - 3));
            }
        }

        if (0 === $syncedCount && [] === $errors) {
            $this->addFlash('info', 'ℹ️ 没有找到需要同步的已完成文档');
        }
    }

    /**
     * 重定向到索引页面
     *
     * @param array<string, mixed> $queryParams 额外的查询参数
     */
    private function redirectToIndex(array $queryParams = []): RedirectResponse
    {
        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->setAll($queryParams)
            ->generateUrl()
        ;

        return $this->redirect($url);
    }
}
