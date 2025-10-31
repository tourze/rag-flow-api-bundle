<?php

declare(strict_types=1);

/**
 * 生成 LLM 模型 Fixtures 数据的脚本
 * 基于 llm.json 文件的真实数据
 */

// 读取JSON文件
$jsonFile = __DIR__ . '/llm.json';
if (!file_exists($jsonFile)) {
    exit("JSON file not found: {$jsonFile}\n");
}

$fileContents = file_get_contents($jsonFile);
if (false === $fileContents) {
    exit("Failed to read JSON file\n");
}

/** @var array{data: array<string, array<int, array<string, mixed>>>}|null $jsonData */
$jsonData = json_decode($fileContents, true);
if (null === $jsonData || !isset($jsonData['data']) || !is_array($jsonData['data'])) {
    exit("Invalid JSON data\n");
}

// 选择重要的提供商（包含主流和常用的）
$importantProviders = [
    'OpenAI',          // 最主流
    'Anthropic',       // Claude
    'DeepSeek',        // 国产优秀
    'ZHIPU-AI',        // 智谱AI
    'Tongyi-Qianwen',  // 阿里巴巴通义千问
    'Moonshot',        // 月之暗面
    'BAAI',            // 向量模型
    'Jina',            // 向量模型
    'Cohere',          // 重排序模型
    'Gemini',          // Google
    'Mistral',         // 欧洲模型
    'Groq',             // 高速推理
];

/**
 * 转换日期格式
 * @param mixed $dateString
 */
function convertDate($dateString): ?string
{
    if (!is_string($dateString) || '' === $dateString) {
        return null;
    }

    // 处理 GMT 格式: "Tue, 23 Sep 2025 16:50:47 GMT"
    $timestamp = strtotime($dateString);
    if (false === $timestamp) {
        return null;
    }

    return date('Y-m-d H:i:s', $timestamp);
}

/**
 * 转换标签
 * @param mixed $tagsString
 * @return list<string>|null
 */
function convertTags($tagsString): ?array
{
    if (!is_string($tagsString) || '' === $tagsString) {
        return null;
    }

    return array_map('trim', explode(',', $tagsString));
}

/**
 * 格式化单个模型的必需字段
 * @param array<string, mixed> $model
 */
function formatRequiredFields(array $model): string
{
    $llmNameRaw = $model['llm_name'] ?? '';
    $llmName = is_string($llmNameRaw) ? $llmNameRaw : '';

    $modelTypeRaw = $model['model_type'] ?? '';
    $modelType = is_string($modelTypeRaw) ? $modelTypeRaw : '';

    $availableValue = $model['available'] ?? false;
    $available = isTruthy($availableValue) ? 'true' : 'false';

    $lines = [
        "                    'fid' => '" . addslashes($llmName) . "',",
        "                    'llm_name' => '" . addslashes($llmName) . "',",
        "                    'available' => " . $available . ',',
        "                    'model_type' => '" . addslashes($modelType) . "',",
    ];

    return implode("\n", $lines) . "\n";
}

/**
 * 格式化max_tokens字段
 * @param array<string, mixed> $model
 */
function formatMaxTokens(array $model): ?string
{
    if (!isset($model['max_tokens'])) {
        return null;
    }

    $maxTokens = is_numeric($model['max_tokens']) ? $model['max_tokens'] : 0;

    return "                    'max_tokens' => " . $maxTokens . ',';
}

/**
 * 格式化status字段
 * @param array<string, mixed> $model
 */
function formatStatus(array $model): ?string
{
    if (!isset($model['status'])) {
        return null;
    }

    $status = is_numeric($model['status']) ? (int) $model['status'] : 1;

    return "                    'status' => " . $status . ',';
}

/**
 * 格式化is_tools字段
 * @param array<string, mixed> $model
 */
function formatIsTools(array $model): ?string
{
    if (!isset($model['is_tools'])) {
        return null;
    }

    $isToolsValue = $model['is_tools'];
    $value = isTruthy($isToolsValue) ? 'true' : 'false';

    return "                    'is_tools' => " . $value . ',';
}

/**
 * 格式化tags字段
 * @param array<string, mixed> $model
 */
function formatTags(array $model): ?string
{
    if (!isset($model['tags'])) {
        return null;
    }

    $tagsArray = convertTags($model['tags']);
    if (null === $tagsArray) {
        return null;
    }

    $escapedTags = array_map(
        function (string $tag): string {
            return addslashes($tag);
        },
        $tagsArray
    );
    $tagsStr = "['" . implode("', '", $escapedTags) . "']";

    return "                    'tags' => " . $tagsStr . ',';
}

/**
 * 格式化单个模型的可选字段
 * @param array<string, mixed> $model
 */
function formatOptionalFields(array $model): string
{
    $lines = array_filter(
        [
            formatMaxTokens($model),
            formatStatus($model),
            formatIsTools($model),
            formatTags($model),
        ],
        function (?string $value): bool {
            return null !== $value;
        }
    );

    return count($lines) > 0 ? implode("\n", $lines) . "\n" : '';
}

/**
 * 转换时间戳为日期字符串
 * @param mixed $timestamp
 */
function convertTimestamp($timestamp): ?string
{
    if (!is_numeric($timestamp)) {
        return null;
    }

    $seconds = (int) ($timestamp / 1000);

    return date('Y-m-d H:i:s', $seconds);
}

/**
 * 格式化单个模型的时间字段
 * @param array<string, mixed> $model
 */
function formatTimeFields(array $model): string
{
    $lines = [];
    $timeFields = [
        'create_date' => 'convertDate',
        'create_time' => 'convertTimestamp',
        'update_date' => 'convertDate',
        'update_time' => 'convertTimestamp',
    ];

    foreach ($timeFields as $field => $converter) {
        if (!isset($model[$field])) {
            continue;
        }

        $date = $converter($model[$field]);
        if (null !== $date) {
            $lines[] = "                    '{$field}' => '{$date}',";
        }
    }

    return count($lines) > 0 ? implode("\n", $lines) . "\n" : '';
}

/**
 * 格式化单个模型数据
 * @param array<string, mixed> $model
 */
function formatModel(array $model): string
{
    $output = "                [\n";
    $output .= formatRequiredFields($model);
    $output .= formatOptionalFields($model);
    $output .= formatTimeFields($model);
    $output .= "                ],\n";

    return $output;
}

/**
 * 格式化单个提供商的数据
 * @param array<int, array<string, mixed>> $models
 */
function formatProvider(array $models): string
{
    $output = '';
    foreach ($models as $model) {
        $output .= formatModel($model);
    }

    return $output;
}

/**
 * 生成PHP数组格式的字符串
 * @param array<string, array<int, array<string, mixed>>> $data
 */
function generatePhpArray(array $data): string
{
    $output = "[\n";

    foreach ($data as $provider => $models) {
        $output .= "            '{$provider}' => [\n";
        $output .= formatProvider($models);
        $output .= "            ],\n";
    }

    $output .= '        ]';

    return $output;
}

/**
 * 判断值是否为真值
 * @param mixed $value
 */
function isTruthy($value): bool
{
    if (is_bool($value)) {
        return $value;
    }

    return 0 !== $value && '' !== $value;
}

/**
 * 获取模型类型的排序权重
 * @param array<string, mixed> $model
 */
function getModelTypeOrder(array $model): int
{
    $typeOrder = ['chat' => 1, 'embedding' => 2, 'rerank' => 3];
    $modelTypeValue = $model['model_type'] ?? '';
    $modelType = is_string($modelTypeValue) ? $modelTypeValue : '';

    return $typeOrder[$modelType] ?? 4;
}

/**
 * 比较两个模型的优先级
 * @param array<string, mixed> $a
 * @param array<string, mixed> $b
 */
function compareModels(array $a, array $b): int
{
    // 优先可用的模型
    $aAvailable = isTruthy($a['available'] ?? false);
    $bAvailable = isTruthy($b['available'] ?? false);

    if ($aAvailable !== $bAvailable) {
        return $bAvailable <=> $aAvailable;
    }

    // 然后按类型排序：chat > embedding > rerank
    return getModelTypeOrder($a) <=> getModelTypeOrder($b);
}

// 过滤并准备数据
$filteredData = [];
$totalModels = 0;

foreach ($importantProviders as $provider) {
    if (!isset($jsonData['data'][$provider])) {
        continue;
    }

    $providerData = $jsonData['data'][$provider];
    if (!is_array($providerData)) {
        continue;
    }

    /** @var array<int, array<string, mixed>> $models */
    $models = $providerData;

    // 限制每个提供商的模型数量，优先选择可用的模型
    usort($models, 'compareModels');

    // 每个提供商最多取前15个模型
    $filteredData[$provider] = array_slice($models, 0, 15);
    $totalModels += count($filteredData[$provider]);
}

echo '已选择 ' . count($filteredData) . " 个提供商，共 {$totalModels} 个模型\n";

// 生成PHP代码
$phpCode = generatePhpArray($filteredData);

// 输出到文件或控制台
echo "\n\n生成的PHP数组代码：\n";
echo "    private function getLlmModelData(): array\n    {\n        return ";
echo $phpCode;
echo ";\n    }\n";
