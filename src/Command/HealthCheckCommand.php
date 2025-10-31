<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManagerInterface;

#[AsCommand(
    name: 'rag-flow:health:check',
    description: '检查 RAGFlow 系统健康状态'
)]
class HealthCheckCommand extends Command
{
    public function __construct(
        private readonly RAGFlowInstanceManagerInterface $instanceManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('检查 RAGFlow 系统健康状态')
            ->addOption('instance', 'i', InputOption::VALUE_OPTIONAL, '检查指定实例，不指定则检查所有实例')
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, '输出格式', 'table')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $instanceName = $input->getOption('instance');
        $outputFormat = $input->getOption('output');
        $io = new SymfonyStyle($input, $output);

        try {
            $io->title('RAGFlow 系统健康检查');

            if (is_string($instanceName) && '' !== $instanceName) {
                // 检查指定实例
                $result = $this->checkSingleInstance($instanceName);
                $results = [$result];
            } else {
                // 检查所有实例
                $instances = $this->instanceManager->getActiveInstances();
                $results = [];
                foreach ($instances as $instance) {
                    $results[] = $this->checkSingleInstance($instance->getName());
                }
            }

            if ([] === $results) {
                $io->warning('没有找到任何 RAGFlow 实例');

                return Command::SUCCESS;
            }

            $format = is_string($outputFormat) ? $outputFormat : 'table';

            return $this->outputResults($io, $results, $format);
        } catch (\Exception $e) {
            $io->error(sprintf('健康检查失败: %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }

    /** @return array<string, mixed> */
    private function checkSingleInstance(string $instanceName): array
    {
        $result = [
            'name' => $instanceName,
            'status' => 'unknown',
            'message' => '',
            'response_time' => null,
            'checked_at' => new \DateTime(),
        ];

        try {
            $startTime = microtime(true);
            $isHealthy = $this->instanceManager->checkHealth($instanceName);
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            $result['response_time'] = $responseTime;

            if ($isHealthy) {
                $result['status'] = 'healthy';
                $result['message'] = '连接正常';
            } else {
                $result['status'] = 'unhealthy';
                $result['message'] = '连接失败';
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    /** @param array<string, mixed>[] $results */
    private function outputResults(SymfonyStyle $io, array $results, string $outputFormat): int
    {
        if ('json' === $outputFormat) {
            $jsonOutput = json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $io->writeln(false !== $jsonOutput ? $jsonOutput : '[]');

            return Command::SUCCESS;
        }

        // 统计结果
        $total = count($results);
        $healthy = count(array_filter($results, fn ($r) => 'healthy' === $r['status']));
        $unhealthy = count(array_filter($results, fn ($r) => 'unhealthy' === $r['status']));
        $errors = count(array_filter($results, fn ($r) => 'error' === $r['status']));

        // 输出概览
        $io->section('健康检查概览');
        $io->writeln(sprintf('总实例数: <info>%d</info>', $total));
        $io->writeln(sprintf('健康实例: <info>%d</info>', $healthy));
        $io->writeln(sprintf('异常实例: <error>%d</error>', $unhealthy));
        $io->writeln(sprintf('错误实例: <error>%d</error>', $errors));

        // 输出详细结果
        $io->section('详细结果');
        $table = [];
        foreach ($results as $result) {
            $statusColor = match ($result['status']) {
                'healthy' => 'green',
                'unhealthy' => 'red',
                'error' => 'red',
                default => 'gray',
            };

            $statusText = match ($result['status']) {
                'healthy' => '健康',
                'unhealthy' => '异常',
                'error' => '错误',
                default => '未知',
            };

            $responseTime = is_numeric($result['response_time']) ? $result['response_time'] : null;
            $checkedAt = $result['checked_at'] instanceof \DateTime ? $result['checked_at'] : new \DateTime();

            $table[] = [
                $result['name'],
                sprintf('<fg=%s>%s</>', $statusColor, $statusText),
                $result['message'],
                null !== $responseTime ? sprintf('%s ms', $responseTime) : 'N/A',
                $checkedAt->format('Y-m-d H:i:s'),
            ];
        }

        $io->table([
            '实例名称', '状态', '消息', '响应时间', '检查时间',
        ], $table);

        // 根据健康状态决定退出码
        if ($errors > 0) {
            return Command::FAILURE;
        }

        if ($unhealthy > 0) {
            $io->warning('部分实例健康状态异常');

            return Command::SUCCESS;
        }

        $io->success('所有实例健康状态正常');

        return Command::SUCCESS;
    }
}
