<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Controller\Admin\DatasetDocument;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractInvokableControllerTestCase;
use Tourze\RAGFlowApiBundle\Controller\Admin\DatasetDocument\SyncAllChunksController;

/**
 * @internal
 */
#[CoversClass(SyncAllChunksController::class)]
#[RunTestsInSeparateProcesses]
class SyncAllChunksControllerTest extends AbstractInvokableControllerTestCase
{
    protected function getControllerFqcn(): string
    {
        return SyncAllChunksController::class;
    }
}
