<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Helper\ChatAssistant;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Helper\ChatAssistant\LlmModelExtractor;

/**
 * @internal
 */
#[CoversClass(LlmModelExtractor::class)]
final class LlmModelExtractorTest extends TestCase
{
    private LlmModelExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new LlmModelExtractor();
    }

    public function testExtractModelsReturnsEmptyWhenNoData(): void
    {
        $response = [];

        $result = $this->extractor->extractModels($response);

        $this->assertSame([], $result);
    }

    public function testExtractModelsReturnsEmptyWhenDataNotArray(): void
    {
        $response = ['data' => 'not-an-array'];

        $result = $this->extractor->extractModels($response);

        $this->assertSame([], $result);
    }

    public function testExtractModelsExtractsValidModels(): void
    {
        $response = [
            'data' => [
                'OpenAI' => [
                    [
                        'fid' => 'gpt-4',
                        'llm_name' => 'GPT-4',
                        'model_type' => 'chat',
                        'available' => true,
                    ],
                ],
                'DeepSeek' => [
                    [
                        'fid' => 'deepseek-chat',
                        'llm_name' => 'DeepSeek Chat',
                        'model_type' => 'chat',
                        'available' => true,
                    ],
                ],
            ],
        ];

        $result = $this->extractor->extractModels($response);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('GPT-4 (OpenAI)', $result);
        $this->assertSame('gpt-4', $result['GPT-4 (OpenAI)']);
        $this->assertArrayHasKey('DeepSeek Chat (DeepSeek)', $result);
        $this->assertSame('deepseek-chat', $result['DeepSeek Chat (DeepSeek)']);
    }

    public function testIsValidChatModelReturnsTrueForValidModel(): void
    {
        $model = [
            'fid' => 'test-id',
            'llm_name' => 'Test Model',
            'model_type' => 'chat',
            'available' => true,
        ];

        $result = $this->extractor->isValidChatModel($model);

        $this->assertTrue($result);
    }

    public function testIsValidChatModelReturnsFalseWhenNotAvailable(): void
    {
        $model = [
            'fid' => 'test-id',
            'llm_name' => 'Test Model',
            'model_type' => 'chat',
            'available' => false,
        ];

        $result = $this->extractor->isValidChatModel($model);

        $this->assertFalse($result);
    }

    public function testIsValidChatModelReturnsFalseWhenModelTypeIsNotChat(): void
    {
        $model = [
            'fid' => 'test-id',
            'llm_name' => 'Test Model',
            'model_type' => 'embedding',
            'available' => true,
        ];

        $result = $this->extractor->isValidChatModel($model);

        $this->assertFalse($result);
    }

    public function testGetDefaultModelsReturnsExpectedModels(): void
    {
        $result = $this->extractor->getDefaultModels();

        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('GPT-4 (OpenAI)', $result);
        $this->assertSame('gpt-4', $result['GPT-4 (OpenAI)']);
    }
}
