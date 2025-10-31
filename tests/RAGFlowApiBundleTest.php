<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\RAGFlowApiBundle\RAGFlowApiBundle;

/**
 * @internal
 */
#[CoversClass(RAGFlowApiBundle::class)]
#[RunTestsInSeparateProcesses]
class RAGFlowApiBundleTest extends AbstractBundleTestCase
{
}
