<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;
use Tourze\RAGFlowApiBundle\Service\AdminMenu;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    private AdminMenu $adminMenu;

    protected function onSetUp(): void
    {
        $this->adminMenu = static::getService(AdminMenu::class);
    }

    public function testInvoke(): void
    {
        // 简单测试服务实例化成功
        $this->assertInstanceOf(AdminMenu::class, $this->adminMenu);
        // 创建简单的模拟菜单项
        $mainItem = $this->createMock(ItemInterface::class);
        $ragFlowMenu = $this->createMock(ItemInterface::class);
        // 测试当RAGFlow管理菜单不存在时的行为
        $mainItem->expects($this->exactly(2))->method('getChild')->with('RAGFlow管理')->willReturnOnConsecutiveCalls(null, $ragFlowMenu);
        $mainItem->expects($this->once())->method('addChild')->with('RAGFlow管理')->willReturn($ragFlowMenu);
        $ragFlowMenu->expects($this->once())->method('setAttribute')->with('icon', 'fas fa-brain')->willReturn($ragFlowMenu);
        // 允许任意数量的菜单项添加
        $ragFlowMenu->expects($this->once())->method('addChild')->willReturn($this->createMock(ItemInterface::class));
        // 执行测试
        ($this->adminMenu)($mainItem);
    }

    public function testInvokeWithExistingRagFlowMenu(): void
    {
        // 测试当RAGFlow管理菜单已存在时的行为
        $mainItem = $this->createMock(ItemInterface::class);
        $ragFlowMenu = $this->createMock(ItemInterface::class);
        $mainItem->expects($this->exactly(2))->method('getChild')->with('RAGFlow管理')->willReturn($ragFlowMenu);
        $mainItem->expects($this->never())->method('addChild');
        // 允许任意数量的菜单项添加
        $ragFlowMenu->expects($this->once())->method('addChild')->willReturn($this->createMock(ItemInterface::class));
        ($this->adminMenu)($mainItem);
    }
}
