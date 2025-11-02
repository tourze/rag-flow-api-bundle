<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Service\ActionResult;

/**
 * 测试动作结果
 * @internal
 */
#[CoversClass(ActionResult::class)]
class ActionResultTest extends TestCase
{
    public function testActionResult(): void
    {
        $result = new ActionResult();
        $this->assertInstanceOf(ActionResult::class, $result);
    }
}
