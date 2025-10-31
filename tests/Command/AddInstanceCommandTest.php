<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\RAGFlowApiBundle\Command\AddInstanceCommand;

/**
 * @internal
 */
#[CoversClass(AddInstanceCommand::class)]
#[RunTestsInSeparateProcesses]
class AddInstanceCommandTest extends AbstractCommandTestCase
{
    protected function getCommandTester(): CommandTester
    {
        return new CommandTester(self::getService(AddInstanceCommand::class));
    }

    protected function onSetUp(): void
    {
        // Use real services instead of mocks - tests will work with actual implementation
    }

    public function testExecuteWithRequiredArguments(): void
    {
        $commandTester = $this->getCommandTester();

        // Since this will try to create a real instance and make HTTP calls,
        // we expect this to fail gracefully with a connection error
        $exitCode = $commandTester->execute([
            'name' => 'test-instance-required-args',
            'api_url' => 'https://invalid-test-url.com/api',
            'api_key' => 'test-key',
        ]);

        // The command should handle connection errors and return 1
        $this->assertEquals(1, $exitCode);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('实例 [test-instance-required-args] 创建成功', $output);
        $this->assertStringContainsString('连接测试失败', $output);
    }

    public function testExecuteWithAllOptions(): void
    {
        $commandTester = $this->getCommandTester();

        $exitCode = $commandTester->execute([
            'name' => 'test-instance-all-options',
            'api_url' => 'https://invalid-test-url.com/api',
            'api_key' => 'test-key',
            '--description' => 'Test Description',
            '--timeout' => '60',
            '--enabled' => false,
        ]);

        // The command should handle connection errors and return 1
        $this->assertEquals(1, $exitCode);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('实例 [test-instance-all-options] 创建成功', $output);
        $this->assertStringContainsString('连接测试失败', $output);
    }

    public function testExecuteWithHealthCheckFailure(): void
    {
        // This test is essentially the same as the previous ones
        // since we're using invalid URLs that will fail health checks
        $commandTester = $this->getCommandTester();

        $exitCode = $commandTester->execute([
            'name' => 'test-instance-health-check',
            'api_url' => 'https://invalid-test-url.com/api',
            'api_key' => 'test-key',
        ]);

        $this->assertEquals(1, $exitCode);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('实例 [test-instance-health-check] 创建成功', $output);
        $this->assertStringContainsString('连接测试失败', $output);
    }

    public function testExecuteWithCreationException(): void
    {
        // Test with invalid data that should cause validation errors
        $commandTester = $this->getCommandTester();

        $exitCode = $commandTester->execute([
            'name' => '', // Empty name should cause validation error
            'api_url' => 'invalid-url',
            'api_key' => '',
        ]);

        // Command should handle validation errors gracefully
        $this->assertEquals(1, $exitCode);
        $output = $commandTester->getDisplay();
        // The exact error message may vary, but it should indicate failure
        $this->assertStringContainsString('失败', $output);
    }

    public function testArgumentName(): void
    {
        $command = self::getService(AddInstanceCommand::class);
        $definition = $command->getDefinition();
        $argument = $definition->getArgument('name');

        $this->assertEquals('name', $argument->getName());
        $this->assertTrue($argument->isRequired());
        $this->assertEquals('实例名称', $argument->getDescription());
    }

    public function testArgumentApiUrl(): void
    {
        $command = self::getService(AddInstanceCommand::class);
        $definition = $command->getDefinition();
        $argument = $definition->getArgument('api_url');

        $this->assertEquals('api_url', $argument->getName());
        $this->assertTrue($argument->isRequired());
        $this->assertEquals('API 地址', $argument->getDescription());
    }

    public function testArgumentApiKey(): void
    {
        $command = self::getService(AddInstanceCommand::class);
        $definition = $command->getDefinition();
        $argument = $definition->getArgument('api_key');

        $this->assertEquals('api_key', $argument->getName());
        $this->assertTrue($argument->isRequired());
        $this->assertEquals('API Key', $argument->getDescription());
    }

    public function testOptionDescription(): void
    {
        $command = self::getService(AddInstanceCommand::class);
        $definition = $command->getDefinition();
        $option = $definition->getOption('description');

        $this->assertEquals('description', $option->getName());
        $this->assertEquals('d', $option->getShortcut());
        $this->assertEquals('描述', $option->getDescription());
        $this->assertEquals('', $option->getDefault());
    }

    public function testOptionTimeout(): void
    {
        $command = self::getService(AddInstanceCommand::class);
        $definition = $command->getDefinition();
        $option = $definition->getOption('timeout');

        $this->assertEquals('timeout', $option->getName());
        $this->assertEquals('t', $option->getShortcut());
        $this->assertEquals('超时时间', $option->getDescription());
        $this->assertEquals(30, $option->getDefault());
    }

    public function testOptionEnabled(): void
    {
        $command = self::getService(AddInstanceCommand::class);
        $definition = $command->getDefinition();
        $option = $definition->getOption('enabled');

        $this->assertEquals('enabled', $option->getName());
        $this->assertEquals('e', $option->getShortcut());
        $this->assertEquals('是否启用', $option->getDescription());
        $this->assertTrue($option->getDefault());
    }

    public function testCommandConfiguration(): void
    {
        $command = self::getService(AddInstanceCommand::class);

        $this->assertEquals('rag-flow:instance:add', $command->getName());
        $this->assertEquals('添加 RAGFlow 实例', $command->getDescription());

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('name'));
        $this->assertTrue($definition->hasArgument('api_url'));
        $this->assertTrue($definition->hasArgument('api_key'));
        $this->assertTrue($definition->hasOption('description'));
        $this->assertTrue($definition->hasOption('timeout'));
        $this->assertTrue($definition->hasOption('enabled'));
    }
}
