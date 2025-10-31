<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Helper;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Helper\AgentStatsProcessor;

/**
 * AgentStatsProcessor 测试
 *
 * 测试智能体统计数据处理辅助类的各种业务场景
 * 验证数据处理的正确性、类型安全性和异常处理
 *
 * @internal
 */
#[CoversClass(AgentStatsProcessor::class)]
final class AgentStatsProcessorTest extends TestCase
{
    private AgentStatsProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new AgentStatsProcessor();
    }

    /**
     * 提供测试用的有效统计数据
     *
     * @return array<string, array{mixed, array<string, int>}>
     */
    public static function provideValidStatsData(): array
    {
        return [
            'basic_stats' => [
                [
                    ['status' => 'active', 'count' => 10],
                    ['status' => 'inactive', 'count' => 5],
                    ['status' => 'pending', 'count' => 3],
                ],
                ['active' => 10, 'inactive' => 5, 'pending' => 3],
            ],
            'string_counts' => [
                [
                    ['status' => 'online', 'count' => '15'],
                    ['status' => 'offline', 'count' => '8'],
                    ['status' => 'busy', 'count' => '2'],
                ],
                ['online' => 15, 'offline' => 8, 'busy' => 2],
            ],
            'float_counts_rounded' => [
                [
                    ['status' => 'ready', 'count' => 12.7],
                    ['status' => 'processing', 'count' => 3.2],
                ],
                ['ready' => 12, 'processing' => 3],
            ],
            'single_stat' => [
                [
                    ['status' => 'running', 'count' => 1],
                ],
                ['running' => 1],
            ],
            'mixed_numeric_formats' => [
                [
                    ['status' => 'success', 'count' => '20'],
                    ['status' => 'error', 'count' => 5],
                    ['status' => 'warning', 'count' => 2.5],
                ],
                ['success' => 20, 'error' => 5, 'warning' => 2],
            ],
        ];
    }

    /**
     * 提供测试用的无效统计数据
     *
     * @return array<string, array{mixed}>
     */
    public static function provideInvalidStatsData(): array
    {
        return [
            'null_input' => [null],
            'empty_string' => [''],
            'numeric_value' => [42],
            'boolean_false' => [false],
            'boolean_true' => [true],
            'empty_array' => [[]],
        ];
    }

    /**
     * 提供包含无效项的统计数据
     *
     * @return array<string, array{mixed, array<string, int>}>
     */
    public static function provideMixedValidInvalidStatsData(): array
    {
        return [
            'mixed_with_invalid_entries' => [
                [
                    ['status' => 'valid', 'count' => 10],
                    'invalid_entry',
                    null,
                    ['status' => 'another_valid', 'count' => 5],
                    123,
                    [],
                    ['status' => 'third_valid', 'count' => 3],
                ],
                ['valid' => 10, 'another_valid' => 5, 'third_valid' => 3],
            ],
            'entries_missing_required_fields' => [
                [
                    ['status' => 'complete', 'count' => 8],
                    ['status' => 'missing_count'],
                    ['count' => 5],
                    ['status' => 'complete_again', 'count' => '12'],
                    ['status' => null, 'count' => 3],
                    [123, 456],
                    ['status' => 'valid_last', 'count' => 1],
                ],
                ['complete' => 8, 'complete_again' => 12, 'valid_last' => 1],
            ],
            'entries_with_wrong_types' => [
                [
                    ['status' => 'correct', 'count' => 7],
                    ['status' => 123, 'count' => 5], // status not string
                    ['status' => 'invalid_count', 'count' => 'not_a_number'], // count not numeric
                    ['status' => 'also_correct', 'count' => '15'],
                ],
                ['correct' => 7, 'also_correct' => 15],
            ],
        ];
    }

    /**
     * 提供边界情况和特殊值的测试数据
     *
     * @return array<string, array{mixed, array<string, int>}>
     */
    public static function provideEdgeCaseStatsData(): array
    {
        return [
            'zero_counts' => [
                [
                    ['status' => 'idle', 'count' => 0],
                    ['status' => 'stopped', 'count' => '0'],
                    ['status' => 'paused', 'count' => 0.0],
                ],
                ['idle' => 0, 'stopped' => 0, 'paused' => 0],
            ],
            'negative_counts' => [
                [
                    ['status' => 'error', 'count' => -5],
                    ['status' => 'warning', 'count' => '-10'],
                    ['status' => 'critical', 'count' => -2.5],
                ],
                ['error' => -5, 'warning' => -10, 'critical' => -2],
            ],
            'large_numbers' => [
                [
                    ['status' => 'success', 'count' => 999999],
                    ['status' => 'failed', 'count' => '1000000'],
                ],
                ['success' => 999999, 'failed' => 1000000],
            ],
            'empty_status_strings' => [
                [
                    ['status' => '', 'count' => 5],
                    ['status' => 'non_empty', 'count' => 3],
                ],
                ['' => 5, 'non_empty' => 3],
            ],
            'special_characters_in_status' => [
                [
                    ['status' => '状态-中文', 'count' => 2],
                    ['status' => 'status_with_underscore', 'count' => 4],
                    ['status' => 'status-with-dash', 'count' => 1],
                    ['status' => 'status.with.dots', 'count' => 3],
                ],
                ['状态-中文' => 2, 'status_with_underscore' => 4, 'status-with-dash' => 1, 'status.with.dots' => 3],
            ],
        ];
    }

    /**
     * 测试处理有效的统计数据
     *
     * @param mixed $stats 输入统计数据
     * @param array<string, int> $expected 预期结果
     */
    #[DataProvider('provideValidStatsData')]
    public function testProcessStatsWithValidData(mixed $stats, array $expected): void
    {
        $result = $this->processor->processStats($stats);

        $this->assertSame($expected, $result);

        // 验证返回的数组键都是字符串，值都是整数
        foreach ($result as $status => $count) {
            // 验证具体的键值对而不是类型，因为类型已经确定
            $this->assertArrayHasKey($status, $expected);
            $this->assertEquals($expected[$status], $count);
        }
    }

    /**
     * 测试处理无效的统计数据
     *
     * @param mixed $stats 无效输入数据
     */
    #[DataProvider('provideInvalidStatsData')]
    public function testProcessStatsWithInvalidData(mixed $stats): void
    {
        $result = $this->processor->processStats($stats);

        $this->assertSame([], $result);
    }

    /**
     * 测试处理包含有效和无效项混合的统计数据
     *
     * @param mixed $stats 混合数据
     * @param array<string, int> $expected 预期的有效结果
     */
    #[DataProvider('provideMixedValidInvalidStatsData')]
    public function testProcessStatsWithMixedValidInvalidData(mixed $stats, array $expected): void
    {
        $result = $this->processor->processStats($stats);

        $this->assertSame($expected, $result);

        // 验证只包含有效的统计项
        foreach ($result as $status => $count) {
            $this->assertArrayHasKey($status, $expected);
            $this->assertEquals($expected[$status], $count);
        }
    }

    /**
     * 测试边界情况和特殊值
     *
     * @param mixed $stats 边界情况数据
     * @param array<string, int> $expected 预期结果
     */
    #[DataProvider('provideEdgeCaseStatsData')]
    public function testProcessStatsWithEdgeCases(mixed $stats, array $expected): void
    {
        $result = $this->processor->processStats($stats);

        $this->assertSame($expected, $result);
    }

    /**
     * 测试重复状态的处理（后面的值会覆盖前面的）
     */
    public function testProcessStatsWithDuplicateStatuses(): void
    {
        $stats = [
            ['status' => 'active', 'count' => 10],
            ['status' => 'active', 'count' => 20], // 这个会覆盖前面的
            ['status' => 'inactive', 'count' => 5],
            ['status' => 'active', 'count' => 30], // 这个会再次覆盖
        ];

        $result = $this->processor->processStats($stats);

        $this->assertSame(['active' => 30, 'inactive' => 5], $result);
    }

    /**
     * 测试空数组输入
     */
    public function testProcessStatsWithEmptyArray(): void
    {
        $stats = [];
        $result = $this->processor->processStats($stats);

        $this->assertSame([], $result);
    }

    /**
     * 测试复杂的嵌套结构（应该被过滤掉）
     */
    public function testProcessStatsWithComplexNestedStructure(): void
    {
        $stats = [
            ['status' => 'simple', 'count' => 1],
            [
                'status' => 'nested',
                'count' => 2,
                'extra' => 'field',
                'data' => ['complex' => 'structure'],
            ],
            ['status' => 'simple2', 'count' => 3],
        ];

        $result = $this->processor->processStats($stats);

        $this->assertSame(['simple' => 1, 'nested' => 2, 'simple2' => 3], $result);
    }

    /**
     * 测试类型转换的边界情况
     */
    public function testProcessStatsTypeConversions(): void
    {
        $stats = [
            ['status' => 'string_int', 'count' => '42'],
            ['status' => 'string_float', 'count' => '3.14'],
            ['status' => 'actual_float', 'count' => 2.71],
            ['status' => 'scientific', 'count' => '1e3'],
            ['status' => 'hex', 'count' => '0xFF'], // 这不会被识别为数字
        ];

        $result = $this->processor->processStats($stats);

        // 注意：'0xFF' 不会被 is_numeric() 识别为数字
        $this->assertSame([
            'string_int' => 42,
            'string_float' => 3,
            'actual_float' => 2,
            'scientific' => 1000,
        ], $result);
    }

    /**
     * 测试处理大量数据项
     */
    public function testProcessStatsWithLargeDataset(): void
    {
        $stats = [];
        $expected = [];

        // 生成1000个测试项
        for ($i = 0; $i < 1000; ++$i) {
            $status = 'status_' . ($i % 10); // 10个不同的状态
            $count = $i + 1;
            $stats[] = ['status' => $status, 'count' => $count];

            // 最后的值会覆盖前面的
            $expected[$status] = $i > 990 ? $count : ((($i % 10) + 1) + 990);
        }

        $result = $this->processor->processStats($stats);

        $this->assertSame($expected, $result);
        $this->assertCount(10, $result);
    }

    /**
     * 集成测试：模拟真实API响应场景
     */
    public function testRealWorldApiIntegrationScenario(): void
    {
        // 模拟来自智能体统计API的真实响应
        $apiResponse = [
            ['status' => 'online', 'count' => 25],
            ['status' => 'busy', 'count' => 8],
            ['status' => 'offline', 'count' => 12],
            ['status' => 'maintenance', 'count' => 2],
            // API可能返回的一些边界情况
            ['status' => 'error', 'count' => '0'], // 字符串形式的0
            ['status' => 'unknown', 'count' => null], // null计数（会被过滤）
            'invalid_entry', // 无效条目（会被过滤）
            ['count' => 5], // 缺少status（会被过滤）
            ['status' => '', 'count' => 3], // 空状态
        ];

        $result = $this->processor->processStats($apiResponse);

        $expected = [
            'online' => 25,
            'busy' => 8,
            'offline' => 12,
            'maintenance' => 2,
            'error' => 0,
            '' => 3,
        ];

        $this->assertSame($expected, $result);

        // 验证结果的业务含义
        $this->assertArrayHasKey('online', $result);
        $this->assertArrayHasKey('busy', $result);
        $this->assertArrayHasKey('offline', $result);
        $this->assertArrayHasKey('maintenance', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('', $result); // 空状态

        // 验证总数合理性
        $totalAgents = array_sum($result);
        $this->assertGreaterThan(0, $totalAgents);
        $this->assertSame(50, $totalAgents); // 25+8+12+2+0+3 = 50
    }

    /**
     * 测试与真实业务场景的集成
     */
    public function testBusinessScenarioIntegration(): void
    {
        // 模拟智能体在一天不同时段的状态统计
        $morningStats = [
            ['status' => 'starting', 'count' => 5],
            ['status' => 'running', 'count' => 15],
            ['status' => 'idle', 'count' => 3],
        ];

        $afternoonStats = [
            ['status' => 'running', 'count' => 18],
            ['status' => 'busy', 'count' => 8],
            ['status' => 'error', 'count' => 2],
        ];

        $eveningStats = [
            ['status' => 'stopping', 'count' => 3],
            ['status' => 'stopped', 'count' => 20],
            ['status' => 'maintenance', 'count' => 2],
        ];

        // 处理不同时段的统计
        $morningResult = $this->processor->processStats($morningStats);
        $afternoonResult = $this->processor->processStats($afternoonStats);
        $eveningResult = $this->processor->processStats($eveningStats);

        // 验证各时段的统计结果
        $this->assertSame(['starting' => 5, 'running' => 15, 'idle' => 3], $morningResult);
        $this->assertSame(['running' => 18, 'busy' => 8, 'error' => 2], $afternoonResult);
        $this->assertSame(['stopping' => 3, 'stopped' => 20, 'maintenance' => 2], $eveningResult);

        // 业务逻辑验证
        $this->assertSame(23, array_sum($morningResult)); // 早上总计23个智能体
        $this->assertSame(28, array_sum($afternoonResult)); // 下午总计28个智能体
        $this->assertSame(25, array_sum($eveningResult)); // 晚上总计25个智能体

        // 验证关键业务状态
        $this->assertArrayHasKey('running', $morningResult);
        $this->assertArrayHasKey('running', $afternoonResult);
        $this->assertArrayHasKey('stopped', $eveningResult);
    }

    /**
     * 性能测试：确保处理大量数据时性能可接受
     */
    public function testPerformanceWithLargeDataset(): void
    {
        $largeStats = [];
        $statusTypes = ['active', 'inactive', 'pending', 'error', 'maintenance'];

        // 生成10000个统计项
        for ($i = 0; $i < 10000; ++$i) {
            $status = $statusTypes[$i % count($statusTypes)];
            $count = rand(1, 1000);
            $largeStats[] = ['status' => $status, 'count' => $count];
        }

        $startTime = microtime(true);
        $result = $this->processor->processStats($largeStats);
        $endTime = microtime(true);

        // 验证结果正确性
        $this->assertCount(count($statusTypes), $result);

        // 验证每个状态都有对应的结果
        foreach ($statusTypes as $status) {
            $this->assertArrayHasKey($status, $result);
            // 验证值为非负整数（更有意义的业务逻辑检查）
            $this->assertGreaterThanOrEqual(0, $result[$status]);
        }

        // 性能断言：处理10000项应该在合理时间内完成（比如1秒内）
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(1.0, $executionTime, '处理大量统计数据应该在合理时间内完成');
    }
}
