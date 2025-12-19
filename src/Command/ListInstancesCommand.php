<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManagerInterface;

#[AsCommand(
    name: 'rag-flow:instance:list',
    description: '列出所有 RAGFlow 实例'
)]
final class ListInstancesCommand extends Command
{
    public function __construct(
        private readonly RAGFlowInstanceManagerInterface $instanceManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('RAGFlow 实例列表');

        try {
            $instances = $this->instanceManager->getActiveInstances();

            if ([] === $instances) {
                $io->warning('没有找到任何 RAGFlow 实例');

                return Command::SUCCESS;
            }

            $table = [];
            foreach ($instances as $instance) {
                $healthStatus = '未知';
                try {
                    $healthStatus = $this->instanceManager->checkHealth($instance->getName()) ? '健康' : '异常';
                } catch (\Exception $e) {
                    $healthStatus = '检查失败';
                }

                $table[] = [
                    $instance->getName(),
                    $instance->getApiUrl(),
                    $instance->getDescription() ?? '',
                    $instance->isEnabled() ? '启用' : '禁用',
                    $instance->getTimeout() . 's',
                    $healthStatus,
                    $instance->getLastHealthCheck()?->format('Y-m-d H:i:s') ?? '从未检查',
                ];
            }

            $io->table([
                '名称', 'API 地址', '描述', '状态', '超时', '健康状态', '最后检查时间',
            ], $table);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('获取实例列表失败: %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }
}
