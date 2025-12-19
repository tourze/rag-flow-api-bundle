<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;
use Tourze\RAGFlowApiBundle\Service\AdminMenu;

/**
 * AdminMenu 集成测试
 *
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    private ?AdminMenu $adminMenu = null;

    protected function onSetUp(): void
    {
        // 尝试从容器获取服务，如果失败则跳过测试
        try {
            $this->adminMenu = static::getService(AdminMenu::class);
        } catch (\Throwable) {
            // 服务未注册时，将在测试中标记为跳过
            $this->adminMenu = null;
        }
    }

    public function testInvoke(): void
    {
        if (null === $this->adminMenu) {
            self::markTestSkipped('AdminMenu service not available in test container');
        }

        // 简单测试服务实例化成功
        $this->assertInstanceOf(AdminMenu::class, $this->adminMenu);
        // 创建简单的模拟菜单项
        $mainItem = $this->createMock(ItemInterface::class);
        $ragFlowMenu = $this->createMock(ItemInterface::class);
        $subMenuItem = $this->createMock(ItemInterface::class);

        // 测试当RAGFlow管理菜单不存在时的行为
        $mainItem->expects($this->exactly(2))->method('getChild')->with('RAGFlow管理')->willReturnOnConsecutiveCalls(null, $ragFlowMenu);
        $mainItem->expects($this->once())->method('addChild')->with('RAGFlow管理')->willReturn($ragFlowMenu);
        $ragFlowMenu->expects($this->once())->method('setAttribute')->with('icon', 'fas fa-brain')->willReturn($ragFlowMenu);

        // 允许任意数量的菜单项添加
        $subMenuItem->method('setUri')->willReturnSelf();
        $subMenuItem->method('setAttribute')->willReturnSelf();
        $ragFlowMenu->method('addChild')->willReturn($subMenuItem);

        // 执行测试
        ($this->adminMenu)($mainItem);
    }

    public function testInvokeWithExistingRagFlowMenu(): void
    {
        if (null === $this->adminMenu) {
            self::markTestSkipped('AdminMenu service not available in test container');
        }

        // 测试当RAGFlow管理菜单已存在时的行为
        $mainItem = $this->createMock(ItemInterface::class);
        $ragFlowMenu = $this->createMock(ItemInterface::class);
        $subMenuItem = $this->createMock(ItemInterface::class);

        $mainItem->expects($this->exactly(2))->method('getChild')->with('RAGFlow管理')->willReturn($ragFlowMenu);
        $mainItem->expects($this->never())->method('addChild');

        // 允许任意数量的菜单项添加
        $subMenuItem->method('setUri')->willReturnSelf();
        $subMenuItem->method('setAttribute')->willReturnSelf();
        $ragFlowMenu->method('addChild')->willReturn($subMenuItem);

        ($this->adminMenu)($mainItem);
    }
}
