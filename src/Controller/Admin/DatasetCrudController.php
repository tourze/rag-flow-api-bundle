<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Service\DatasetService;
use Tourze\RAGFlowApiBundle\Service\LocalDataSyncService;
use Tourze\RAGFlowApiBundle\Service\RAGFlowFacadeService;
use Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManagerInterface;

/**
 * 数据集管理CRUD Controller
 *
 * 基于Dataset实体的CRUD Controller，直接操作本地数据库并同步到RAGFlow API
 *
 * @extends AbstractCrudController<Dataset>
 */
#[AdminCrud(routePath: '/rag-flow/dataset', routeName: 'rag_flow_dataset')]
final class DatasetCrudController extends AbstractCrudController implements ServiceSubscriberInterface
{
    private ?DatasetService $datasetService = null;

    public function __construct(
        private readonly RAGFlowInstanceManagerInterface $instanceManager,
        private readonly LocalDataSyncService $localDataSyncService,
        private readonly RAGFlowFacadeService $ragflowFacade,
    ) {
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            RAGFlowInstanceManagerInterface::class,
            LocalDataSyncService::class,
            RAGFlowFacadeService::class,
        ]);
    }

    private function getDatasetService(): DatasetService
    {
        if (null === $this->datasetService) {
            $this->datasetService = new DatasetService($this->instanceManager, $this->localDataSyncService);
        }

        return $this->datasetService;
    }

    public static function getEntityFqcn(): string
    {
        return Dataset::class;
    }

    /**
     * 重写查询构建器，在显示列表前同步远程数据
     */
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        // 先同步远程数据到本地
        $this->syncRemoteDatasets();

        // 返回标准的查询构建器
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
    }

    /**
     * 同步远程数据集到本地数据库
     */
    private function syncRemoteDatasets(): void
    {
        try {
            // 获取远程数据集列表
            $remoteDatasets = $this->getDatasetService()->list();

            // 数据已经通过LocalDataSyncService自动同步到本地数据库
            // list方法会调用syncDatasetFromApi来保存到本地

            // $this->addFlash('success', sprintf('已同步 %d 个远程数据集', count($remoteDatasets)));
        } catch (\Exception $e) {
            // 同步失败不影响列表显示
            // 在生产环境使用日志服务记录错误，测试环境静默忽略
            // 在测试环境中，session可能未初始化，跳过flash消息
            try {
                $this->addFlash('warning', '远程数据同步失败，显示本地数据');
            } catch (\Exception) {
                // 忽略flash消息失败（测试环境）
            }
        }
    }

    public function configureFields(string $pageName): iterable
    {
        $isNewPage = Crud::PAGE_NEW === $pageName;

        yield IntegerField::new('id', 'ID')
            ->hideOnForm()
            ->setHelp('数据集在本地数据库的主键ID')
        ;

        yield TextField::new('remoteId', '远程ID')
            ->hideOnForm()
            ->setHelp('数据集在RAGFlow系统中的唯一标识符')
        ;

        yield TextField::new('name', '数据集名称')
            ->setRequired(true)
            ->setHelp('为数据集设置一个便于识别的名称，最长255个字符')
            ->setColumns(6)
            ->setFormTypeOption('attr', [
                'placeholder' => '例如：客户服务知识库',
                'maxlength' => 255,
                'pattern' => '.{1,255}',
                'title' => '数据集名称长度应在1-255个字符之间',
                'required' => true,
            ])
        ;

        yield TextareaField::new('description', '数据集描述')
            ->setHelp('详细描述这个数据集的用途、内容和应用场景')
            ->setColumns(12)
            ->setNumOfRows(4)
            ->setFormTypeOption('attr', [
                'placeholder' => '例如：用于客户服务的常见问题和解答库，包含产品介绍、使用说明、故障排除等内容...',
                'maxlength' => 2000,
            ])
        ;

        yield ChoiceField::new('chunkMethod', '分块方法')
            ->setChoices([
                '默认' => 'naive',
                '书籍' => 'book',
                '邮件' => 'email',
                '法律文档' => 'laws',
                '手册' => 'manual',
                '单独处理' => 'one',
                '论文' => 'paper',
                '图片' => 'picture',
                '演示文稿' => 'presentation',
                '问答' => 'qa',
                '表格' => 'table',
                '标签' => 'tag',
            ])
            ->setHelp('选择文档分块的策略，默认为naive')
            ->setColumns(4)
            ->setRequired(false)
            ->allowMultipleChoices(false)
            ->setFormTypeOption('placeholder', $isNewPage ? '默认' : null)
        ;

        yield ChoiceField::new('embeddingModel', '嵌入模型')
            ->setChoices([
                'BAAI/bge-large-zh-v1.5' => 'BAAI/bge-large-zh-v1.5@BAAI',
                '自定义输入' => '',
            ])
            ->setHelp('选择用于生成向量嵌入的模型，格式：model_name@model_factory，默认BAAI/bge-large-zh-v1.5')
            ->setColumns(6)
            ->setRequired(false)
            ->allowMultipleChoices(false)
            ->setFormTypeOption('placeholder', $isNewPage ? '默认BAAI/bge-large-zh-v1.5' : null)
        ;

        yield TextField::new('status', '状态')
            ->hideOnForm()
            ->setHelp('数据集的当前状态')
        ;

        yield DateTimeField::new('remoteCreateTime', '远程创建时间')
            ->hideOnForm()
            ->setHelp('数据集在RAGFlow中的创建时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('remoteUpdateTime', '远程更新时间')
            ->hideOnForm()
            ->setHelp('数据集在RAGFlow中的最后更新时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('lastSyncTime', '最后同步时间')
            ->hideOnForm()
            ->setHelp('数据最后同步到RAGFlow的时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield IntegerField::new('documentCount', '文档数量')
            ->hideOnForm()
            ->setHelp('数据集包含的文档数量')
            ->formatValue(function ($value, $entity) {
                if ($entity instanceof Dataset) {
                    $count = count($entity->getDocuments());

                    return $count > 0 ? sprintf('<span class="badge bg-info">%d</span>', $count) :
                                       '<span class="text-muted">0</span>';
                }

                return '0';
            })
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('name')
            ->add('chunkMethod')
            ->add('status')
        ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('数据集')
            ->setEntityLabelInPlural('数据集')
            ->setPageTitle('index', '数据集管理')
            ->setPageTitle('new', '创建数据集')
            ->setPageTitle('edit', '编辑数据集')
            ->setPageTitle('detail', '数据集详情')
            ->setDefaultSort(['id' => 'DESC'])
            ->showEntityActionsInlined()
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $manageDocumentsAction = Action::new('manageDocuments', '管理文档', 'fa fa-folder-open')
            ->linkToCrudAction('manageDocuments')
            ->displayIf(static function (Dataset $entity): bool {
                return null !== $entity->getId();
            })
            ->setCssClass('btn btn-sm btn-outline-success')
        ;

        return $actions
            // 添加DETAIL到INDEX页面（INDEX默认不包含）
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            // 移除默认的删除动作（明确隐藏删除功能）- 仅当DELETE动作存在时才移除
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
            // 添加自定义动作
            ->add(Crud::PAGE_INDEX, $manageDocumentsAction)
            ->add(Crud::PAGE_DETAIL, $manageDocumentsAction)
        ;
    }

    /**
     * 新增实体后的处理 - 同步到RAGFlow API
     * @param mixed $entityInstance
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Dataset) {
            // 设置基本信息
            $entityInstance->setRagFlowInstance($this->instanceManager->getDefaultInstance());
            $entityInstance->setLastSyncTime(new \DateTimeImmutable());

            // 让EasyAdmin自动处理persist和flush
            parent::persistEntity($entityManager, $entityInstance);

            // 在实体已经有ID后，同步到RAGFlow API
            try {
                $data = $this->convertDatasetToApiData($entityInstance);
                $dataset = $this->getDatasetService()->create($data);

                // 更新本地记录的remoteId
                $entityInstance->setRemoteId($dataset->getRemoteId());
                $entityInstance->setRemoteCreateTime($dataset->getRemoteCreateTime());
                $entityInstance->setRemoteUpdateTime($dataset->getRemoteUpdateTime());
                $entityInstance->setStatus('synced');

                $entityManager->flush();
            } catch (\Exception $e) {
                // API同步失败，但不影响本地操作
                // 设置状态表示同步失败
                $entityInstance->setStatus('sync_failed');
                $entityManager->flush();

                // 在测试环境中，session可能未初始化，跳过flash消息
                try {
                    $this->addFlash('warning', '数据集已创建，但与RAGFlow同步失败。请稍后重试同步。');
                } catch (\Exception) {
                    // 忽略flash消息失败（测试环境）
                }
            }
        }
    }

    /**
     * 更新实体后的处理 - 同步到RAGFlow API
     * @param mixed $entityInstance
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Dataset) {
            return;
        }

        $entityInstance->setLastSyncTime(new \DateTimeImmutable());
        parent::updateEntity($entityManager, $entityInstance);

        if ($this->shouldSyncToRemote($entityInstance)) {
            $this->syncDatasetToRemote($entityManager, $entityInstance);
        }
    }

    private function shouldSyncToRemote(Dataset $dataset): bool
    {
        return null !== $dataset->getRemoteId() && '' !== $dataset->getRemoteId();
    }

    private function syncDatasetToRemote(EntityManagerInterface $entityManager, Dataset $dataset): void
    {
        try {
            $data = $this->convertDatasetToApiData($dataset);
            $remoteId = $dataset->getRemoteId();
            if (null === $remoteId || '' === $remoteId) {
                throw new \RuntimeException('Dataset remote ID is required for sync');
            }
            $this->getDatasetService()->update($remoteId, $data);
            $this->handleSyncSuccess($entityManager, $dataset);
        } catch (\Exception $e) {
            $this->handleSyncFailure($entityManager, $dataset);
        }
    }

    private function handleSyncSuccess(EntityManagerInterface $entityManager, Dataset $dataset): void
    {
        $dataset->setStatus('synced');
        $entityManager->flush();
    }

    private function handleSyncFailure(EntityManagerInterface $entityManager, Dataset $dataset): void
    {
        $dataset->setStatus('sync_failed');
        $entityManager->flush();

        try {
            $this->addFlash('warning', '数据集已更新，但与RAGFlow同步失败。');
        } catch (\Exception) {
            // 忽略flash消息失败（测试环境）
        }
    }

    /**
     * 删除实体的处理 - 只删除本地数据，不调用远程API
     * @param mixed $entityInstance
     */
    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Dataset) {
            // 检查数据集是否有关联的文档
            $documentCount = count($entityInstance->getDocuments());
            if ($documentCount > 0) {
                // 如果有关联文档，抛出异常阻止删除
                throw new \RuntimeException(sprintf('数据集 "%s" 包含 %d 个文档，请先删除所有文档后再删除数据集。', $entityInstance->getName(), $documentCount));
            }

            // 注释：已屏蔽远程API删除逻辑，避免误删除远程数据
            // if (null !== $entityInstance->getRemoteId() && '' !== $entityInstance->getRemoteId()) {
            //     $this->getDatasetService()->delete($entityInstance->getRemoteId());
            // }
        }

        // 调用父类方法执行标准的EasyAdmin删除流程
        if ($entityInstance instanceof Dataset) {
            parent::deleteEntity($entityManager, $entityInstance);
        }
    }

    /**
     * 将Dataset实体转换为API请求数据
     *
     * @return array<string, mixed>
     */
    private function convertDatasetToApiData(Dataset $entity): array
    {
        return [
            'name' => $entity->getName(),
            'description' => $entity->getDescription(),
            'parser_method' => $entity->getParserMethod() ?? 'intelligent',
            'chunk_method' => $entity->getChunkMethod() ?? 'naive',
            'chunk_size' => $entity->getChunkSize() ?? 1024,
            'language' => $entity->getLanguage() ?? 'zh',
            'embedding_model' => $entity->getEmbeddingModel() ?? 'text-embedding-3-small',
            'similarity_threshold' => $entity->getSimilarityThreshold() ?? 70,
        ];
    }

    /**
     * 管理数据集文档
     */
    #[AdminAction(routePath: '/manage-documents/{entityId}', routeName: 'manage_documents')]
    public function manageDocuments(AdminContext $context): Response
    {
        try {
            $datasetId = $this->extractDatasetId($context);
            $url = $this->getDocumentManagementUrl($datasetId);

            return $this->redirect($url);
        } catch (\InvalidArgumentException $e) {
            $this->addFlashMessageSafely('danger', $e->getMessage());

            return $this->redirect($this->generateUrl('admin'));
        }
    }

    /**
     * 从AdminContext提取数据集ID
     */
    private function extractDatasetId(AdminContext $context): int
    {
        $dataset = $context->getEntity()->getInstance();

        if (!$dataset instanceof Dataset) {
            throw new \InvalidArgumentException('无法获取数据集实体');
        }

        $datasetId = $dataset->getDatabaseId();
        if (null === $datasetId) {
            throw new \InvalidArgumentException('数据集ID无效');
        }

        return $datasetId;
    }

    /**
     * 获取文档管理URL，支持回退机制
     */
    private function getDocumentManagementUrl(int $datasetId): string
    {
        try {
            $url = $this->ragflowFacade->getDocumentManagementUrl($datasetId);

            if ($this->isValidRoute($url)) {
                return $url;
            }
        } catch (\Exception) {
            // 获取URL失败，使用回退URL
        }

        return $this->generateUrl('admin_rag_flow_document_index', [
            'filters[dataset]' => $datasetId,
        ]);
    }

    /**
     * 验证URL是否为有效路由
     */
    private function isValidRoute(string $url): bool
    {
        // 如果不是回退路径，认为是有效的
        if (!str_starts_with($url, '/admin/datasets/')) {
            return true;
        }

        // 验证回退路径是否有对应路由
        try {
            $router = $this->container->get('router');
            if (null === $router) {
                return false;
            }

            // 使用更安全的方式调用router
            if (is_object($router) && method_exists($router, 'match')) {
                $router->match($url);

                return true;
            }
        } catch (\Exception) {
            // 路由不存在或调用失败
        }

        return false;
    }

    /**
     * 安全地添加Flash消息，避免测试环境异常
     */
    private function addFlashMessageSafely(string $type, string $message): void
    {
        try {
            $this->addFlash($type, $message);
        } catch (\Exception) {
            // 忽略flash消息失败（测试环境）
        }
    }
}
