<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManagerInterface;

#[AsCommand(
    name: 'rag-flow:instance:add',
    description: '添加 RAGFlow 实例'
)]
final class AddInstanceCommand extends Command
{
    public function __construct(
        private readonly RAGFlowInstanceManagerInterface $instanceManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('添加 RAGFlow 实例')
            ->addArgument('name', InputArgument::REQUIRED, '实例名称')
            ->addArgument('api_url', InputArgument::REQUIRED, 'API 地址')
            ->addArgument('api_key', InputArgument::REQUIRED, 'API Key')
            ->addOption('description', 'd', InputOption::VALUE_OPTIONAL, '描述', '')
            ->addOption('timeout', 't', InputOption::VALUE_OPTIONAL, '超时时间', 30)
            ->addOption('enabled', 'e', InputOption::VALUE_OPTIONAL, '是否启用', true)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $timeoutOption = $input->getOption('timeout');
        $enabledOption = $input->getOption('enabled');

        $config = [
            'name' => $input->getArgument('name'),
            'api_url' => $input->getArgument('api_url'),
            'api_key' => $input->getArgument('api_key'),
            'description' => $input->getOption('description'),
            'timeout' => is_numeric($timeoutOption) ? (int) $timeoutOption : 30,
            'enabled' => filter_var($enabledOption, FILTER_VALIDATE_BOOLEAN),
        ];

        try {
            $instance = $this->instanceManager->createInstance($config);

            $io = new SymfonyStyle($input, $output);
            $io->success(sprintf('实例 [%s] 创建成功', $instance->getName()));

            // 测试连接
            if ($this->instanceManager->checkHealth($instance->getName())) {
                $io->success('连接测试成功');
            } else {
                $io->error('连接测试失败');

                return Command::FAILURE;
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io = new SymfonyStyle($input, $output);
            $io->error(sprintf('创建失败: %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }
}
