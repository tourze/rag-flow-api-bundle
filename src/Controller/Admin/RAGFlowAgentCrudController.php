<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowAgent;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Repository\RAGFlowInstanceRepository;
use Tourze\RAGFlowApiBundle\Service\AgentApiService;

/**
 * RAGFlow智能体管理控制器
 *
 * @extends AbstractCrudController<RAGFlowAgent>
 */
#[AdminCrud(routePath: '/rag-flow/agent', routeName: 'rag_flow_agent')]
final class RAGFlowAgentCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly AgentApiService $agentApiService,
        private readonly RAGFlowInstanceRepository $instanceRepository,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return RAGFlowAgent::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('RAGFlow智能体')
            ->setEntityLabelInPlural('RAGFlow智能体')
            ->setPageTitle('index', 'RAGFlow智能体管理')
            ->setPageTitle('new', '创建新智能体')
            ->setPageTitle('edit', '编辑智能体')
            ->setPageTitle('detail', '智能体详情')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $syncAction = Action::new('sync', '同步到远程', 'fas fa-sync')
            ->linkToCrudAction('syncToRemote')
            ->addCssClass('btn btn-warning')
            ->displayIf(static function (RAGFlowAgent $agent): bool {
                return 'published' !== $agent->getStatus();
            })
        ;

        $batchSyncAction = Action::new('batchSync', '批量同步', 'fas fa-sync-alt')
            ->linkToCrudAction('batchSync')
            ->addCssClass('btn btn-info')
            ->createAsGlobalAction()
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, $syncAction)
            ->add(Crud::PAGE_INDEX, $batchSyncAction)
            ->add(Crud::PAGE_DETAIL, $syncAction)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('ragFlowInstance')
            ->add('status')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id', 'ID')->onlyOnIndex(),
            TextField::new('title', '标题')->setRequired(true),
            TextareaField::new('description', '描述')->hideOnIndex(),
            AssociationField::new('ragFlowInstance', 'RAGFlow实例')
                ->setRequired(true)
                ->autocomplete()
                ->formatValue(static function (?RAGFlowInstance $instance): string {
                    return $instance?->getName() ?? '';
                }),
            ChoiceField::new('status', '状态')
                ->setChoices([
                    '草稿' => 'draft',
                    '已发布' => 'published',
                    '已归档' => 'archived',
                    '同步失败' => 'sync_failed',
                ])
                ->renderAsBadges([
                    'draft' => 'secondary',
                    'published' => 'success',
                    'archived' => 'warning',
                    'sync_failed' => 'danger',
                ]),
        ];

        if (Crud::PAGE_NEW === $pageName || Crud::PAGE_EDIT === $pageName) {
            $fields[] = CodeEditorField::new('dsl', 'DSL配置')
                ->setLanguage('yaml') // 使用支持的语言
                ->setNumOfRows(20)
                ->setHelp('Canvas DSL对象配置，JSON格式')
            ;
        }

        if (Crud::PAGE_DETAIL === $pageName || Crud::PAGE_INDEX === $pageName) {
            $fields[] = TextField::new('remoteId', '远程ID')->hideOnIndex();
            $fields[] = DateTimeField::new('createTime', '创建时间')->hideOnForm();
            $fields[] = DateTimeField::new('lastSyncTime', '最后同步时间')->hideOnForm();

            if (Crud::PAGE_DETAIL === $pageName) {
                $fields[] = TextareaField::new('syncErrorMessage', '同步错误信息')->hideOnIndex();
            }
        }

        return $fields;
    }

    /**
     * 同步单个智能体到远程
     */
    #[AdminAction(routePath: '/sync-to-remote/{entityId}', routeName: 'sync_to_remote')]
    public function syncToRemote(AdminContext $context): Response
    {
        $agent = $context->getEntity()->getInstance();

        if (!$agent instanceof RAGFlowAgent) {
            $this->addFlash('danger', '无效的智能体');

            return $this->redirectToRoute('admin');
        }

        $result = $this->agentApiService->updateAgent($agent);

        if (true === $result['success']) {
            $this->addFlash('success', $result['message']);
        } else {
            $message = is_string($result['message'] ?? null) ? $result['message'] : '未知错误';
            $this->addFlash('danger', '同步失败: ' . $message);
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl()
        ;

        return new RedirectResponse($url);
    }

    /**
     * 批量同步智能体
     */
    #[AdminAction(routePath: '/batch-sync', routeName: 'batch_sync')]
    public function batchSync(AdminContext $context): Response
    {
        $instances = $this->instanceRepository->findBy(['enabled' => true]);

        if (0 === count($instances)) {
            $this->addFlash('warning', '没有找到启用的RAGFlow实例');

            return $this->redirectToRoute('admin');
        }

        [$totalSuccess, $totalFailure] = $this->processBatchSync($instances);
        $this->addBatchSyncFlashMessage($totalSuccess, $totalFailure);

        return $this->redirectToCrudIndex();
    }

    /**
     * @param RAGFlowInstance[] $instances
     * @return array{int, int}
     */
    private function processBatchSync(array $instances): array
    {
        $totalSuccess = 0;
        $totalFailure = 0;

        foreach ($instances as $instance) {
            $result = $this->agentApiService->syncAllAgents($instance);

            if (isset($result['data']) && is_array($result['data'])) {
                $data = $result['data'];
                $successCount = $data['success_count'] ?? 0;
                $totalSuccess += is_int($successCount) ? $successCount : 0;
                $failureCount = $data['failure_count'] ?? 0;
                $totalFailure += is_int($failureCount) ? $failureCount : 0;
            }
        }

        return [$totalSuccess, $totalFailure];
    }

    private function addBatchSyncFlashMessage(int $totalSuccess, int $totalFailure): void
    {
        if (0 === $totalFailure) {
            $this->addFlash('success', sprintf('批量同步完成: %d个成功', $totalSuccess));
        } else {
            $this->addFlash('warning', sprintf('批量同步完成: %d个成功, %d个失败', $totalSuccess, $totalFailure));
        }
    }

    private function redirectToCrudIndex(): RedirectResponse
    {
        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl()
        ;

        return new RedirectResponse($url);
    }

    public function persistEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        // DSL is always an array, no conversion needed
        parent::persistEntity($entityManager, $entityInstance);

        // 自动同步到远程（如果实例启用了自动同步）
        if ($entityInstance->getRagFlowInstance()->isEnabled()) {
            $result = $this->agentApiService->createAgent($entityInstance);

            if (true === $result['success']) {
                $this->addFlash('success', '智能体创建成功并已同步到远程');
            } else {
                $message = is_string($result['message'] ?? null) ? $result['message'] : '未知错误';
                $this->addFlash('warning', '智能体创建成功，但同步失败: ' . $message);
            }
        }
    }

    public function updateEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        // DSL is always an array, no conversion needed
        parent::updateEntity($entityManager, $entityInstance);

        // 自动同步到远程（如果实例启用了自动同步）
        if ($entityInstance->getRagFlowInstance()->isEnabled()) {
            $result = $this->agentApiService->updateAgent($entityInstance);

            if (true === $result['success']) {
                $this->addFlash('success', '智能体更新成功并已同步到远程');
            } else {
                $message = is_string($result['message'] ?? null) ? $result['message'] : '未知错误';
                $this->addFlash('warning', '智能体更新成功，但同步失败: ' . $message);
            }
        }
    }

    public function deleteEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        // 先从远程删除
        if (null !== $entityInstance->getRemoteId()) {
            $result = $this->agentApiService->deleteAgent($entityInstance);

            if (true !== $result['success']) {
                $message = is_string($result['message'] ?? null) ? $result['message'] : '未知错误';
                $this->addFlash('warning', '远程删除失败，但本地记录将被删除: ' . $message);
            }
        }

        parent::deleteEntity($entityManager, $entityInstance);

        $this->addFlash('success', '智能体删除成功');
    }
}
