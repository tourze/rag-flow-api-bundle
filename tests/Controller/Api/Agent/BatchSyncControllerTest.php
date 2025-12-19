<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Controller\Api\Agent;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractInvokableControllerTestCase;
use Tourze\RAGFlowApiBundle\Controller\Api\Agent\BatchSyncController;

/**
 * @internal
 */
#[CoversClass(BatchSyncController::class)]
#[RunTestsInSeparateProcesses]
class BatchSyncControllerTest extends AbstractInvokableControllerTestCase
{
    protected function getControllerFqcn(): string
    {
        return BatchSyncController::class;
    }
}
