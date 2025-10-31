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
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Request\ListDatasetsRequest;
use Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManagerInterface;

#[AsCommand(
    name: 'rag-flow:dataset:list',
    description: '列出 RAGFlow 数据集'
)]
class ListDatasetsCommand extends Command
{
    public function __construct(
        private readonly RAGFlowInstanceManagerInterface $instanceManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('列出 RAGFlow 数据集')
            ->addArgument('instance', InputArgument::OPTIONAL, '实例名称', 'default')
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, '输出格式', 'table')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $instanceName = $input->getArgument('instance');
        $outputFormat = $input->getOption('output');
        $io = new SymfonyStyle($input, $output);

        try {
            $instanceNameStr = is_string($instanceName) ? $instanceName : '';
            $client = $this->instanceManager->getClient($instanceNameStr);
            $datasetResponse = $client->datasets()->list();

            // DatasetService::list() 返回 Dataset[] 数组
            /** @var Dataset[] $datasets */
            $datasets = $datasetResponse;

            $format = is_string($outputFormat) ? $outputFormat : 'table';
            if ('json' === $format) {
                $this->outputJson($io, $datasets);

                return Command::SUCCESS;
            }

            $this->outputTable($io, $instanceNameStr, $datasets);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('获取数据集列表失败: %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }

    /** @param Dataset[] $datasets */
    private function outputJson(SymfonyStyle $io, array $datasets): void
    {
        $datasetData = [];
        foreach ($datasets as $dataset) {
            $datasetData[] = [
                'id' => $dataset->getRemoteId(),
                'name' => $dataset->getName(),
                'description' => $dataset->getDescription(),
                'language' => $dataset->getLanguage(),
                'chunk_method' => $dataset->getChunkMethod(),
                'status' => $dataset->getStatus(),
                'created_time' => $dataset->getRemoteCreateTime()?->format('Y-m-d H:i:s'),
            ];
        }
        $jsonOutput = json_encode($datasetData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $io->writeln(false !== $jsonOutput ? $jsonOutput : '[]');
    }

    /** @param Dataset[] $datasets */
    private function outputTable(SymfonyStyle $io, string $instanceName, array $datasets): void
    {
        $io->title(sprintf('RAGFlow 实例 [%s] 数据集列表', $instanceName));

        if ([] === $datasets) {
            $io->warning('没有找到任何数据集');

            return;
        }

        $table = [];
        foreach ($datasets as $dataset) {
            $table[] = [
                $dataset->getRemoteId() ?? '',
                $dataset->getName(),
                $dataset->getDescription() ?? '',
                $dataset->getLanguage() ?? '',
                $dataset->getChunkMethod() ?? '',
                $dataset->getStatus() ?? '',
                $dataset->getRemoteCreateTime()?->format('Y-m-d H:i:s') ?? '',
            ];
        }

        $io->table([
            'ID', '名称', '描述', '语言', '分块方法', '状态', '创建时间',
        ], $table);
    }
}
