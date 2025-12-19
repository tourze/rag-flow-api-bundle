<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Controller\Admin\DatasetDocument;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractInvokableControllerTestCase;
use Tourze\RAGFlowApiBundle\Controller\Admin\DatasetDocument\GetParseStatusController;

/**
 * @internal
 */
#[CoversClass(GetParseStatusController::class)]
#[RunTestsInSeparateProcesses]
class GetParseStatusControllerTest extends AbstractInvokableControllerTestCase
{
    protected function getControllerFqcn(): string
    {
        return GetParseStatusController::class;
    }
}
