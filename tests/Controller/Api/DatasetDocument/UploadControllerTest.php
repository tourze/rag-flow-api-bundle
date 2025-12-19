<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Controller\Api\DatasetDocument;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractInvokableControllerTestCase;
use Tourze\RAGFlowApiBundle\Controller\Api\DatasetDocument\UploadController;

/**
 * @internal
 */
#[CoversClass(UploadController::class)]
#[RunTestsInSeparateProcesses]
class UploadControllerTest extends AbstractInvokableControllerTestCase
{
    protected function getControllerFqcn(): string
    {
        return UploadController::class;
    }
}
