<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\RAGFlowApiBundle\Command\ListDatasetsCommand;

/**
 * @internal
 */
#[CoversClass(ListDatasetsCommand::class)]
#[RunTestsInSeparateProcesses]
class ListDatasetsCommandTest extends AbstractCommandTestCase
{
    protected function getCommandTester(): CommandTester
    {
        return new CommandTester(self::getService(ListDatasetsCommand::class));
    }

    protected function onSetUp(): void
    {
        // Use real services instead of mocks - tests will work with actual implementation
    }

    public function testExecuteWithDefaultInstance(): void
    {
        // Since we're using real services without configured instances,
        // this will likely fail trying to connect to the default instance
        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([]);

        // Should handle connection error gracefully
        $this->assertEquals(1, $exitCode);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('获取数据集列表失败', $output);
    }

    public function testExecuteWithSpecificInstance(): void
    {
        // Test with a specific invalid instance name
        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute(['instance' => 'invalid-instance']);

        // Should handle connection error gracefully
        $this->assertEquals(1, $exitCode);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('获取数据集列表失败', $output);
    }

    public function testExecuteWithJsonOutput(): void
    {
        // Test JSON output format
        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute(['--output' => 'json']);

        // Should handle connection error gracefully
        $this->assertEquals(1, $exitCode);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('获取数据集列表失败', $output);
    }

    public function testArgumentInstance(): void
    {
        $command = self::getService(ListDatasetsCommand::class);
        $definition = $command->getDefinition();
        $argument = $definition->getArgument('instance');

        $this->assertEquals('instance', $argument->getName());
        $this->assertFalse($argument->isRequired());
        $this->assertEquals('实例名称', $argument->getDescription());
        $this->assertEquals('default', $argument->getDefault());
    }

    public function testOptionOutput(): void
    {
        $command = self::getService(ListDatasetsCommand::class);
        $definition = $command->getDefinition();
        $option = $definition->getOption('output');

        $this->assertEquals('output', $option->getName());
        $this->assertEquals('o', $option->getShortcut());
        $this->assertEquals('输出格式', $option->getDescription());
        $this->assertEquals('table', $option->getDefault());
    }

    public function testCommandConfiguration(): void
    {
        $command = self::getService(ListDatasetsCommand::class);

        $this->assertEquals('rag-flow:dataset:list', $command->getName());
        $this->assertEquals('列出 RAGFlow 数据集', $command->getDescription());

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('instance'));
        $this->assertTrue($definition->hasOption('output'));
        $this->assertEquals('default', $definition->getArgument('instance')->getDefault());
        $this->assertEquals('table', $definition->getOption('output')->getDefault());
    }
}
