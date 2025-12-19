<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Controller\Api\KnowledgeGraph;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractInvokableControllerTestCase;
use Tourze\RAGFlowApiBundle\Controller\Api\KnowledgeGraph\SearchEntitiesController;

/**
 * @internal
 */
#[CoversClass(SearchEntitiesController::class)]
#[RunTestsInSeparateProcesses]
class SearchEntitiesControllerTest extends AbstractInvokableControllerTestCase
{
    protected function getControllerFqcn(): string
    {
        return SearchEntitiesController::class;
    }
}
