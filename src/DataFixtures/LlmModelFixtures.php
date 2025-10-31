<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\RAGFlowApiBundle\Entity\LlmModel;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Repository\RAGFlowInstanceRepository;

/**
 * LLM模型数据Fixtures
 *
 * 基于RAGFlow API返回的实际JSON数据创建LLM模型测试数据
 */
#[When(env: 'test')]
#[When(env: 'dev')]
class LlmModelFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public function __construct(
        private readonly RAGFlowInstanceRepository $instanceRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $instance = $this->instanceRepository->findOneBy(['name' => 'user1']);
        if (null === $instance) {
            throw new \RuntimeException('RAGFlowInstance not found. Please load RAGFlowInstanceFixtures first.');
        }

        $llmData = $this->getLlmModelData();
        $this->createModelsFromData($manager, $instance, $llmData);
        $manager->flush();
    }

    /**
     * @param array<string, array<int, array<string, mixed>>> $llmData
     */
    private function createModelsFromData(ObjectManager $manager, RAGFlowInstance $instance, array $llmData): void
    {
        foreach ($llmData as $providerName => $models) {
            $this->createModelsForProvider($manager, $instance, $providerName, $models);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $models
     */
    private function createModelsForProvider(ObjectManager $manager, RAGFlowInstance $instance, string $providerName, array $models): void
    {
        foreach ($models as $modelData) {
            $llmModel = $this->createLlmModel($modelData, $providerName, $instance);
            $manager->persist($llmModel);
        }
    }

    /**
     * @param array<string, mixed> $modelData
     */
    private function createLlmModel(array $modelData, string $providerName, RAGFlowInstance $instance): LlmModel
    {
        $this->validateRequiredFields($modelData);

        $llmModel = new LlmModel();
        $this->setRequiredFields($llmModel, $modelData, $providerName, $instance);
        $this->setOptionalFields($llmModel, $modelData);
        $this->setTimestampFields($llmModel, $modelData);

        return $llmModel;
    }

    /**
     * 验证必需字段
     *
     * @param array<string, mixed> $modelData
     */
    private function validateRequiredFields(array $modelData): void
    {
        $this->validateFidField($modelData);
        $this->validateLlmNameField($modelData);
        $this->validateAvailableField($modelData);
        $this->validateModelTypeField($modelData);
    }

    /**
     * 验证fid字段
     *
     * @param array<string, mixed> $modelData
     */
    private function validateFidField(array $modelData): void
    {
        if (!isset($modelData['fid']) || !is_string($modelData['fid'])) {
            throw new \RuntimeException('Missing or invalid fid in model data');
        }
    }

    /**
     * 验证llm_name字段
     *
     * @param array<string, mixed> $modelData
     */
    private function validateLlmNameField(array $modelData): void
    {
        if (!isset($modelData['llm_name']) || !is_string($modelData['llm_name'])) {
            throw new \RuntimeException('Missing or invalid llm_name in model data');
        }
    }

    /**
     * 验证available字段
     *
     * @param array<string, mixed> $modelData
     */
    private function validateAvailableField(array $modelData): void
    {
        if (!isset($modelData['available']) || !is_bool($modelData['available'])) {
            throw new \RuntimeException('Missing or invalid available in model data');
        }
    }

    /**
     * 验证model_type字段
     *
     * @param array<string, mixed> $modelData
     */
    private function validateModelTypeField(array $modelData): void
    {
        if (!isset($modelData['model_type']) || !is_string($modelData['model_type'])) {
            throw new \RuntimeException('Missing or invalid model_type in model data');
        }
    }

    /**
     * 设置必需字段
     *
     * @param array<string, mixed> $modelData
     */
    private function setRequiredFields(LlmModel $llmModel, array $modelData, string $providerName, RAGFlowInstance $instance): void
    {
        // 在入口处断言类型，确保 PHPStan 能理解这些字段已通过验证
        assert(is_string($modelData['fid']));
        assert(is_string($modelData['llm_name']));
        assert(is_bool($modelData['available']));
        assert(is_string($modelData['model_type']));

        $llmModel->setFid($modelData['fid']);
        $llmModel->setLlmName($modelData['llm_name']);
        $llmModel->setAvailable($modelData['available']);
        $llmModel->setModelType($modelData['model_type']);
        $llmModel->setProviderName($providerName);
        $llmModel->setRagFlowInstance($instance);
    }

    /**
     * @param array<string, mixed> $modelData
     */
    private function setOptionalFields(LlmModel $llmModel, array $modelData): void
    {
        $this->setMaxTokensIfPresent($llmModel, $modelData);
        $this->setStatusIfPresent($llmModel, $modelData);
        $this->setIsToolsIfPresent($llmModel, $modelData);
        $this->setTagsIfPresent($llmModel, $modelData);
    }

    /**
     * 设置max_tokens字段(如果存在)
     *
     * @param array<string, mixed> $modelData
     */
    private function setMaxTokensIfPresent(LlmModel $llmModel, array $modelData): void
    {
        if (isset($modelData['max_tokens']) && is_int($modelData['max_tokens'])) {
            $llmModel->setMaxTokens($modelData['max_tokens']);
        }
    }

    /**
     * 设置status字段(如果存在)
     *
     * @param array<string, mixed> $modelData
     */
    private function setStatusIfPresent(LlmModel $llmModel, array $modelData): void
    {
        if (isset($modelData['status']) && is_int($modelData['status'])) {
            $llmModel->setStatus($modelData['status']);
        }
    }

    /**
     * 设置is_tools字段(如果存在)
     *
     * @param array<string, mixed> $modelData
     */
    private function setIsToolsIfPresent(LlmModel $llmModel, array $modelData): void
    {
        if (isset($modelData['is_tools']) && is_bool($modelData['is_tools'])) {
            $llmModel->setIsTools($modelData['is_tools']);
        }
    }

    /**
     * 设置tags字段(如果存在)
     *
     * @param array<string, mixed> $modelData
     */
    private function setTagsIfPresent(LlmModel $llmModel, array $modelData): void
    {
        if (!isset($modelData['tags']) || !is_array($modelData['tags'])) {
            return;
        }

        $tags = array_filter($modelData['tags'], 'is_string');
        $llmModel->setTags($tags);
    }

    /**
     * @param array<string, mixed> $modelData
     */
    private function setTimestampFields(LlmModel $llmModel, array $modelData): void
    {
        $this->setCreateTimestamps($llmModel, $modelData);
        $this->setUpdateTimestamps($llmModel, $modelData);
    }

    /**
     * @param array<string, mixed> $modelData
     */
    private function setCreateTimestamps(LlmModel $llmModel, array $modelData): void
    {
        $this->setCreateDateIfPresent($llmModel, $modelData);
        $this->setCreateTimeIfPresent($llmModel, $modelData);
    }

    /**
     * 设置create_date字段(如果存在)
     *
     * @param array<string, mixed> $modelData
     */
    private function setCreateDateIfPresent(LlmModel $llmModel, array $modelData): void
    {
        $createDate = $this->parseTimestamp($modelData, 'create_date');
        if (null !== $createDate) {
            $llmModel->setApiCreateDate($createDate);
        }
    }

    /**
     * 设置create_time字段(如果存在)
     *
     * @param array<string, mixed> $modelData
     */
    private function setCreateTimeIfPresent(LlmModel $llmModel, array $modelData): void
    {
        $createTime = $this->parseTimestamp($modelData, 'create_time');
        if (null !== $createTime) {
            $llmModel->setApiCreateTime($createTime);
        }
    }

    /**
     * @param array<string, mixed> $modelData
     */
    private function setUpdateTimestamps(LlmModel $llmModel, array $modelData): void
    {
        $this->setUpdateDateIfPresent($llmModel, $modelData);
        $this->setUpdateTimeIfPresent($llmModel, $modelData);
    }

    /**
     * 设置update_date字段(如果存在)
     *
     * @param array<string, mixed> $modelData
     */
    private function setUpdateDateIfPresent(LlmModel $llmModel, array $modelData): void
    {
        $updateDate = $this->parseTimestamp($modelData, 'update_date');
        if (null !== $updateDate) {
            $llmModel->setApiUpdateDate($updateDate);
        }
    }

    /**
     * 设置update_time字段(如果存在)
     *
     * @param array<string, mixed> $modelData
     */
    private function setUpdateTimeIfPresent(LlmModel $llmModel, array $modelData): void
    {
        $updateTime = $this->parseTimestamp($modelData, 'update_time');
        if (null !== $updateTime) {
            $llmModel->setApiUpdateTime($updateTime);
        }
    }

    /**
     * 解析时间戳
     *
     * @param array<string, mixed> $modelData
     */
    private function parseTimestamp(array $modelData, string $key): ?\DateTimeImmutable
    {
        if (!isset($modelData[$key]) || !is_string($modelData[$key])) {
            return null;
        }

        $timestamp = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $modelData[$key]);

        return false !== $timestamp ? $timestamp : null;
    }

    public function getDependencies(): array
    {
        return [
            RAGFlowInstanceFixtures::class,
        ];
    }

    /**
     * 动态从llm.json读取全部480个模型数据
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function getLlmModelData(): array
    {
        $jsonContent = $this->readJsonFile();
        $jsonData = $this->decodeJsonContent($jsonContent);
        $data = $this->extractDataField($jsonData);

        /** @var array<string, array<int, array<string, mixed>>> $data */
        return $this->normalizeJsonData($data);
    }

    private function readJsonFile(): string
    {
        $jsonFile = __DIR__ . '/../../llm.json';

        if (!file_exists($jsonFile)) {
            throw new \RuntimeException("LLM JSON file not found: {$jsonFile}");
        }

        $jsonContent = file_get_contents($jsonFile);
        if (false === $jsonContent) {
            throw new \RuntimeException("Failed to read LLM JSON file: {$jsonFile}");
        }

        return $jsonContent;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonContent(string $jsonContent): array
    {
        $jsonData = json_decode($jsonContent, true);
        if (!is_array($jsonData)) {
            throw new \RuntimeException('Invalid LLM JSON data: expected array');
        }

        // 断言键为字符串类型，符合预期的数据结构
        foreach (array_keys($jsonData) as $key) {
            assert(is_string($key));
        }

        /** @var array<string, mixed> $jsonData */
        return $jsonData;
    }

    /**
     * @param array<string, mixed> $jsonData
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function extractDataField(array $jsonData): array
    {
        if (!isset($jsonData['data']) || !is_array($jsonData['data'])) {
            throw new \RuntimeException('Invalid LLM JSON data structure: missing or invalid data field');
        }

        $data = $jsonData['data'];

        // 验证结构：每个提供商名称（键）应为字符串，值应为模型数组
        foreach ($data as $providerName => $models) {
            assert(is_string($providerName));
            assert(is_array($models));
            foreach ($models as $model) {
                assert(is_array($model));
            }
        }

        /** @var array<string, array<int, array<string, mixed>>> $data */
        return $data;
    }

    /**
     * 标准化JSON数据，处理数据类型转换
     *
     * @param array<string, array<int, array<string, mixed>>> $rawData
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function normalizeJsonData(array $rawData): array
    {
        $normalizedData = [];

        foreach ($rawData as $providerName => $models) {
            $normalizedData[$providerName] = [];

            foreach ($models as $model) {
                $normalizedData[$providerName][] = $this->normalizeModelData($model);
            }
        }

        return $normalizedData;
    }

    /**
     * 标准化单个模型数据
     *
     * @param array<string, mixed> $model
     * @return array<string, mixed>
     */
    private function normalizeModelData(array $model): array
    {
        $normalized = $model;

        $normalized = $this->normalizeStatus($normalized);
        $normalized = $this->normalizeTags($normalized);
        $normalized = $this->normalizeTimestamps($normalized);

        return $this->normalizeDates($normalized);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeStatus(array $data): array
    {
        if (!isset($data['status'])) {
            return $data;
        }

        $data['status'] = $this->convertToStatusInt($data['status']);

        return $data;
    }

    /**
     * 转换为状态整数
     */
    private function convertToStatusInt(mixed $status): int
    {
        if (is_int($status)) {
            return $status;
        }

        if (is_string($status) || is_float($status)) {
            return (int) $status;
        }

        return 0;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeTags(array $data): array
    {
        if (isset($data['tags']) && is_string($data['tags'])) {
            $data['tags'] = array_map('trim', explode(',', $data['tags']));
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeTimestamps(array $data): array
    {
        $data = $this->normalizeCreateTime($data);

        return $this->normalizeUpdateTime($data);
    }

    /**
     * 标准化create_time
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeCreateTime(array $data): array
    {
        if (isset($data['create_time']) && is_numeric($data['create_time'])) {
            $data['create_time'] = $this->convertTimestampToDateString($data['create_time']);
        }

        return $data;
    }

    /**
     * 标准化update_time
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeUpdateTime(array $data): array
    {
        if (isset($data['update_time']) && is_numeric($data['update_time'])) {
            $data['update_time'] = $this->convertTimestampToDateString($data['update_time']);
        }

        return $data;
    }

    /**
     * 转换时间戳为日期字符串
     */
    private function convertTimestampToDateString(int|float|string $timestamp): string
    {
        $unixTimestamp = (int) ((float) $timestamp / 1000);

        return date('Y-m-d H:i:s', $unixTimestamp);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeDates(array $data): array
    {
        if (isset($data['create_date']) && is_string($data['create_date'])) {
            $data['create_date'] = $this->convertGmtDate($data['create_date']);
        }

        if (isset($data['update_date']) && is_string($data['update_date'])) {
            $data['update_date'] = $this->convertGmtDate($data['update_date']);
        }

        return $data;
    }

    /**
     * 转换GMT日期格式
     */
    private function convertGmtDate(string $gmtDate): ?string
    {
        $timestamp = strtotime($gmtDate);

        return false !== $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
    }

    public static function getGroups(): array
    {
        return [
            'rag-flow-api',
            'llm-model',
        ];
    }
}
