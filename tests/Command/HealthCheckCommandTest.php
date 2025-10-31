<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\RAGFlowApiBundle\Command\HealthCheckCommand;

/**
 * @internal
 */
#[CoversClass(HealthCheckCommand::class)]
#[RunTestsInSeparateProcesses]
class HealthCheckCommandTest extends AbstractCommandTestCase
{
    protected function getCommandTester(): CommandTester
    {
        return new CommandTester(self::getService(HealthCheckCommand::class));
    }

    protected function onSetUp(): void
    {
        // Use real services instead of mocks - tests will work with actual implementation
    }

    public function testExecuteWithExistingInstances(): void
    {
        // Since we're using real services with test fixtures,
        // there should be at least one test instance configured
        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([]);

        $this->assertEquals(0, $exitCode);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('RAGFlow 系统健康检查', $output);
        $this->assertStringContainsString('健康检查概览', $output);
        // Should show at least one instance (the actual instance name from test data)
        $this->assertStringContainsString('user1', $output);
    }

    public function testExecuteWithSpecificInvalidInstance(): void
    {
        // Test with a specific instance name that will fail health check
        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute(['--instance' => 'invalid-instance']);

        // Should complete successfully even if health check fails
        $this->assertEquals(1, $exitCode);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('invalid-instance', $output);
    }

    public function testExecuteWithJsonOutput(): void
    {
        // Test JSON output format with existing instances
        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute(['--output' => 'json']);

        $this->assertEquals(0, $exitCode);
        $output = $commandTester->getDisplay();
        // Should contain valid JSON output
        $this->assertNotEmpty($output);
        // Just check that it contains some expected JSON structure
        $this->assertStringContainsString('{', $output);
        $this->assertStringContainsString('status', $output);
    }

    public function testOptionInstance(): void
    {
        $command = self::getService(HealthCheckCommand::class);
        $definition = $command->getDefinition();
        $option = $definition->getOption('instance');

        $this->assertEquals('instance', $option->getName());
        $this->assertEquals('i', $option->getShortcut());
        $this->assertEquals('检查指定实例，不指定则检查所有实例', $option->getDescription());
        $this->assertNull($option->getDefault());
    }

    public function testOptionOutput(): void
    {
        $command = self::getService(HealthCheckCommand::class);
        $definition = $command->getDefinition();
        $option = $definition->getOption('output');

        $this->assertEquals('output', $option->getName());
        $this->assertEquals('o', $option->getShortcut());
        $this->assertEquals('输出格式', $option->getDescription());
        $this->assertEquals('table', $option->getDefault());
    }

    public function testCommandConfiguration(): void
    {
        $command = self::getService(HealthCheckCommand::class);

        $this->assertEquals('rag-flow:health:check', $command->getName());
        $this->assertEquals('检查 RAGFlow 系统健康状态', $command->getDescription());

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasOption('instance'));
        $this->assertTrue($definition->hasOption('output'));
    }
}
