<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\RAGFlowApiBundle\Entity\ChatAssistant;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\LlmModel;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;

/**
 * RAGFlow API管理后台菜单提供者
 *
 * 简化版：仅提供RAGFlow实例管理入口
 */
#[Autoconfigure(public: true)]
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private LinkGeneratorInterface $linkGenerator,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        // 创建RAGFlow管理主菜单
        if (null === $item->getChild('RAGFlow管理')) {
            $item->addChild('RAGFlow管理')
                ->setAttribute('icon', 'fas fa-brain')
            ;
        }

        $ragFlowMenu = $item->getChild('RAGFlow管理');
        if (null === $ragFlowMenu) {
            return;
        }

        // RAGFlow实例管理子菜单
        $ragFlowMenu->addChild('RAGFlow实例')
            ->setUri($this->linkGenerator->getCurdListPage(RAGFlowInstance::class))
            ->setAttribute('icon', 'fas fa-server')
        ;

        // 数据集管理
        $ragFlowMenu->addChild('数据集管理')
            ->setUri($this->linkGenerator->getCurdListPage(Dataset::class))
            ->setAttribute('icon', 'fas fa-database')
        ;

        // LLM模型管理
        $ragFlowMenu->addChild('LLM模型管理')
            ->setUri($this->linkGenerator->getCurdListPage(LlmModel::class))
            ->setAttribute('icon', 'fas fa-brain')
        ;

        // 聊天助手管理
        $ragFlowMenu->addChild('聊天助手管理')
            ->setUri($this->linkGenerator->getCurdListPage(ChatAssistant::class))
            ->setAttribute('icon', 'fas fa-robot')
        ;
    }
}
