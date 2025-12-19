<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManagerInterface;

#[AsCommand(
    name: 'rag-flow:instance:test',
    description: '测试 RAGFlow 实例连接'
)]
final class TestInstanceCommand extends Command
{
    public function __construct(
        private readonly RAGFlowInstanceManagerInterface $instanceManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('测试 RAGFlow 实例连接')
            ->addArgument('instance', InputArgument::REQUIRED, '实例名称')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $instanceName = $input->getArgument('instance');
        $io = new SymfonyStyle($input, $output);

        try {
            $instanceNameStr = is_string($instanceName) ? $instanceName : '';
            $io->section(sprintf('测试实例 [%s] 连接', $instanceNameStr));

            $isHealthy = $this->instanceManager->checkHealth($instanceNameStr);

            if ($isHealthy) {
                $io->success('连接测试成功');
                $io->info('实例状态正常，可以正常使用');
            } else {
                $io->error('连接测试失败');
                $io->warning('请检查实例配置和网络连接');

                return Command::FAILURE;
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('测试失败: %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }
}
