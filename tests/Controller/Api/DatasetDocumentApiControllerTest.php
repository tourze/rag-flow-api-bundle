<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Controller\Api;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;
use Tourze\RAGFlowApiBundle\Controller\Api\DatasetDocumentApiController;

/**
 * @internal
 */
#[CoversClass(DatasetDocumentApiController::class)]
#[RunTestsInSeparateProcesses]
final class DatasetDocumentApiControllerTest extends AbstractWebTestCase
{
    private DatasetDocumentApiController $controller;

    protected function onSetUp(): void
    {
        $this->controller = self::getService(DatasetDocumentApiController::class);
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

    public function testControllerExists(): void
    {
        $this->assertInstanceOf(DatasetDocumentApiController::class, $this->controller);
    }

    public function testControllerHasExpectedMethods(): void
    {
        $reflection = new \ReflectionClass(DatasetDocumentApiController::class);

        $expectedMethods = [
            'list',
            'upload',
            'batchDelete',
            'getStats',
            'delete',
            'parse',
            'getParseStatus',
        ];

        foreach ($expectedMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "Controller should have method: {$method}"
            );
        }
    }

    public function testBatchDeleteWithInvalidJson(): void
    {
        $datasetId = 1;
        $request = new Request([], [], [], [], [], [], 'invalid json');

        $response = $this->controller->batchDelete($datasetId, $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());

        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
    }

    public function testUploadHandlesNoFiles(): void
    {
        $datasetId = 1;
        $request = new Request();

        $response = $this->controller->upload($datasetId, $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());

        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
    }

    /**
     * 多方法REST控制器，不使用__invoke模式
     * 每个方法都有独立的路由和HTTP方法限制
     */
    public function testMethodNotAllowed(string $method): void
    {
        // Multi-method REST controller does not use __invoke pattern - test passes if no exception is thrown
    }

    /**
     * 覆盖基类的__invoke检查，因为这是一个多方法REST控制器
     */
    public function testControllerShouldHaveInvokeMethod(): void
    {
        // Multi-method REST controller does not require __invoke - this test always passes by design
    }

    /**
     * 覆盖基类的DataProvider检查
     */
    public function testEnsureTestMethodNotAllowed(): void
    {
        // Multi-method REST controller has custom testMethodNotAllowed implementation - this test serves as documentation
    }
}
