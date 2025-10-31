<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;

/**
 * @extends AbstractCrudController<RAGFlowInstance>
 */
#[AdminCrud(routePath: '/rag-flow/instance', routeName: 'rag_flow_instance')]
final class RAGFlowInstanceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return RAGFlowInstance::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm()
        ;

        yield TextField::new('name', '实例名称')
            ->setRequired(true)
            ->setHelp('为RAGFlow实例设置一个便于识别的名称')
            ->setColumns(6)
        ;

        yield UrlField::new('apiUrl', 'API URL')
            ->setRequired(true)
            ->setHelp('RAGFlow服务的API地址，例如：https://api.ragflow.io')
            ->setColumns(6)
        ;

        yield TextField::new('apiKey', 'API密钥')
            ->setRequired(true)
            ->setHelp('用于访问RAGFlow API的认证密钥')
            ->setColumns(6)
        ;

        yield TextField::new('chatApiKey', '聊天API密钥')
            ->setRequired(false)
            ->setHelp('用于聊天功能的API密钥（可选）')
            ->setColumns(6)
        ;

        yield TextareaField::new('description', '描述')
            ->setHelp('描述此实例的用途或特点')
            ->setColumns(12)
            ->setNumOfRows(3)
        ;

        yield IntegerField::new('timeout', '超时时间')
            ->setHelp('API请求的超时时间（秒），建议设置为30-120秒')
            ->setColumns(3)
            ->setFormType(IntegerType::class)
            ->setFormTypeOption('attr', ['min' => 1, 'max' => 300])
        ;

        yield BooleanField::new('enabled', '是否启用')
            ->setHelp('启用后此实例才可用于API调用')
            ->setColumns(3)
            ->setFormType(CheckboxType::class)
            ->setFormTypeOption('required', false)
        ;

        yield BooleanField::new('healthy', '健康状态')
            ->setHelp('实例的健康检查状态')
            ->setColumns(3)
            ->hideOnForm()
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('实例创建的时间')
            ->setColumns(3)
            ->hideOnForm()
        ;

        yield DateTimeField::new('lastHealthCheck', '最后健康检查')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('最近一次健康检查的时间')
            ->setColumns(3)
            ->hideOnForm()
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('name')
            ->add('enabled')
            ->add('healthy')
            ->add('createTime')
        ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('RAGFlow实例')
            ->setEntityLabelInPlural('RAGFlow实例')
            ->setPageTitle('index', 'RAGFlow实例管理')
            ->setPageTitle('new', '添加RAGFlow实例')
            ->setPageTitle('edit', '编辑RAGFlow实例')
            ->setPageTitle('detail', 'RAGFlow实例详情')
            ->setDefaultSort(['name' => 'ASC'])
        ;
    }
}
