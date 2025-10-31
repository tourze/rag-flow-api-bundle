<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Service\ActionResult;

/**
 * 测试动作结果
 */
class ActionResultTest extends TestCase
{
    public function testActionResult(): void
    {
        $result = new ActionResult();
        $this->assertInstanceOf(ActionResult::class, $result);
    }
}