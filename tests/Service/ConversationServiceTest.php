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
 * ConversationService 集成测试
 *
 * @internal
 */
#[CoversClass(ConversationService::class)]
#[RunTestsInSeparateProcesses]
class ConversationServiceTest extends AbstractIntegrationTestCase
{
    /** @var RAGFlowApiClient&MockObject */
    private RAGFlowApiClient $client;

    private RAGFlowInstanceManagerInterface $instanceManager;

    private LocalDataSyncService $localDataSyncService;

    private ConversationService $conversationService;

    private RAGFlowInstance $ragFlowInstance;

    protected function onSetUp(): void
    {
        // 创建真实的 RAGFlowInstance 并持久化
        $this->ragFlowInstance = new RAGFlowInstance();
        $this->ragFlowInstance->setName('Test Instance-' . uniqid('', true));
        $this->ragFlowInstance->setApiUrl('https://test.ragflow.io/api');
        $this->ragFlowInstance->setApiKey('test-api-key-' . uniqid('', true));
        $this->ragFlowInstance->setIsDefault(true);

        $em = self::getEntityManager();
        $em->persist($this->ragFlowInstance);
        $em->flush();

        // Mock RAGFlowApiClient (网络请求客户端)
        $this->client = $this->createMock(RAGFlowApiClient::class);
        // 让 Mock 客户端返回我们创建的 RAGFlowInstance
        $this->client->method('getInstance')->willReturn($this->ragFlowInstance);

        // Mock RAGFlowInstanceManagerInterface
        $this->instanceManager = $this->createMock(RAGFlowInstanceManagerInterface::class);
        $this->instanceManager->method('getDefaultClient')->willReturn($this->client);

        // 将 Mock 服务注入到容器中
        self::getContainer()->set(RAGFlowInstanceManagerInterface::class, $this->instanceManager);

        // 从服务容器获取 ConversationService
        $this->conversationService = self::getService(ConversationService::class);
        $this->localDataSyncService = self::getService(LocalDataSyncService::class);
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
        // 创建真实的 Dataset 实体
        $dataset1 = new Dataset();
        $dataset1->setName('Test Dataset 1');
        $dataset1->setRagFlowInstance($this->ragFlowInstance);
        $dataset1->setRemoteId('dataset-1');

        $dataset2 = new Dataset();
        $dataset2->setName('Test Dataset 2');
        $dataset2->setRagFlowInstance($this->ragFlowInstance);
        $dataset2->setRemoteId('dataset-2');

        $em = self::getEntityManager();
        $em->persist($dataset1);
        $em->persist($dataset2);
        $em->flush();

        $name = 'Test Assistant';
        $datasetIds = ['dataset-1', 'dataset-2'];
        $options = ['temperature' => 0.5];
        $expectedResponse = ['id' => 'assistant-123', 'name' => 'Test Assistant', 'status' => 'ready'];

        $this->client->expects($this->once())->method('request')->with(self::callback(function ($request) {
            return $request instanceof CreateConversationRequest;
        }))->willReturn($expectedResponse);

        $result = $this->conversationService->createChatAssistant($name, $datasetIds, $options);

        $this->assertInstanceOf(ChatAssistant::class, $result);
        $this->assertSame('Test Assistant', $result->getName());

        // 验证已持久化
        $this->assertTrue($em->contains($result));
    }

    public function testListChatAssistants(): void
    {
        // 创建真实的 Dataset 实体
        $dataset1 = new Dataset();
        $dataset1->setName('Dataset 1');
        $dataset1->setRagFlowInstance($this->ragFlowInstance);
        $dataset1->setRemoteId('dataset-1');

        $dataset2 = new Dataset();
        $dataset2->setName('Dataset 2');
        $dataset2->setRagFlowInstance($this->ragFlowInstance);
        $dataset2->setRemoteId('dataset-2');

        $em = self::getEntityManager();
        $em->persist($dataset1);
        $em->persist($dataset2);
        $em->flush();

        $filters = ['status' => 'active'];
        $apiResponse = ['data' => [['id' => 'assistant-1', 'name' => 'Assistant 1', 'dataset_id' => 'dataset-1'], ['id' => 'assistant-2', 'name' => 'Assistant 2', 'dataset_id' => 'dataset-2']]];

        $this->client->method('request')->with(self::callback(function ($request) {
            return $request instanceof ListChatAssistantsRequest;
        }))->willReturn($apiResponse);

        $result = $this->conversationService->listChatAssistants($filters);

        $this->assertCount(2, $result);
        $this->assertInstanceOf(ChatAssistant::class, $result[0]);
        $this->assertInstanceOf(ChatAssistant::class, $result[1]);
        $this->assertSame('Assistant 1', $result[0]->getName());
        $this->assertSame('Assistant 2', $result[1]->getName());

        // 验证已持久化
        $this->assertTrue($em->contains($result[0]));
        $this->assertTrue($em->contains($result[1]));
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
