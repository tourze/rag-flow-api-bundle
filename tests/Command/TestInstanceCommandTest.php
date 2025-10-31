<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\RAGFlowApiBundle\Command\TestInstanceCommand;

/**
 * @internal
 */
#[CoversClass(TestInstanceCommand::class)]
#[RunTestsInSeparateProcesses]
class TestInstanceCommandTest extends AbstractCommandTestCase
{
    protected function getCommandTester(): CommandTester
    {
        return new CommandTester(self::getService(TestInstanceCommand::class));
    }

    protected function onSetUp(): void
    {
        // Use real services instead of mocks - tests will work with actual implementation
    }

    public function testExecuteWithInvalidInstance(): void
    {
        // Test with an invalid instance that will fail health check
        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute(['instance' => 'invalid-instance']);

        // Should handle connection error gracefully and return failure
        $this->assertEquals(1, $exitCode);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('测试实例 [invalid-instance] 连接', $output);
        $this->assertStringContainsString('失败', $output);
    }

    public function testExecuteWithDifferentInstanceName(): void
    {
        // Test with another invalid instance name
        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute(['instance' => 'another-invalid-instance']);

        // Should handle connection error gracefully and return failure
        $this->assertEquals(1, $exitCode);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('测试实例 [another-invalid-instance] 连接', $output);
        $this->assertStringContainsString('失败', $output);
    }

    public function testArgumentInstance(): void
    {
        $command = self::getService(TestInstanceCommand::class);
        $definition = $command->getDefinition();
        $argument = $definition->getArgument('instance');

        $this->assertEquals('instance', $argument->getName());
        $this->assertTrue($argument->isRequired());
        $this->assertEquals('实例名称', $argument->getDescription());
    }

    public function testCommandExecutionFormat(): void
    {
        // Test that command executes and produces expected output format
        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute(['instance' => 'test-instance']);

        // Should handle the test gracefully
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('测试实例 [test-instance] 连接', $output);
    }

    public function testCommandConfiguration(): void
    {
        $command = self::getService(TestInstanceCommand::class);

        $this->assertEquals('rag-flow:instance:test', $command->getName());
        $this->assertEquals('测试 RAGFlow 实例连接', $command->getDescription());

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('instance'));
        $this->assertTrue($definition->getArgument('instance')->isRequired());
        $this->assertEquals('实例名称', $definition->getArgument('instance')->getDescription());
    }
}
