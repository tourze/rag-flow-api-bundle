<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Controller\Api\Dataset;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractInvokableControllerTestCase;
use Tourze\RAGFlowApiBundle\Controller\Api\Dataset\GetKnowledgeGraphController;

/**
 * @internal
 */
#[CoversClass(GetKnowledgeGraphController::class)]
#[RunTestsInSeparateProcesses]
class GetKnowledgeGraphControllerTest extends AbstractInvokableControllerTestCase
{
    protected function getControllerFqcn(): string
    {
        return GetKnowledgeGraphController::class;
    }
}
