<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Controller\Api\Chat;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractInvokableControllerTestCase;
use Tourze\RAGFlowApiBundle\Controller\Api\Chat\OpenAIChatCompletionController;

/**
 * @internal
 */
#[CoversClass(OpenAIChatCompletionController::class)]
#[RunTestsInSeparateProcesses]
class OpenAIChatCompletionControllerTest extends AbstractInvokableControllerTestCase
{
    protected function getControllerFqcn(): string
    {
        return OpenAIChatCompletionController::class;
    }
}
