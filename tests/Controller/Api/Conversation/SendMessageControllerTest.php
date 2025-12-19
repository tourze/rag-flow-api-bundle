<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Controller\Api\Conversation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractInvokableControllerTestCase;
use Tourze\RAGFlowApiBundle\Controller\Api\Conversation\SendMessageController;

/**
 * @internal
 */
#[CoversClass(SendMessageController::class)]
#[RunTestsInSeparateProcesses]
class SendMessageControllerTest extends AbstractInvokableControllerTestCase
{
    protected function getControllerFqcn(): string
    {
        return SendMessageController::class;
    }
}
