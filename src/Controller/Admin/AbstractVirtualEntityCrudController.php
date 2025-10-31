<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 虚拟实体CRUD控制器抽象基类
 *
 * 用于处理不对应数据库表的虚拟实体，通过API进行数据操作
 * 子类需要实现具体的API操作方法和数据转换逻辑
 *
 * @template TEntity of object
 * @extends AbstractCrudController<TEntity>
 */
abstract class AbstractVirtualEntityCrudController extends AbstractCrudController
{
    /**
     * 获取所有实体数据（用于列表页面）
     *
     * @param array<string, mixed> $filters
     * @return array<int, mixed>
     */
    abstract protected function fetchAllEntities(array $filters = []): array;

    /**
     * 根据ID获取单个实体数据
     *
     * @return array<string, mixed>|null
     */
    abstract protected function fetchEntityById(string $id): ?array;

    /**
     * 创建新实体
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    abstract protected function createVirtualEntity(array $data): array;

    /**
     * 更新实体
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    abstract protected function updateVirtualEntity(string $id, array $data): array;

    /**
     * 删除实体
     */
    abstract protected function deleteVirtualEntity(string $id): bool;

    /**
     * 将API响应数据转换为虚拟实体对象
     *
     * @param array<string, mixed> $data
     */
    abstract protected function mapDataToEntity(array $data): object;

    /**
     * 将虚拟实体对象转换为API请求数据
     *
     * @return array<string, mixed>
     */
    abstract protected function mapEntityToData(object $entity): array;

    public function index(AdminContext $context): Response
    {
        try {
            // 获取过滤参数
            $filters = $this->extractFiltersFromRequest($context->getRequest());

            // 通过API获取数据
            $apiData = $this->fetchAllEntities($filters);

            // 转换为虚拟实体对象
            $entities = [];
            foreach ($apiData as $item) {
                if (is_array($item)) {
                    /** @var array<string, mixed> $item */
                    $entities[] = $this->mapDataToEntity($item);
                }
            }

            // 渲染列表页面，提供所有必需的模板变量
            $actionsConfig = $this->getActionsForTemplate();

            return $this->render('@EasyAdmin/crud/index.html.twig', [
                'entities' => $entities,
                'fields' => $this->configureFields(Crud::PAGE_INDEX),
                'page_title' => $this->getPageTitle('index'),
                'batch_actions' => $actionsConfig['batch_actions'],
                'global_actions' => $actionsConfig['global_actions'],
                'entity_actions' => $actionsConfig['entity_actions'],
                'crud' => null,
                'filters' => [],
                'paginator' => null,
                'search' => null,
                'has_batch_actions' => false,
                'has_global_actions' => [] !== $actionsConfig['global_actions'],
                'has_filters' => false,
                'has_search' => false,
                'sort' => null,
                'content_title' => $this->getPageTitle('index'),
                'ea' => $context,
                'admin_context' => $context,
                'delete_form' => null,
                'current_filters' => [],
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', sprintf('获取数据失败: %s', $e->getMessage()));

            $actionsConfig = $this->getActionsForTemplate();

            return $this->render('@EasyAdmin/crud/index.html.twig', [
                'entities' => [],
                'fields' => $this->configureFields(Crud::PAGE_INDEX),
                'page_title' => $this->getPageTitle('index'),
                'batch_actions' => $actionsConfig['batch_actions'],
                'global_actions' => $actionsConfig['global_actions'],
                'entity_actions' => $actionsConfig['entity_actions'],
                'crud' => null,
                'filters' => [],
                'paginator' => null,
                'search' => null,
                'has_batch_actions' => false,
                'has_global_actions' => [] !== $actionsConfig['global_actions'],
                'has_filters' => false,
                'has_search' => false,
                'sort' => null,
                'content_title' => $this->getPageTitle('index'),
                'ea' => $context,
                'admin_context' => $context,
                'delete_form' => null,
                'current_filters' => [],
            ]);
        }
    }

    public function new(AdminContext $context): Response
    {
        $entityFqcn = static::getEntityFqcn();
        $newEntity = new $entityFqcn();

        $form = $this->createEntityForm($newEntity, 'new');
        $form->handleRequest($context->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $data = $this->mapEntityToData($newEntity);
                $this->createVirtualEntity($data);

                $this->addFlash('success', '创建成功');

                return $this->redirectToRoute($this->getIndexRouteName());
            } catch (\Exception $e) {
                $this->addFlash('danger', sprintf('创建失败: %s', $e->getMessage()));
            }
        }

        return $this->render('@EasyAdmin/crud/new.html.twig', [
            'entity' => $newEntity,
            'new_form' => $form->createView(),
            'fields' => $this->configureFields(Crud::PAGE_NEW),
            'page_title' => $this->getPageTitle('new'),
        ]);
    }

    public function edit(AdminContext $context): Response
    {
        $entityId = $context->getRequest()->query->get('entityId');

        if (null === $entityId || '' === $entityId || !is_string($entityId)) {
            throw $this->createNotFoundException('实体ID不能为空');
        }

        try {
            $apiData = $this->fetchEntityById($entityId);

            if (null === $apiData || [] === $apiData) {
                throw $this->createNotFoundException('未找到指定的实体');
            }

            $entity = $this->mapDataToEntity($apiData);

            $form = $this->createEntityForm($entity, 'edit');
            $form->handleRequest($context->getRequest());

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $data = $this->mapEntityToData($entity);
                    $this->updateVirtualEntity($entityId, $data);

                    $this->addFlash('success', '更新成功');

                    return $this->redirectToRoute($this->getIndexRouteName());
                } catch (\Exception $e) {
                    $this->addFlash('danger', sprintf('更新失败: %s', $e->getMessage()));
                }
            }

            return $this->render('@EasyAdmin/crud/edit.html.twig', [
                'entity' => $entity,
                'edit_form' => $form->createView(),
                'fields' => $this->configureFields(Crud::PAGE_EDIT),
                'page_title' => $this->getPageTitle('edit'),
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', sprintf('获取实体数据失败: %s', $e->getMessage()));

            return $this->redirectToRoute($this->getIndexRouteName());
        }
    }

    public function delete(AdminContext $context): Response
    {
        $entityId = $context->getRequest()->query->get('entityId');

        if (null === $entityId || '' === $entityId || !is_string($entityId)) {
            throw $this->createNotFoundException('实体ID不能为空');
        }

        try {
            $success = $this->deleteVirtualEntity($entityId);

            if ($success) {
                $this->addFlash('success', '删除成功');
            } else {
                $this->addFlash('danger', '删除失败');
            }
        } catch (\Exception $e) {
            $this->addFlash('danger', sprintf('删除失败: %s', $e->getMessage()));
        }

        return $this->redirectToRoute($this->getIndexRouteName());
    }

    /**
     * 从请求中提取过滤参数
     *
     * @return array<string, mixed>
     */
    protected function extractFiltersFromRequest(Request $request): array
    {
        /** @var array<string, mixed> $filters */
        $filters = [];

        // 从查询参数中提取过滤条件
        if ($request->query->has('filters')) {
            $queryFilters = $request->query->all('filters');
            // 确保返回类型正确
            foreach ($queryFilters as $key => $value) {
                if (is_string($key)) {
                    $filters[$key] = $value;
                }
            }
        }

        return $filters;
    }

    /**
     * 创建实体表单
     */
    protected function createEntityForm(object $entity, string $action): FormInterface
    {
        $formBuilder = $this->createFormBuilder($entity);

        // 为每个字段添加表单字段
        $fields = $this->configureFields($action);
        foreach ($fields as $field) {
            // 确保field是对象并且有getProperty方法
            if (is_object($field) && method_exists($field, 'getProperty')) {
                $property = $field->getProperty();
                if (is_string($property)) {
                    $fieldType = $this->getFormFieldType($field);
                    $options = $this->getFormFieldOptions($field);

                    $formBuilder->add($property, $fieldType, $options);
                }
            }
        }

        return $formBuilder->getForm();
    }

    /**
     * 获取字段的表单类型
     */
    protected function getFormFieldType(object $field): string
    {
        // 根据字段类型返回对应的表单类型
        if (method_exists($field, 'getFieldFqcn')) {
            $fieldTypeName = $field->getFieldFqcn();

            return match ($fieldTypeName) {
                'EasyCorp\Bundle\EasyAdminBundle\Field\TextField' => TextType::class,
                'EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField' => TextareaType::class,
                'EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField' => IntegerType::class,
                'EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField' => ChoiceType::class,
                default => TextType::class,
            };
        }

        return TextType::class;
    }

    /**
     * 获取字段的表单选项
     *
     * @return array<string, mixed>
     */
    protected function getFormFieldOptions(object $field): array
    {
        $options = [
            'required' => false,
        ];

        // 设置标签
        if (method_exists($field, 'getLabel')) {
            $options['label'] = $field->getLabel();
        }

        // 如果是ChoiceField，添加choices选项
        if (method_exists($field, 'getFieldFqcn') && method_exists($field, 'getCustomOptions')) {
            $fieldFqcn = $field->getFieldFqcn();
            if ('EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField' === $fieldFqcn) {
                $customOptions = $field->getCustomOptions();
                if (is_array($customOptions) && isset($customOptions['choices'])) {
                    $options['choices'] = $customOptions['choices'];
                }
            }
        }

        return $options;
    }

    /**
     * 获取页面标题
     */
    protected function getPageTitle(string $action): string
    {
        // 获取实体类名，用于生成默认标题
        $entityClass = static::getEntityFqcn();
        $entityName = basename(str_replace('\\', '/', $entityClass));

        return match ($action) {
            'index' => $entityName . ' 列表',
            'new' => '新建 ' . $entityName,
            'edit' => '编辑 ' . $entityName,
            'detail' => $entityName . ' 详情',
            default => ucfirst($action) . ' ' . $entityName,
        };
    }

    /**
     * 获取索引页面路由名称
     */
    protected function getIndexRouteName(): string
    {
        // 从AdminCrud注解获取路由名称，或者使用默认格式
        $reflection = new \ReflectionClass($this);
        $attributes = $reflection->getAttributes(AdminCrud::class);

        if ([] !== $attributes) {
            $adminCrud = $attributes[0]->newInstance();
            if (method_exists($adminCrud, 'getRouteName')) {
                $routeName = $adminCrud->getRouteName();
                if ($routeName && is_string($routeName)) {
                    return $routeName;
                }
            }
        }

        // 默认路由名称格式
        $entityFqcn = static::getEntityFqcn();

        return 'admin_' . strtolower(str_replace('\\', '_', $entityFqcn));
    }

    /**
     * 配置CRUD基本设置（提供给模板的默认配置）
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular($this->getEntityLabel(true))
            ->setEntityLabelInPlural($this->getEntityLabel(false))
            ->setPageTitle('index', $this->getPageTitle('index'))
            ->setPageTitle('new', $this->getPageTitle('new'))
            ->setPageTitle('edit', $this->getPageTitle('edit'))
            ->setPageTitle('detail', $this->getPageTitle('detail'))
        ;
    }

    /**
     * 获取实体标签
     */
    protected function getEntityLabel(bool $singular = true): string
    {
        $entityClass = static::getEntityFqcn();
        $entityName = basename(str_replace('\\', '/', $entityClass));

        return $singular ? $entityName : $entityName . 's';
    }

    /**
     * 配置操作按钮（提供给模板的默认配置）
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::NEW)
            ->add(Crud::PAGE_INDEX, Action::EDIT)
            ->set(Crud::PAGE_INDEX, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }

    /**
     * 获取用于模板的Actions配置
     * 由于虚拟实体的特殊性，暂时禁用标准操作按钮
     * 子类可以根据需要覆盖此方法来自定义操作
     *
     * @return array<string, array<int, mixed>>
     */
    protected function getActionsForTemplate(): array
    {
        return [
            'global_actions' => [],
            'batch_actions' => [],
            'entity_actions' => [],
        ];
    }
}
