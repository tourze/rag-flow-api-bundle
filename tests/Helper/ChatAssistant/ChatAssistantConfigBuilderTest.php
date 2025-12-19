<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Helper\ChatAssistant;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Entity\ChatAssistant;
use Tourze\RAGFlowApiBundle\Helper\ChatAssistant\ChatAssistantConfigBuilder;

/**
 * @internal
 */
#[CoversClass(ChatAssistantConfigBuilder::class)]
#[RunTestsInSeparateProcesses]
class ChatAssistantConfigBuilderTest extends AbstractIntegrationTestCase
{
    private ChatAssistantConfigBuilder $builder;

    protected function onSetUp(): void
    {
        $this->builder = self::getService(ChatAssistantConfigBuilder::class);
    }

    public function testBuildLlmConfigReturnsNullWhenAllFieldsNull(): void
    {
        $entity = new ChatAssistant();
        $entity->setName('Test Assistant');
        $entity->setLlmModel(null);
        $entity->setTemperature(null);
        $entity->setTopP(null);
        $entity->setPresencePenalty(null);
        $entity->setFrequencyPenalty(null);

        $result = $this->builder->buildLlmConfig($entity);
        $this->assertNull($result);
    }

    public function testBuildLlmConfigReturnsArrayWithModelName(): void
    {
        $entity = new ChatAssistant();
        $entity->setName('Test Assistant');
        $entity->setLlmModel('gpt-4');
        $entity->setTemperature(null);
        $entity->setTopP(null);
        $entity->setPresencePenalty(null);
        $entity->setFrequencyPenalty(null);

        $result = $this->builder->buildLlmConfig($entity);
        $this->assertIsArray($result);
        $this->assertSame('gpt-4', $result['model_name']);
    }

    public function testBuildPromptConfigIncludesShowQuote(): void
    {
        $entity = new ChatAssistant();
        $entity->setName('Test Assistant');
        $entity->setSimilarityThreshold(null);
        $entity->setEmptyResponse(null);
        $entity->setOpener(null);
        $entity->setSystemPrompt(null);
        $entity->setShowQuote(true);

        $result = $this->builder->buildPromptConfig($entity);
        $this->assertArrayHasKey('show_quote', $result);
        $this->assertTrue($result['show_quote']);
    }

    public function testBuildApiDataIncludesBasicFields(): void
    {
        $entity = new ChatAssistant();
        $entity->setName('Test Assistant');
        $entity->setDatasetIds(['ds1', 'ds2']);
        $entity->setDescription(null);
        $entity->setSystemPrompt(null);
        $entity->setAvatar(null);
        $entity->setLanguage(null);
        $entity->setLlmModel(null);
        $entity->setTemperature(null);
        $entity->setTopP(null);
        $entity->setPresencePenalty(null);
        $entity->setFrequencyPenalty(null);
        $entity->setSimilarityThreshold(null);
        $entity->setShowQuote(false);
        $entity->setEmptyResponse(null);
        $entity->setOpener(null);

        $result = $this->builder->buildApiData($entity);
        $this->assertSame('Test Assistant', $result['name']);
        $this->assertSame(['ds1', 'ds2'], $result['dataset_ids']);
        $this->assertArrayHasKey('prompt', $result);
    }
}
