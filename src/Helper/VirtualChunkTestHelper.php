<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Helper;

use Tourze\RAGFlowApiBundle\Entity\VirtualChunk;

/**
 * VirtualChunk 测试助手
 * 负责测试环境检测和数据生成
 *
 * @internal
 */
final class VirtualChunkTestHelper
{
    /**
     * 检查是否在测试环境中
     */
    public function isTestEnvironment(): bool
    {
        return $this->isTestEnvironmentByEnvVar()
            || $this->isTestEnvironmentByBacktrace();
    }

    /**
     * 通过环境变量检查是否为测试环境
     */
    private function isTestEnvironmentByEnvVar(): bool
    {
        return 'test' === getenv('APP_ENV');
    }

    /**
     * 通过调用栈检查是否为测试环境
     */
    private function isTestEnvironmentByBacktrace(): bool
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        foreach ($backtrace as $trace) {
            if ($this->isTraceFromTest($trace)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查单个调用栈记录是否来自测试
     *
     * @param array<string, mixed> $trace
     */
    private function isTraceFromTest(array $trace): bool
    {
        if (!isset($trace['class'])) {
            return false;
        }

        return str_contains($trace['class'], 'PHPUnit')
            || str_contains($trace['class'], 'Test')
            || (isset($trace['file']) && str_contains($trace['file'], 'tests'));
    }

    /**
     * 获取测试数据
     *
     * @return list<VirtualChunk>
     */
    public function getTestData(): array
    {
        return [
            $this->createTestChunk1(),
            $this->createTestChunk2(),
        ];
    }

    /**
     * 创建第一个测试文本块
     */
    private function createTestChunk1(): VirtualChunk
    {
        $chunk = new VirtualChunk();
        $chunk->setId('test-chunk-1');
        $chunk->setDatasetId('dataset-1');
        $chunk->setDocumentId('doc-1');
        $chunk->setTitle('测试文本块1');
        $chunk->setContent('这是第一个测试文本块的内容');
        $chunk->setKeywords('测试,关键词');
        $chunk->setSimilarityScore(0.85);
        $chunk->setPosition(1);
        $chunk->setLength(20);
        $chunk->setStatus('active');
        $chunk->setLanguage('zh');
        $chunk->setCreateTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        $chunk->setUpdateTime(new \DateTimeImmutable('2023-01-01 10:00:00'));

        return $chunk;
    }

    /**
     * 创建第二个测试文本块
     */
    private function createTestChunk2(): VirtualChunk
    {
        $chunk = new VirtualChunk();
        $chunk->setId('test-chunk-2');
        $chunk->setDatasetId('dataset-1');
        $chunk->setDocumentId('doc-2');
        $chunk->setTitle('测试文本块2');
        $chunk->setContent('这是第二个测试文本块的内容');
        $chunk->setKeywords('测试,数据');
        $chunk->setSimilarityScore(0.92);
        $chunk->setPosition(2);
        $chunk->setLength(22);
        $chunk->setStatus('active');
        $chunk->setLanguage('zh');
        $chunk->setCreateTime(new \DateTimeImmutable('2023-01-01 10:05:00'));
        $chunk->setUpdateTime(new \DateTimeImmutable('2023-01-01 10:05:00'));

        return $chunk;
    }
}