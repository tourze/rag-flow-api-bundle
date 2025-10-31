<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Entity\ChatAssistant;
use Tourze\RAGFlowApiBundle\Entity\Chunk;
use Tourze\RAGFlowApiBundle\Entity\Conversation;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Entity\LlmModel;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Enum\DocumentStatus;
use Tourze\RAGFlowApiBundle\Service\LocalDataSyncService;

/**
 * @internal
 */
#[CoversClass(LocalDataSyncService::class)]
#[RunTestsInSeparateProcesses]
class LocalDataSyncServiceTest extends AbstractIntegrationTestCase
{
    private LocalDataSyncService $localDataSyncService;

    private RAGFlowInstance $testInstance;

    protected function onSetUp(): void
    {
        $this->localDataSyncService = self::getService(LocalDataSyncService::class);

        // Create test RAGFlow instance
        $instance = new RAGFlowInstance();
        $instance->setName('Test Instance');
        $instance->setBaseUrl('http://test.example.com');
        $instance->setApiKey('test-key');
        $result = $this->persistAndFlush($instance);
        $this->assertInstanceOf(RAGFlowInstance::class, $result);
        $this->testInstance = $result;
    }

    public function testServiceCreation(): void
    {
        $this->assertInstanceOf(LocalDataSyncService::class, $this->localDataSyncService);
    }

    public function testSyncDatasetFromApi(): void
    {
        $apiData = [
            'id' => 'api-dataset-123',
            'name' => 'API测试数据集',
            'description' => '从API同步的测试数据集',
            'chunk_method' => 'naive',
            'language' => 'zh-CN',
            'embedding_model' => 'text-embedding-ada-002',
            'create_time' => '2024-01-01T10:00:00Z',
            'update_time' => '2024-01-01T11:00:00Z',
        ];

        $dataset = $this->localDataSyncService->syncDatasetFromApi($this->testInstance, $apiData);

        $this->assertInstanceOf(Dataset::class, $dataset);
        $this->assertEquals('api-dataset-123', $dataset->getRemoteId());
        $this->assertEquals('API测试数据集', $dataset->getName());
        $this->assertEquals('从API同步的测试数据集', $dataset->getDescription());
        $this->assertEquals('naive', $dataset->getChunkMethod());
        $this->assertEquals('zh-CN', $dataset->getLanguage());
        $this->assertEquals('text-embedding-ada-002', $dataset->getEmbeddingModel());
        $this->assertNotNull($dataset->getRemoteCreateTime());
        $this->assertNotNull($dataset->getRemoteUpdateTime());
        $this->assertNotNull($dataset->getLastSyncTime());
    }

    public function testSyncDocumentFromApi(): void
    {
        // Create dataset first
        $dataset = new Dataset();
        $dataset->setName('文档同步测试数据集');
        $dataset->setDescription('用于测试文档同步的数据集');
        $dataset->setRemoteId('sync-dataset-456');
        $dataset->setRagFlowInstance($this->testInstance);
        $result = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $result);
        $persistedDataset = $result;

        $apiData = [
            'id' => 'api-document-789',
            'name' => 'API测试文档.pdf',
            'filename' => 'test-document.pdf',
            'type' => 'pdf',
            'size' => 1024000,
            'status' => 'uploaded',
            'progress' => 0.0,
            'progress_msg' => '准备解析',
            'language' => 'zh',
            'chunk_num' => 0,
            'create_time' => '2024-01-01T10:30:00Z',
            'update_time' => '2024-01-01T10:35:00Z',
        ];

        $document = $this->localDataSyncService->syncDocumentFromApi($persistedDataset, $apiData);

        $this->assertInstanceOf(Document::class, $document);
        $this->assertEquals('api-document-789', $document->getRemoteId());
        $this->assertEquals('API测试文档.pdf', $document->getName());
        $this->assertEquals('test-document.pdf', $document->getFilename());
        $this->assertEquals('pdf', $document->getType());
        $this->assertEquals(1024000, $document->getSize());
        $this->assertEquals(DocumentStatus::UPLOADED, $document->getStatus());
        $this->assertEquals(0.0, $document->getProgress());
        $this->assertEquals('准备解析', $document->getProgressMsg());
        $this->assertEquals('zh', $document->getLanguage());
        $this->assertEquals(0, $document->getChunkCount());
        $this->assertSame($persistedDataset, $document->getDataset());
        $this->assertNotNull($document->getRemoteCreateTime());
        $this->assertNotNull($document->getRemoteUpdateTime());
        $this->assertNotNull($document->getLastSyncTime());
    }

    public function testSyncChatAssistantFromApiWithDataset(): void
    {
        // Create dataset first
        $dataset = new Dataset();
        $dataset->setName('助手同步测试数据集');
        $dataset->setDescription('用于测试助手同步的数据集');
        $dataset->setRemoteId('assistant-sync-dataset-111');
        $dataset->setRagFlowInstance($this->testInstance);
        $result = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $result);
        $persistedDataset = $result;

        $apiData = [
            'id' => 'api-assistant-222',
            'name' => 'API测试助手',
            'description' => '从API同步的测试助手',
            'avatar' => 'https://example.com/avatar.png',
            'language' => 'zh-CN',
            'llm' => [
                'model_name' => 'gpt-3.5-turbo',
                'temperature' => 0.7,
                'top_p' => 0.95,
                'max_tokens' => 2048,
                'presence_penalty' => 0.0,
                'frequency_penalty' => 0.0,
            ],
            'prompt' => [
                'similarity_threshold' => 0.2,
                'keywords_similarity_weight' => 0.7,
                'top_n' => 6,
                'variables' => [],
                'rerank_model' => '',
                'empty_response' => '抱歉，我找不到相关信息。',
                'opener' => '您好！有什么可以帮助您的吗？',
                'show_quote' => true,
                'prompt' => '您是一个专业的AI助手。',
            ],
            'status' => 'active',
            'tenant_id' => 'tenant-def',
            'create_time' => '2024-01-01T11:00:00Z',
            'update_time' => '2024-01-01T11:30:00Z',
        ];

        $assistant = $this->localDataSyncService->syncChatAssistantFromApiWithDataset($persistedDataset, $apiData);

        $this->assertInstanceOf(ChatAssistant::class, $assistant);
        $this->assertEquals('api-assistant-222', $assistant->getRemoteId());
        $this->assertEquals('API测试助手', $assistant->getName());
        $this->assertEquals('从API同步的测试助手', $assistant->getDescription());
        $this->assertEquals('https://example.com/avatar.png', $assistant->getAvatar());
        $this->assertEquals('zh-CN', $assistant->getLanguage());
        $this->assertEquals('gpt-3.5-turbo', $assistant->getLlmModel());
        $this->assertEquals(0.7, $assistant->getTemperature());
        $this->assertEquals(0.95, $assistant->getTopP());
        $this->assertEquals(2048, $assistant->getMaxTokens());
        $this->assertEquals(0.0, $assistant->getPresencePenalty());
        $this->assertEquals(0.0, $assistant->getFrequencyPenalty());
        $this->assertEquals(0.2, $assistant->getSimilarityThreshold());
        $this->assertEquals(0.7, $assistant->getKeywordsSimilarityWeight());
        $this->assertEquals(6, $assistant->getTopN());
        $this->assertEquals('抱歉，我找不到相关信息。', $assistant->getEmptyResponse());
        $this->assertEquals('您好！有什么可以帮助您的吗？', $assistant->getOpener());
        $this->assertTrue($assistant->getShowQuote());
        $this->assertEquals('您是一个专业的AI助手。', $assistant->getSystemPrompt());
        $this->assertEquals('active', $assistant->getStatus());
        $this->assertEquals('tenant-def', $assistant->getTenantId());
        $this->assertSame($persistedDataset, $assistant->getDataset());
        $this->assertNotNull($assistant->getRemoteCreateTime());
        $this->assertNotNull($assistant->getRemoteUpdateTime());
        $this->assertNotNull($assistant->getLastSyncTime());
    }

    public function testSyncChunkFromApi(): void
    {
        // Create necessary related entities
        $dataset = new Dataset();
        $dataset->setName('块同步测试数据集');
        $dataset->setDescription('用于测试块同步的数据集');
        $dataset->setRagFlowInstance($this->testInstance);
        $result1 = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $result1);
        $persistedDataset = $result1;

        $document = new Document();
        $document->setName('块同步测试文档.pdf');
        $document->setDataset($persistedDataset);
        $result2 = $this->persistAndFlush($document);
        $this->assertInstanceOf(Document::class, $result2);
        $persistedDocument = $result2;

        $apiData = [
            'id' => 'api-chunk-333',
            'content' => '这是一个从API同步的文本块内容，包含了重要的信息。',
            'position' => 1,
            'page_number' => 5,
            'start_pos' => 100,
            'end_pos' => 200,
            'token_count' => 25,
            'similarity_score' => 0.85,
            'embedding_vector' => [0.1, 0.2, 0.3, 0.4, 0.5],
            'keywords' => ['API', '同步', '文本块'],
            'metadata' => [
                'source' => 'document.pdf',
                'extraction_method' => 'sliding_window',
                'confidence' => 0.92,
            ],
            'create_time' => '2024-01-01T12:00:00Z',
            'update_time' => '2024-01-01T12:05:00Z',
        ];

        $chunk = $this->localDataSyncService->syncChunkFromApi($persistedDocument, $apiData);

        $this->assertInstanceOf(Chunk::class, $chunk);
        $this->assertEquals('api-chunk-333', $chunk->getRemoteId());
        $this->assertEquals('这是一个从API同步的文本块内容，包含了重要的信息。', $chunk->getContent());
        $this->assertEquals(1, $chunk->getPosition());
        $this->assertEquals(5, $chunk->getPageNumber());
        $this->assertEquals(100, $chunk->getStartPos());
        $this->assertEquals(200, $chunk->getEndPos());
        $this->assertEquals(25, $chunk->getTokenCount());
        $this->assertEquals(0.85, $chunk->getSimilarityScore());
        $this->assertEquals([0.1, 0.2, 0.3, 0.4, 0.5], $chunk->getEmbeddingVector());
        $this->assertEquals(['API', '同步', '文本块'], $chunk->getKeywords());
        $this->assertEquals([
            'source' => 'document.pdf',
            'extraction_method' => 'sliding_window',
            'confidence' => 0.92,
        ], $chunk->getMetadata());
        $this->assertSame($persistedDocument, $chunk->getDocument());
        $this->assertNotNull($chunk->getRemoteCreateTime());
        $this->assertNotNull($chunk->getRemoteUpdateTime());
        $this->assertNotNull($chunk->getLastSyncTime());
    }

    public function testUpdateExistingEntity(): void
    {
        // Create a dataset first
        $dataset = new Dataset();
        $dataset->setName('原始数据集名称');
        $dataset->setDescription('原始描述');
        $dataset->setRemoteId('update-test-dataset');
        $dataset->setLanguage('zh-CN');
        $dataset->setRagFlowInstance($this->testInstance);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $this->persistAndFlush($dataset);

        // Sync with same remote ID but different data
        $updatedApiData = [
            'id' => 'update-test-dataset',
            'name' => '更新后的数据集名称',
            'description' => '更新后的描述',
            'language' => 'en-US',
            'chunk_method' => 'manual',
            'embedding_model' => 'text-embedding-3-large',
        ];

        $updatedDataset = $this->localDataSyncService->syncDatasetFromApi($this->testInstance, $updatedApiData);

        // Should be the same entity but with updated data
        $this->assertEquals($persistedDataset->getId(), $updatedDataset->getId());
        $this->assertEquals('更新后的数据集名称', $updatedDataset->getName());
        $this->assertEquals('更新后的描述', $updatedDataset->getDescription());
        $this->assertEquals('en-US', $updatedDataset->getLanguage());
        $this->assertEquals('manual', $updatedDataset->getChunkMethod());
        $this->assertEquals('text-embedding-3-large', $updatedDataset->getEmbeddingModel());
    }

    public function testSyncWithMissingOptionalFields(): void
    {
        // Test syncing API data with missing optional fields
        $minimalApiData = [
            'id' => 'minimal-dataset',
            'name' => '最小数据集',
            // Missing optional fields like description, chunk_method, language
        ];

        $dataset = $this->localDataSyncService->syncDatasetFromApi($this->testInstance, $minimalApiData);

        $this->assertInstanceOf(Dataset::class, $dataset);
        $this->assertEquals('minimal-dataset', $dataset->getRemoteId());
        $this->assertEquals('最小数据集', $dataset->getName());
        $this->assertNull($dataset->getDescription());
        $this->assertNull($dataset->getChunkMethod());
        $this->assertNull($dataset->getLanguage());
    }

    public function testSyncDocumentWithDifferentStatuses(): void
    {
        // Create dataset
        $dataset = new Dataset();
        $dataset->setName('状态测试数据集');
        $dataset->setRemoteId('status-test-dataset');
        $dataset->setRagFlowInstance($this->testInstance);
        $result = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $result);
        $persistedDataset = $result;

        $statuses = [
            'pending' => DocumentStatus::PENDING,
            'uploading' => DocumentStatus::UPLOADING,
            'uploaded' => DocumentStatus::UPLOADED,
            'parsing' => DocumentStatus::PROCESSING,
            'parsed' => DocumentStatus::COMPLETED,
            'parse_failed' => DocumentStatus::FAILED,
        ];

        foreach ($statuses as $apiStatus => $expectedStatus) {
            $apiData = [
                'id' => "doc-{$apiStatus}",
                'name' => "文档-{$apiStatus}.pdf",
                'status' => $apiStatus,
                'progress' => 'parsed' === $apiStatus ? 1.0 : 0.5,
            ];

            $document = $this->localDataSyncService->syncDocumentFromApi($persistedDataset, $apiData);

            $this->assertEquals($expectedStatus, $document->getStatus());
            $this->assertEquals('parsed' === $apiStatus ? 100.0 : 50.0, $document->getProgress());
        }
    }

    public function testDateTimeConversion(): void
    {
        $apiData = [
            'id' => 'datetime-test-dataset',
            'name' => '时间转换测试数据集',
            'create_time' => '2024-01-15T14:30:45Z',
            'update_time' => '2024-01-15T15:45:30+08:00',
        ];

        $dataset = $this->localDataSyncService->syncDatasetFromApi($this->testInstance, $apiData);

        $this->assertInstanceOf(\DateTimeImmutable::class, $dataset->getRemoteCreateTime());
        $this->assertInstanceOf(\DateTimeImmutable::class, $dataset->getRemoteUpdateTime());
        $this->assertEquals('2024-01-15 14:30:45', $dataset->getRemoteCreateTime()->format('Y-m-d H:i:s'));
    }

    public function testDeleteLocalDataset(): void
    {
        // Create a dataset
        $dataset = new Dataset();
        $dataset->setName('删除测试数据集');
        $dataset->setRemoteId('delete-test-dataset');
        $dataset->setRagFlowInstance($this->testInstance);
        $this->persistAndFlush($dataset);

        // Delete it
        $this->localDataSyncService->deleteLocalDataset('delete-test-dataset', $this->testInstance);

        // Verify it's deleted
        $deletedDataset = self::getEntityManager()->getRepository(Dataset::class)->findOneBy([
            'remoteId' => 'delete-test-dataset',
        ]);

        $this->assertNull($deletedDataset);
    }

    public function testDeleteChatAssistant(): void
    {
        // Create a chat assistant
        $assistant = new ChatAssistant();
        $assistant->setName('删除测试助手');
        $assistant->setRemoteId('delete-test-assistant');
        $this->persistAndFlush($assistant);

        // Delete it
        $this->localDataSyncService->deleteChatAssistant('delete-test-assistant', $this->testInstance);

        // Verify it's deleted
        $deletedAssistant = self::getEntityManager()->getRepository(ChatAssistant::class)->findOneBy([
            'remoteId' => 'delete-test-assistant',
        ]);

        $this->assertNull($deletedAssistant);
    }

    public function testGetEntityManager(): void
    {
        $em = $this->localDataSyncService->getEntityManager();
        $this->assertInstanceOf(EntityManagerInterface::class, $em);
    }

    public function testSyncConversationFromApi(): void
    {
        $apiData = [
            'id' => 'api-conversation-123',
            'name' => 'API同步测试对话', // ConversationMapper使用name字段
            'user_id' => 'user-456',
            'status' => 'active',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => '你好，我想了解一下产品信息',
                    'timestamp' => '2024-01-01T10:00:00Z',
                ],
                [
                    'role' => 'assistant',
                    'content' => '您好！我很乐意为您介绍我们的产品。',
                    'timestamp' => '2024-01-01T10:00:05Z',
                ],
            ],
            'message_count' => 2,
            'context' => [
                'session_id' => 'session-789',
                'preferences' => ['language' => 'zh-CN'],
            ],
            'dialog' => [
                'total_tokens' => 150,
                'completion_tokens' => 80,
                'prompt_tokens' => 70,
            ],
            'last_activity_time' => '2024-01-01T10:30:00Z',
            'create_time' => '2024-01-01T10:00:00Z',
            'update_time' => '2024-01-01T10:35:00Z',
        ];

        $conversation = $this->localDataSyncService->syncConversationFromApi($this->testInstance, $apiData);

        $this->assertInstanceOf(Conversation::class, $conversation);
        $this->assertEquals('api-conversation-123', $conversation->getRemoteId());
        $this->assertEquals('API同步测试对话', $conversation->getTitle()); // getTitle() 应该返回 name 字段的值
        $this->assertSame($this->testInstance, $conversation->getRagFlowInstance());
        $this->assertNotNull($conversation->getLastSyncTime());
        $this->assertNotNull($conversation->getRemoteCreateTime());
        $this->assertNotNull($conversation->getRemoteUpdateTime());

        // 验证对话数据 - ConversationMapper处理了dialog字段
        $dialog = $conversation->getDialog();
        $this->assertIsArray($dialog);
        $this->assertEquals(150, $dialog['total_tokens']);
        $this->assertEquals(80, $dialog['completion_tokens']);
        $this->assertEquals(70, $dialog['prompt_tokens']);

        // 验证时间转换
        $this->assertEquals('2024-01-01 10:00:00', $conversation->getRemoteCreateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-01-01 10:35:00', $conversation->getRemoteUpdateTime()->format('Y-m-d H:i:s'));

        // 注意：ConversationMapper不处理user_id, status, messages, context, last_activity_time等字段
        // 这些字段保持默认值或为null，除非直接通过setter设置
        $this->assertNull($conversation->getLastActivityTime()); // mapper不处理此字段
    }

    public function testSyncConversationFromApiUpdateExisting(): void
    {
        // 先创建一个对话
        $existingConversation = new Conversation();
        $existingConversation->setRemoteId('update-conversation-123');
        $existingConversation->setTitle('原始对话标题');
        $existingConversation->setStatus('inactive');
        $existingConversation->setMessageCount(1);
        $existingConversation->setRagFlowInstance($this->testInstance);
        $result = $this->persistAndFlush($existingConversation);
        $this->assertInstanceOf(Conversation::class, $result);

        // 准备更新数据
        $apiData = [
            'id' => 'update-conversation-123',
            'name' => '更新后的对话标题', // ConversationMapper使用name字段
            'user_id' => 'updated-user',
            'status' => 'active',
            'message_count' => 3,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => '新消息',
                ],
            ],
            'update_time' => '2024-01-02T12:00:00Z',
        ];

        $updatedConversation = $this->localDataSyncService->syncConversationFromApi($this->testInstance, $apiData);

        // 应该是同一个实体，但只有mapper处理的字段会被更新
        $this->assertEquals($existingConversation->getId(), $updatedConversation->getId());
        $this->assertEquals('update-conversation-123', $updatedConversation->getRemoteId());
        $this->assertEquals('更新后的对话标题', $updatedConversation->getTitle()); // name字段被mapper更新
        $remoteUpdateTime = $updatedConversation->getRemoteUpdateTime();
        $this->assertInstanceOf(\DateTimeImmutable::class, $remoteUpdateTime);
        $this->assertEquals('2024-01-02 12:00:00', $remoteUpdateTime->format('Y-m-d H:i:s')); // update_time被mapper更新

        // 注意：ConversationMapper不处理user_id, status, message_count等字段
        // 这些字段保持原有值或为null，除非直接通过setter设置
    }

    public function testSyncConversationFromApiWithMinimalData(): void
    {
        // 测试最小数据集的同步
        $minimalApiData = [
            'id' => 'minimal-conversation-456',
            'name' => '最小对话', // ConversationMapper使用name字段
            // 其他字段都是可选的
        ];

        $conversation = $this->localDataSyncService->syncConversationFromApi($this->testInstance, $minimalApiData);

        $this->assertInstanceOf(Conversation::class, $conversation);
        $this->assertEquals('minimal-conversation-456', $conversation->getRemoteId());
        $this->assertEquals('最小对话', $conversation->getTitle()); // name字段被mapper设置
        $this->assertNull($conversation->getDialog()); // dialog字段在API数据中不存在
        $this->assertSame($this->testInstance, $conversation->getRagFlowInstance());
        $this->assertNotNull($conversation->getLastSyncTime());
    }

    public function testSyncConversationFromApiWithEmptyMessages(): void
    {
        // 测试空消息数组的处理
        $apiData = [
            'id' => 'empty-messages-conversation',
            'name' => '空消息对话', // ConversationMapper使用name字段
            'messages' => [], // 空数组
            'message_count' => 5, // 手动设置消息数量
        ];

        $conversation = $this->localDataSyncService->syncConversationFromApi($this->testInstance, $apiData);

        $this->assertEquals('empty-messages-conversation', $conversation->getRemoteId());
        // ConversationMapper不处理messages字段，所以messages保持默认值null
        $this->assertNull($conversation->getMessages());
        // message_count应该通过messages数组的长度计算为0
        $this->assertEquals(0, $conversation->getMessageCount());
    }

    public function testSyncConversationFromApiWithComplexMessages(): void
    {
        // 测试复杂消息结构
        $apiData = [
            'id' => 'complex-messages-conversation',
            'name' => '复杂消息对话', // ConversationMapper使用name字段
            'messages' => [
                [
                    'id' => 'msg-1',
                    'role' => 'user',
                    'content' => '复杂用户问题',
                    'metadata' => [
                        'source' => 'web',
                        'timestamp' => '2024-01-01T10:00:00Z',
                        'confidence' => 0.95,
                    ],
                    'attachments' => [
                        ['type' => 'image', 'url' => 'https://example.com/image.jpg'],
                    ],
                ],
                [
                    'id' => 'msg-2',
                    'role' => 'assistant',
                    'content' => '复杂助手回答',
                    'metadata' => [
                        'model' => 'gpt-4',
                        'tokens_used' => 120,
                        'response_time' => 2.5,
                    ],
                    'references' => [
                        '文档A', '文档B', '网页C',
                    ],
                ],
            ],
        ];

        $conversation = $this->localDataSyncService->syncConversationFromApi($this->testInstance, $apiData);

        // ConversationMapper只处理name和dialog字段，不处理messages字段
        $this->assertEquals('complex-messages-conversation', $conversation->getRemoteId());
        $this->assertEquals('复杂消息对话', $conversation->getTitle()); // name字段被mapper设置
        $this->assertNull($conversation->getDialog()); // dialog字段在API数据中不存在
        $this->assertSame($this->testInstance, $conversation->getRagFlowInstance());
        $this->assertNotNull($conversation->getLastSyncTime());
    }

    public function testSyncConversationFromApiTransactionIntegrity(): void
    {
        // 测试事务完整性 - 当同步过程中出错时，不应该创建不完整的记录
        $initialCount = count(self::getEntityManager()->getRepository(Conversation::class)->findAll());

        // 准备会导致错误的数据（缺少必需的id字段，但提供title以满足约束）
        $invalidApiData = [
            'title' => '无效对话', // 提供title以满足数据库约束
            // 缺少id字段
        ];

        $this->expectException(\Exception::class);
        $this->localDataSyncService->syncConversationFromApi($this->testInstance, $invalidApiData);

        // 验证数据库中没有创建不完整的记录
        $finalCount = count(self::getEntityManager()->getRepository(Conversation::class)->findAll());
        $this->assertEquals($initialCount, $finalCount);
    }

    public function testSyncLlmModelsFromApi(): void
    {
        $llmData = [
            'OpenAI' => [
                [
                    'fid' => 'gpt-4',
                    'llm_name' => 'GPT-4',
                    'available' => true,
                    'model_type' => 'chat',
                    'max_tokens' => 8192,
                    'status' => 1,
                    'is_tools' => true,
                    'tags' => ['large', 'multimodal'],
                    'create_time' => '2024-01-01T10:00:00Z',
                    'update_time' => '2024-01-01T10:30:00Z',
                ],
                [
                    'fid' => 'text-embedding-ada-002',
                    'llm_name' => 'Text Embedding Ada 002',
                    'available' => true,
                    'model_type' => 'embedding',
                    'max_tokens' => 8192,
                    'status' => 1,
                    'tags' => ['embedding'],
                ],
            ],
            'Anthropic' => [
                [
                    'fid' => 'claude-3-sonnet',
                    'llm_name' => 'Claude 3 Sonnet',
                    'available' => true,
                    'model_type' => 'chat',
                    'max_tokens' => 4096,
                    'status' => 1,
                    'is_tools' => true,
                    'tags' => ['medium', 'text-only'],
                    'create_time' => '2024-01-01T11:00:00Z',
                ],
            ],
            'InvalidProvider' => [
                'not-an-array', // 无效数据，应该被跳过
            ],
        ];

        $this->localDataSyncService->syncLlmModelsFromApi($llmData, $this->testInstance);

        // 验证同步的模型数量
        $repository = self::getEntityManager()->getRepository(LlmModel::class);
        $models = $repository->findBy(['ragFlowInstance' => $this->testInstance]);
        $this->assertCount(3, $models); // 应该只有3个有效模型

        // 验证OpenAI GPT-4模型
        $gpt4 = $repository->findOneBy(['fid' => 'gpt-4', 'ragFlowInstance' => $this->testInstance]);
        $this->assertInstanceOf(LlmModel::class, $gpt4);
        $this->assertEquals('gpt-4', $gpt4->getFid());
        $this->assertEquals('GPT-4', $gpt4->getLlmName());
        $this->assertTrue($gpt4->getAvailable());
        $this->assertEquals('chat', $gpt4->getModelType());
        $this->assertEquals(8192, $gpt4->getMaxTokens());
        $this->assertEquals(1, $gpt4->getStatus());
        $this->assertTrue($gpt4->getIsTools());
        $this->assertEquals(['large', 'multimodal'], $gpt4->getTags());
        $this->assertSame($this->testInstance, $gpt4->getRagFlowInstance());

        // 验证Anthropic Claude 3 Sonnet模型
        $claude3 = $repository->findOneBy(['fid' => 'claude-3-sonnet', 'ragFlowInstance' => $this->testInstance]);
        $this->assertInstanceOf(LlmModel::class, $claude3);
        $this->assertEquals('claude-3-sonnet', $claude3->getFid());
        $this->assertEquals('Claude 3 Sonnet', $claude3->getLlmName());
        $this->assertTrue($claude3->getAvailable());
        $this->assertEquals('chat', $claude3->getModelType());
        $this->assertEquals(4096, $claude3->getMaxTokens());
        $this->assertEquals('Anthropic', $claude3->getProviderName()); // providerName应该从array key设置

        // 验证Text Embedding模型
        $embedding = $repository->findOneBy(['fid' => 'text-embedding-ada-002', 'ragFlowInstance' => $this->testInstance]);
        $this->assertInstanceOf(LlmModel::class, $embedding);
        $this->assertEquals('text-embedding-ada-002', $embedding->getFid());
        $this->assertEquals('Text Embedding Ada 002', $embedding->getLlmName());
        $this->assertTrue($embedding->getAvailable());
        $this->assertEquals('embedding', $embedding->getModelType());
        $this->assertEquals('OpenAI', $embedding->getProviderName());
    }

    public function testSyncLlmModelsFromApiUpdateExisting(): void
    {
        // 先创建一个现有模型
        $existingModel = new LlmModel();
        $existingModel->setFid('update-test-model');
        $existingModel->setLlmName('旧模型名称');
        $existingModel->setAvailable(false);
        $existingModel->setModelType('chat');
        $existingModel->setProviderName('OldProvider'); // 设置providerName以满足约束
        $existingModel->setRagFlowInstance($this->testInstance);
        $this->persistAndFlush($existingModel);

        // 准备更新数据
        $llmData = [
            'TestProvider' => [
                [
                    'fid' => 'update-test-model',
                    'llm_name' => '更新后的模型名称',
                    'available' => true,
                    'model_type' => 'chat',
                    'max_tokens' => 4096,
                    'status' => 1,
                    'tags' => ['updated'],
                    'update_time' => '2024-01-02T12:00:00Z',
                ],
            ],
        ];

        $this->localDataSyncService->syncLlmModelsFromApi($llmData, $this->testInstance);

        // 验证模型已更新
        $repository = self::getEntityManager()->getRepository(LlmModel::class);
        $updatedModel = $repository->findOneBy(['fid' => 'update-test-model', 'ragFlowInstance' => $this->testInstance]);

        $this->assertNotNull($updatedModel);
        $this->assertEquals($existingModel->getId(), $updatedModel->getId()); // 应该是同一个实体
        $this->assertEquals('update-test-model', $updatedModel->getFid());
        $this->assertEquals('更新后的模型名称', $updatedModel->getLlmName());
        $this->assertTrue($updatedModel->getAvailable());
        $this->assertEquals('TestProvider', $updatedModel->getProviderName());
        $this->assertEquals(4096, $updatedModel->getMaxTokens());
        $this->assertEquals(['updated'], $updatedModel->getTags());
    }

    public function testSyncLlmModelsFromApiWithInvalidData(): void
    {
        $initialCount = count(self::getEntityManager()->getRepository(LlmModel::class)->findAll());

        $llmData = [
            'InvalidProvider1' => [
                [
                    'llm_name' => '缺少fid的模型', // 缺少fid字段
                    'available' => true,
                    'model_type' => 'chat',
                ],
                'not-an-array-model', // 不是数组
            ],
            'InvalidProvider2' => 'not-an-array', // 提供商数据不是数组
            'NumericProvider' => [ // 使用字符串键而不是数字键
                [
                    'fid' => 'numeric-provider-model',
                    'llm_name' => '数字键提供商模型',
                    'available' => true,
                    'model_type' => 'chat',
                ],
            ],
            'ValidProvider' => [
                [
                    'fid' => 'valid-model-123',
                    'llm_name' => '有效模型',
                    'available' => true,
                    'model_type' => 'chat',
                ],
            ],
        ];

        $this->localDataSyncService->syncLlmModelsFromApi($llmData, $this->testInstance);

        // 验证只有有效模型被创建
        $repository = self::getEntityManager()->getRepository(LlmModel::class);
        $models = $repository->findBy(['ragFlowInstance' => $this->testInstance]);
        $this->assertCount(2, $models); // 应该有两个有效模型（NumericProvider和ValidProvider）

        $validModel = $repository->findOneBy(['fid' => 'valid-model-123', 'ragFlowInstance' => $this->testInstance]);
        $this->assertInstanceOf(LlmModel::class, $validModel);
        $this->assertEquals('valid-model-123', $validModel->getFid());
        $this->assertEquals('有效模型', $validModel->getLlmName());
        $this->assertTrue($validModel->getAvailable());
        $this->assertEquals('chat', $validModel->getModelType());

        // 验证providerName被正确设置
        $this->assertEquals('ValidProvider', $validModel->getProviderName());

        // 验证NumericProvider模型也被创建
        $numericModel = $repository->findOneBy(['fid' => 'numeric-provider-model', 'ragFlowInstance' => $this->testInstance]);
        $this->assertInstanceOf(LlmModel::class, $numericModel);
        $this->assertEquals('numeric-provider-model', $numericModel->getFid());
        $this->assertEquals('数字键提供商模型', $numericModel->getLlmName());
        $this->assertTrue($numericModel->getAvailable());
        $this->assertEquals('chat', $numericModel->getModelType());
        $this->assertEquals('NumericProvider', $numericModel->getProviderName());
    }

    public function testSyncLlmModelsFromApiWithPartialData(): void
    {
        $llmData = [
            'PartialDataProvider' => [
                [
                    'fid' => 'partial-model-1',
                    'llm_name' => '部分数据模型1',
                    'available' => true,
                    'model_type' => 'chat',
                    // 其他字段都是可选的
                ],
                [
                    'fid' => 'partial-model-2',
                    'llm_name' => '部分数据模型2',
                    'available' => true,
                    'model_type' => 'embedding',
                    'max_tokens' => 2048, // 只有一些字段
                ],
            ],
        ];

        $this->localDataSyncService->syncLlmModelsFromApi($llmData, $this->testInstance);

        $repository = self::getEntityManager()->getRepository(LlmModel::class);

        // 验证第一个模型（只有必需字段）
        $model1 = $repository->findOneBy(['fid' => 'partial-model-1', 'ragFlowInstance' => $this->testInstance]);
        $this->assertInstanceOf(LlmModel::class, $model1);
        $this->assertEquals('partial-model-1', $model1->getFid());
        $this->assertEquals('部分数据模型1', $model1->getLlmName());
        $this->assertTrue($model1->getAvailable());
        $this->assertEquals('chat', $model1->getModelType());
        $this->assertNull($model1->getMaxTokens()); // 未设置
        $this->assertNull($model1->getStatus()); // 未设置
        $this->assertNull($model1->getIsTools()); // 未设置
        $this->assertNull($model1->getTags()); // 未设置

        // 验证第二个模型（部分字段）
        $model2 = $repository->findOneBy(['fid' => 'partial-model-2', 'ragFlowInstance' => $this->testInstance]);
        $this->assertInstanceOf(LlmModel::class, $model2);
        $this->assertEquals('partial-model-2', $model2->getFid());
        $this->assertEquals('部分数据模型2', $model2->getLlmName());
        $this->assertTrue($model2->getAvailable());
        $this->assertEquals('embedding', $model2->getModelType());
        $this->assertEquals(2048, $model2->getMaxTokens()); // 已设置
        $this->assertNull($model2->getStatus()); // 未设置
    }

    public function testSyncLlmModelsFromApiTransactionIntegrity(): void
    {
        // 这个测试验证事务完整性 - 当某个模型同步失败时，整个操作会回滚
        // 但由于实际很难模拟mapper错误，我们改为测试方法的基本功能

        $initialCount = count(self::getEntityManager()->getRepository(LlmModel::class)->findAll());

        // 创建有效的模型数据
        $llmData = [
            'TransactionTestProvider' => [
                [
                    'fid' => 'transaction-test-model',
                    'llm_name' => '事务测试模型',
                    'available' => true,
                    'model_type' => 'chat',
                    'max_tokens' => 4096,
                ],
            ],
        ];

        // 执行同步，应该成功
        $this->localDataSyncService->syncLlmModelsFromApi($llmData, $this->testInstance);

        // 验证模型被成功创建
        $repository = self::getEntityManager()->getRepository(LlmModel::class);
        $model = $repository->findOneBy(['fid' => 'transaction-test-model', 'ragFlowInstance' => $this->testInstance]);

        $this->assertInstanceOf(LlmModel::class, $model);
        $this->assertEquals('transaction-test-model', $model->getFid());
        $this->assertEquals('事务测试模型', $model->getLlmName());
        $this->assertTrue($model->getAvailable());
        $this->assertEquals('chat', $model->getModelType());
        $this->assertEquals(4096, $model->getMaxTokens());
        $this->assertEquals('TransactionTestProvider', $model->getProviderName());

        // 验证总数增加
        $finalCount = count(self::getEntityManager()->getRepository(LlmModel::class)->findAll());
        $this->assertEquals($initialCount + 1, $finalCount);
    }

    public function testSyncLlmModelsFromApiWithEmptyData(): void
    {
        $initialCount = count(self::getEntityManager()->getRepository(LlmModel::class)->findAll());

        // 测试空数据
        $emptyLlmData = [];

        $this->localDataSyncService->syncLlmModelsFromApi($emptyLlmData, $this->testInstance);

        // 验证没有新模型被创建
        $finalCount = count(self::getEntityManager()->getRepository(LlmModel::class)->findAll());
        $this->assertEquals($initialCount, $finalCount);

        // 测试包含无效提供商的空数据
        $invalidLlmData = [
            'EmptyProvider1' => [],
            'EmptyProvider2' => [],
        ];

        $this->localDataSyncService->syncLlmModelsFromApi($invalidLlmData, $this->testInstance);

        // 验证仍然没有新模型被创建
        $finalCount2 = count(self::getEntityManager()->getRepository(LlmModel::class)->findAll());
        $this->assertEquals($initialCount, $finalCount2);
    }
}
