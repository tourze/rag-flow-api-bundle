<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Client\RAGFlowApiClient;
use Tourze\RAGFlowApiBundle\Entity\ChatAssistant;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Repository\DatasetRepository;
use Tourze\RAGFlowApiBundle\Request\ChatCompletionRequest;
use Tourze\RAGFlowApiBundle\Request\CreateChatSessionRequest;
use Tourze\RAGFlowApiBundle\Request\CreateConversationRequest;
use Tourze\RAGFlowApiBundle\Request\DeleteChatAssistantRequest;
use Tourze\RAGFlowApiBundle\Request\GetConversationHistoryRequest;
use Tourze\RAGFlowApiBundle\Request\ListChatAssistantsRequest;
use Tourze\RAGFlowApiBundle\Request\OpenAIChatCompletionRequest;
use Tourze\RAGFlowApiBundle\Request\SendMessageRequest;
use Tourze\RAGFlowApiBundle\Request\UpdateChatAssistantRequest;
use Tourze\RAGFlowApiBundle\Service\ConversationService;
use Tourze\RAGFlowApiBundle\Service\LocalDataSyncService;
use Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManagerInterface;

/**
 * @internal
 */
#[CoversClass(ConversationService::class)]
#[RunTestsInSeparateProcesses]
class ConversationServiceTest extends AbstractIntegrationTestCase
{
    /** @var RAGFlowApiClient&MockObject */
    private RAGFlowApiClient $client;

    /** @var RAGFlowInstanceManagerInterface&MockObject */
    private RAGFlowInstanceManagerInterface $instanceManager;

    /** @var LocalDataSyncService&MockObject */
    private LocalDataSyncService $localDataSyncService;

    /** @var DatasetRepository&MockObject */
    private DatasetRepository $datasetRepository;

    private ConversationService $conversationService;

    protected function onSetUp(): void
    {
        $this->client = $this->createMock(RAGFlowApiClient::class);
        $this->instanceManager = $this->createMock(RAGFlowInstanceManagerInterface::class);
        $this->localDataSyncService = $this->createMock(LocalDataSyncService::class);
        $this->datasetRepository = $this->createMock(DatasetRepository::class);
        $this->instanceManager->expects($this->once())->method('getDefaultClient')->willReturn($this->client);
        // Only set services if they haven't been initialized yet
        if (!self::getContainer()->has(RAGFlowInstanceManagerInterface::class)) {
            self::getContainer()->set(RAGFlowInstanceManagerInterface::class, $this->instanceManager);
        }
        if (!self::getContainer()->has(LocalDataSyncService::class)) {
            self::getContainer()->set(LocalDataSyncService::class, $this->localDataSyncService);
        }
        if (!self::getContainer()->has(DatasetRepository::class)) {
            self::getContainer()->set(DatasetRepository::class, $this->datasetRepository);
        }
        $this->conversationService = self::getService(ConversationService::class);
    }

    public function testSendMessage(): void
    {
        $chatId = 'chat-123';
        $question = 'Hello, how are you?';
        $options = ['stream' => false];
        $expectedResponse = ['message' => ['id' => 'msg-456', 'content' => 'I am fine, thank you!', 'role' => 'assistant']];
        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof SendMessageRequest;
        }))->willReturn($expectedResponse);
        $result = $this->conversationService->sendMessage($chatId, $question, $options);
        $this->assertEquals($expectedResponse, $result);
    }

    public function testSendMessageWithoutOptions(): void
    {
        $chatId = 'chat-123';
        $question = 'Hello, how are you?';
        $expectedResponse = ['message' => ['id' => 'msg-456', 'content' => 'I am fine, thank you!', 'role' => 'assistant']];
        $this->client->expects($this->once())->method('request')->with(self::isInstanceOf(SendMessageRequest::class))->willReturn($expectedResponse);
        $result = $this->conversationService->sendMessage($chatId, $question);
        $this->assertEquals($expectedResponse, $result);
    }

    public function testGetHistory(): void
    {
        $chatId = 'chat-123';
        $options = ['limit' => 10, 'offset' => 0];
        $expectedResponse = ['messages' => [['id' => 'msg-1', 'content' => 'Hello', 'role' => 'user'], ['id' => 'msg-2', 'content' => 'Hi there!', 'role' => 'assistant']], 'total' => 2];
        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof GetConversationHistoryRequest;
        }))->willReturn($expectedResponse);
        $result = $this->conversationService->getHistory($chatId, $options);
        $this->assertEquals($expectedResponse, $result);
    }

    public function testGetHistoryWithoutOptions(): void
    {
        $chatId = 'chat-123';
        $expectedResponse = ['messages' => [['id' => 'msg-1', 'content' => 'Hello', 'role' => 'user']], 'total' => 1];
        $this->client->expects($this->once())->method('request')->with(self::isInstanceOf(GetConversationHistoryRequest::class))->willReturn($expectedResponse);
        $result = $this->conversationService->getHistory($chatId);
        $this->assertEquals($expectedResponse, $result);
    }

    public function testChatCompletion(): void
    {
        $chatId = 'chat-123';
        $messages = [['role' => 'user', 'content' => 'What is the weather like?']];
        $options = ['temperature' => 0.5, 'max_tokens' => 100];
        $expectedResponse = ['message' => ['id' => 'msg-789', 'content' => 'The weather is sunny today.', 'role' => 'assistant']];
        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof ChatCompletionRequest;
        }))->willReturn($expectedResponse);
        $result = $this->conversationService->chatCompletion($chatId, $messages, $options);
        $this->assertEquals($expectedResponse, $result);
    }

    public function testChatCompletionWithoutOptions(): void
    {
        $chatId = 'chat-123';
        $messages = [['role' => 'user', 'content' => 'What is the weather like?']];
        $expectedResponse = ['message' => ['id' => 'msg-789', 'content' => 'The weather is sunny today.', 'role' => 'assistant']];
        $this->client->expects($this->once())->method('request')->with(self::isInstanceOf(ChatCompletionRequest::class))->willReturn($expectedResponse);
        $result = $this->conversationService->chatCompletion($chatId, $messages);
        $this->assertEquals($expectedResponse, $result);
    }

    public function testServiceWithException(): void
    {
        $this->client->expects($this->once())->method('request')->with(self::isInstanceOf(SendMessageRequest::class))->willThrowException(new \RuntimeException('API Error'));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('API Error');
        $this->conversationService->sendMessage('chat-123', 'Test message');
    }

    public function testCreateChatAssistant(): void
    {
        $name = 'Test Assistant';
        $datasetIds = ['dataset-1', 'dataset-2'];
        $options = ['temperature' => 0.5];
        $expectedResponse = ['id' => 'assistant-123', 'name' => 'Test Assistant', 'status' => 'ready'];
        $mockDataset = $this->createMock(Dataset::class);
        $mockChatAssistant = $this->createMock(ChatAssistant::class);
        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof CreateConversationRequest;
        }))->willReturn($expectedResponse);
        $this->datasetRepository->expects($this->once())->method('findOneBy')->willReturn($mockDataset);
        $this->localDataSyncService->expects($this->once())->method('syncChatAssistantFromApiWithDataset')->with($mockDataset, $expectedResponse)->willReturn($mockChatAssistant);
        $result = $this->conversationService->createChatAssistant($name, $datasetIds, $options);
        $this->assertSame($mockChatAssistant, $result);
    }

    public function testListChatAssistants(): void
    {
        $filters = ['status' => 'active'];
        $apiResponse = ['data' => [['id' => 'assistant-1', 'name' => 'Assistant 1', 'dataset_id' => 'dataset-1'], ['id' => 'assistant-2', 'name' => 'Assistant 2', 'dataset_id' => 'dataset-2']]];
        $mockDataset1 = $this->createMock(Dataset::class);
        $mockDataset2 = $this->createMock(Dataset::class);
        $mockAssistant1 = $this->createMock(ChatAssistant::class);
        $mockAssistant2 = $this->createMock(ChatAssistant::class);
        $mockInstance = $this->createMock(RAGFlowInstance::class);
        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof ListChatAssistantsRequest;
        }))->willReturn($apiResponse);
        $this->client->expects($this->exactly(4))->method('getInstance')->willReturn($mockInstance);
        $this->datasetRepository->expects($this->exactly(2))->method('findOneBy')->willReturnOnConsecutiveCalls($mockDataset1, $mockDataset2);
        $this->localDataSyncService->expects($this->exactly(2))->method('syncChatAssistantFromApi')->willReturnOnConsecutiveCalls($mockAssistant1, $mockAssistant2);
        $result = $this->conversationService->listChatAssistants($filters);
        $this->assertCount(2, $result);
        $this->assertSame($mockAssistant1, $result[0]);
        $this->assertSame($mockAssistant2, $result[1]);
    }

    public function testUpdateChatAssistant(): void
    {
        $chatId = 'chat-123';
        $config = ['temperature' => 0.7, 'max_tokens' => 500];
        $expectedResponse = ['id' => 'chat-123', 'config' => $config, 'status' => 'updated'];
        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof UpdateChatAssistantRequest;
        }))->willReturn($expectedResponse);
        $result = $this->conversationService->updateChatAssistant($chatId, $config);
        $this->assertEquals($expectedResponse, $result);
    }

    public function testDeleteChatAssistant(): void
    {
        $chatId = 'chat-123';
        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof DeleteChatAssistantRequest;
        }))->willReturn(['success' => true]);
        $result = $this->conversationService->deleteChatAssistant($chatId);
        $this->assertTrue($result);
    }

    public function testCreateSession(): void
    {
        $chatId = 'chat-123';
        $options = ['session_name' => 'Test Session'];
        $expectedResponse = ['session_id' => 'session-456', 'chat_id' => 'chat-123', 'status' => 'created'];
        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof CreateChatSessionRequest;
        }))->willReturn($expectedResponse);
        $result = $this->conversationService->createSession($chatId, $options);
        $this->assertEquals($expectedResponse, $result);
    }

    public function testOpenAIChatCompletion(): void
    {
        $chatId = 'chat-123';
        $model = 'gpt-4';
        $messages = [['role' => 'user', 'content' => 'Hello, how are you?']];
        $stream = false;
        $options = ['temperature' => 0.8];
        $expectedResponse = ['choices' => [['message' => ['role' => 'assistant', 'content' => 'I am doing well, thank you!']]]];
        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof OpenAIChatCompletionRequest;
        }))->willReturn($expectedResponse);
        $result = $this->conversationService->openAIChatCompletion($chatId, $model, $messages, $stream, $options);
        $this->assertEquals($expectedResponse, $result);
    }

    public function testOpenAIChatCompletionWithoutOptions(): void
    {
        $chatId = 'chat-123';
        $model = 'gpt-4';
        $messages = [['role' => 'user', 'content' => 'Hello!']];
        $expectedResponse = ['choices' => [['message' => ['role' => 'assistant', 'content' => 'Hello there!']]]];
        $this->client->expects($this->once())->method('request')->with(self::isInstanceOf(OpenAIChatCompletionRequest::class))->willReturn($expectedResponse);
        $result = $this->conversationService->openAIChatCompletion($chatId, $model, $messages);
        $this->assertEquals($expectedResponse, $result);
    }
}
