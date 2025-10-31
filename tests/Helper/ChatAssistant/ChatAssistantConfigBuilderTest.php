<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Helper\ChatAssistant;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Entity\ChatAssistant;
use Tourze\RAGFlowApiBundle\Helper\ChatAssistant\ChatAssistantConfigBuilder;

/**
 * @internal
 */
#[CoversClass(ChatAssistantConfigBuilder::class)]
final class ChatAssistantConfigBuilderTest extends TestCase
{
    private ChatAssistantConfigBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new ChatAssistantConfigBuilder();
    }

    public function testBuildLlmConfigReturnsNullWhenAllFieldsNull(): void
    {
        $entity = $this->createMock(ChatAssistant::class);
        $entity->expects($this->once())->method('getLlmModel')->willReturn(null);
        $entity->expects($this->once())->method('getTemperature')->willReturn(null);
        $entity->expects($this->once())->method('getTopP')->willReturn(null);
        $entity->expects($this->once())->method('getPresencePenalty')->willReturn(null);
        $entity->expects($this->once())->method('getFrequencyPenalty')->willReturn(null);
        $result = $this->builder->buildLlmConfig($entity);
        $this->assertNull($result);
    }

    public function testBuildLlmConfigReturnsArrayWithModelName(): void
    {
        $entity = $this->createMock(ChatAssistant::class);
        $entity->expects($this->once())->method('getLlmModel')->willReturn('gpt-4');
        $entity->expects($this->once())->method('getTemperature')->willReturn(null);
        $result = $this->builder->buildLlmConfig($entity);
        $this->assertIsArray($result);
        $this->assertSame('gpt-4', $result['model_name']);
    }

    public function testBuildPromptConfigIncludesShowQuote(): void
    {
        $entity = $this->createMock(ChatAssistant::class);
        $entity->expects($this->once())->method('getSimilarityThreshold')->willReturn(null);
        $entity->expects($this->once())->method('getShowQuote')->willReturn(true);
        $result = $this->builder->buildPromptConfig($entity);
        $this->assertArrayHasKey('show_quote', $result);
        $this->assertTrue($result['show_quote']);
    }

    public function testBuildApiDataIncludesBasicFields(): void
    {
        $entity = $this->createMock(ChatAssistant::class);
        $entity->expects($this->once())->method('getName')->willReturn('Test Assistant');
        $entity->expects($this->once())->method('getDatasetIds')->willReturn(['ds1', 'ds2']);
        $entity->expects($this->once())->method('getDescription')->willReturn(null);
        $entity->expects($this->once())->method('getSystemPrompt')->willReturn(null);
        $entity->expects($this->once())->method('getAvatar')->willReturn(null);
        $entity->expects($this->once())->method('getLanguage')->willReturn(null);
        $entity->expects($this->once())->method('getLlmModel')->willReturn(null);
        $entity->expects($this->once())->method('getShowQuote')->willReturn(false);
        $result = $this->builder->buildApiData($entity);
        $this->assertSame('Test Assistant', $result['name']);
        $this->assertSame(['ds1', 'ds2'], $result['dataset_ids']);
        $this->assertArrayHasKey('prompt', $result);
    }
}
