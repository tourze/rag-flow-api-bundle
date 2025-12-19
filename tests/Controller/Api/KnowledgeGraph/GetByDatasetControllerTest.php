<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Controller\Api\KnowledgeGraph;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractInvokableControllerTestCase;
use Tourze\RAGFlowApiBundle\Controller\Api\KnowledgeGraph\GetByDatasetController;

/**
 * @internal
 */
#[CoversClass(GetByDatasetController::class)]
#[RunTestsInSeparateProcesses]
class GetByDatasetControllerTest extends AbstractInvokableControllerTestCase
{
    protected function getControllerFqcn(): string
    {
        return GetByDatasetController::class;
    }
}
