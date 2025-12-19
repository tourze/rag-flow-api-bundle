<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\DTO\ActionResult;

/**
 * 测试动作结果
 * @internal
 */
#[CoversClass(ActionResult::class)]
class ActionResultTest extends TestCase
{
    public function testSuccessResult(): void
    {
        $result = ActionResult::success('操作成功');

        $this->assertInstanceOf(ActionResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame('操作成功', $result->message);
        $this->assertSame('success', $result->type);
    }

    public function testErrorResult(): void
    {
        $result = ActionResult::error('操作失败');

        $this->assertInstanceOf(ActionResult::class, $result);
        $this->assertFalse($result->success);
        $this->assertSame('操作失败', $result->message);
        $this->assertSame('danger', $result->type);
    }

    public function testInfoResult(): void
    {
        $result = ActionResult::info('提示信息');

        $this->assertInstanceOf(ActionResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame('提示信息', $result->message);
        $this->assertSame('info', $result->type);
    }
}
