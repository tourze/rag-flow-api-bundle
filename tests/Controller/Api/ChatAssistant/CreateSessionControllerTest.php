<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Controller\Api\ChatAssistant;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractInvokableControllerTestCase;
use Tourze\RAGFlowApiBundle\Controller\Api\ChatAssistant\CreateSessionController;

/**
 * @internal
 */
#[CoversClass(CreateSessionController::class)]
#[RunTestsInSeparateProcesses]
class CreateSessionControllerTest extends AbstractInvokableControllerTestCase
{
    protected function getControllerFqcn(): string
    {
        return CreateSessionController::class;
    }
}
