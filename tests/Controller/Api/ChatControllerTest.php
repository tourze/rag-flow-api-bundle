<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Controller\Api;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;
use Tourze\RAGFlowApiBundle\Controller\Api\ChatController;
use Tourze\RAGFlowApiBundle\Service\ConversationService;

/**
 * @internal
 */
#[CoversClass(ChatController::class)]
#[RunTestsInSeparateProcesses]
final class ChatControllerTest extends AbstractWebTestCase
{
    /** @var ConversationService&MockObject */
    private ConversationService $conversationService;

    private ChatController $controller;

    protected function onSetUp(): void
    {
        $this->conversationService = $this->createMock(ConversationService::class);
        self::getContainer()->set(ConversationService::class, $this->conversationService);
        $this->controller = self::getService(ChatController::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonResponse(JsonResponse $response): array
    {
        $content = $response->getContent();

        if (false === $content) {
            throw new \RuntimeException('Failed to get response content');
        }

        $decoded = json_decode($content, true);

        if (!is_array($decoded)) {
            throw new \RuntimeException('Failed to decode JSON response');
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createJsonRequest(array $data): Request
    {
        $jsonContent = json_encode($data);
        $this->assertIsString($jsonContent);

        return new Request([], [], [], [], [], [], $jsonContent);
    }

    public function testControllerExists(): void
    {
        $this->assertInstanceOf(ChatController::class, $this->controller);
    }

    public function testControllerHasExpectedMethods(): void
    {
        $reflection = new \ReflectionClass(ChatController::class);
        $expectedMethods = ['chatCompletion', 'openAIChatCompletion', 'sendMessage', 'getHistory'];
        foreach ($expectedMethods as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Controller should have method: {$method}");
        }
    }

    public function testChatCompletionWithValidData(): void
    {
        $chatId = 'chat-123';
        $requestData = ['messages' => [['role' => 'user', 'content' => 'Hello']], 'temperature' => 0.7];
        $request = $this->createJsonRequest($requestData);
        $expectedResult = ['choices' => [['message' => ['role' => 'assistant', 'content' => 'Hi there!']]]];
        $this->conversationService->expects($this->once())->method('chatCompletion')->with($chatId, $requestData['messages'], ['temperature' => 0.7])->willReturn($expectedResult);
        $response = $this->controller->chatCompletion($chatId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals($expectedResult, $responseData['data']);
    }

    public function testChatCompletionWithInvalidJson(): void
    {
        $chatId = 'chat-123';
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $response = $this->controller->chatCompletion($chatId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Invalid JSON data', $responseData['message']);
    }

    public function testChatCompletionWithMissingMessages(): void
    {
        $chatId = 'chat-123';
        $requestData = ['temperature' => 0.7];
        $request = $this->createJsonRequest($requestData);
        $response = $this->controller->chatCompletion($chatId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Messages array is required', $responseData['message']);
    }

    public function testOpenAIChatCompletionWithValidData(): void
    {
        $chatId = 'chat-123';
        $requestData = ['model' => 'gpt-3.5-turbo', 'messages' => [['role' => 'user', 'content' => 'Hello']], 'stream' => false, 'temperature' => 0.7];
        $request = $this->createJsonRequest($requestData);
        $expectedResult = ['choices' => [['message' => ['role' => 'assistant', 'content' => 'Hi there!']]]];
        $this->conversationService->expects($this->once())->method('openAIChatCompletion')->with($chatId, 'gpt-3.5-turbo', $requestData['messages'], false, ['temperature' => 0.7])->willReturn($expectedResult);
        $response = $this->controller->openAIChatCompletion($chatId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals($expectedResult, $responseData);
    }

    public function testOpenAIChatCompletionWithMissingModel(): void
    {
        $chatId = 'chat-123';
        $requestData = ['messages' => [['role' => 'user', 'content' => 'Hello']]];
        $request = $this->createJsonRequest($requestData);
        $response = $this->controller->openAIChatCompletion($chatId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertIsArray($responseData['error']);
        $this->assertEquals('Model is required', $responseData['error']['message']);
        $this->assertEquals('invalid_request_error', $responseData['error']['type']);
    }

    public function testSendMessageWithValidData(): void
    {
        $chatId = 'chat-123';
        $requestData = ['question' => 'What is AI?', 'stream' => false];
        $request = $this->createJsonRequest($requestData);
        $expectedResult = ['answer' => 'AI is artificial intelligence...'];
        $this->conversationService->expects($this->once())->method('sendMessage')->with($chatId, 'What is AI?', ['stream' => false])->willReturn($expectedResult);
        $response = $this->controller->sendMessage($chatId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals($expectedResult, $responseData['data']);
    }

    public function testSendMessageWithMissingQuestion(): void
    {
        $chatId = 'chat-123';
        $requestData = ['stream' => false];
        $request = $this->createJsonRequest($requestData);
        $response = $this->controller->sendMessage($chatId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Question is required', $responseData['message']);
    }

    public function testGetHistory(): void
    {
        $chatId = 'chat-123';
        $request = new Request(['page' => '1', 'limit' => '50', 'session_id' => 'session-456']);
        $expectedResult = ['messages' => [], 'total' => 0, 'page' => 1, 'limit' => 50];
        $this->conversationService->expects($this->once())->method('getHistory')->with($chatId, ['page' => 1, 'limit' => 50, 'session_id' => 'session-456'])->willReturn($expectedResult);
        $response = $this->controller->getHistory($chatId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals($expectedResult, $responseData['data']);
    }

    public function testChatCompletionHandlesException(): void
    {
        $chatId = 'chat-123';
        $requestData = ['messages' => [['role' => 'user', 'content' => 'Hello']]];
        $request = $this->createJsonRequest($requestData);
        $this->conversationService->expects($this->once())->method('chatCompletion')->willThrowException(new \Exception('Service error'));
        $response = $this->controller->chatCompletion($chatId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Chat completion failed', $responseData['message']);
        $this->assertEquals('Service error', $responseData['error']);
    }

    public function testOpenAIChatCompletionHandlesException(): void
    {
        $chatId = 'chat-123';
        $requestData = ['model' => 'gpt-3.5-turbo', 'messages' => [['role' => 'user', 'content' => 'Hello']]];
        $request = $this->createJsonRequest($requestData);
        $this->conversationService->expects($this->once())->method('openAIChatCompletion')->willThrowException(new \Exception('Service error'));
        $response = $this->controller->openAIChatCompletion($chatId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertIsArray($responseData['error']);
        $this->assertEquals('Service error', $responseData['error']['message']);
        $this->assertEquals('server_error', $responseData['error']['type']);
    }

    public function testSendMessageHandlesException(): void
    {
        $chatId = 'chat-123';
        $requestData = ['question' => 'What is AI?'];
        $request = $this->createJsonRequest($requestData);
        $this->conversationService->expects($this->once())->method('sendMessage')->willThrowException(new \Exception('Service error'));
        $response = $this->controller->sendMessage($chatId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to send message', $responseData['message']);
        $this->assertEquals('Service error', $responseData['error']);
    }

    public function testGetHistoryHandlesException(): void
    {
        $chatId = 'chat-123';
        $request = new Request();
        $this->conversationService->expects($this->once())->method('getHistory')->willThrowException(new \Exception('Service error'));
        $response = $this->controller->getHistory($chatId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to retrieve chat history', $responseData['message']);
        $this->assertEquals('Service error', $responseData['error']);
    }

    public function testMethodNotAllowed(string $method): void
    {
        // ChatController 是多方法控制器，不使用 __invoke，因此此测试不适用
        // 无意义的断言已移除
    }
}
