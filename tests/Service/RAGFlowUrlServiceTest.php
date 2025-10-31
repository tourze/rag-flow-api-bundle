<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Service\RAGFlowUrlService;

/**
 * @internal
 */
#[CoversClass(RAGFlowUrlService::class)]
#[RunTestsInSeparateProcesses]
class RAGFlowUrlServiceTest extends AbstractIntegrationTestCase
{
    private RAGFlowUrlService $urlService;

    protected function onSetUp(): void
    {
        $this->urlService = self::getService(RAGFlowUrlService::class);
    }

    public function testServiceCanBeInstantiated(): void
    {
        $this->assertInstanceOf(RAGFlowUrlService::class, $this->urlService);
    }

    public function testServiceIsRegisteredInContainer(): void
    {
        $this->assertTrue(self::getContainer()->has(RAGFlowUrlService::class));
    }

    public function testGenerateDocumentManagementPath(): void
    {
        $datasetId = 123;
        $expectedPath = "/admin/datasets/{$datasetId}/documents";

        $result = $this->urlService->generateDocumentManagementPath($datasetId);

        $this->assertEquals($expectedPath, $result);
    }

    public function testGenerateKnowledgeGraphPath(): void
    {
        $datasetId = 456;
        $expectedPath = "/admin/datasets/{$datasetId}/knowledge-graph";

        $result = $this->urlService->generateKnowledgeGraphPath($datasetId);

        $this->assertEquals($expectedPath, $result);
    }

    public function testGenerateDocumentUploadPath(): void
    {
        $datasetId = 789;
        $expectedPath = "/admin/datasets/{$datasetId}/documents/upload";

        $result = $this->urlService->generateDocumentUploadPath($datasetId);

        $this->assertEquals($expectedPath, $result);
    }

    public function testGenerateDatasetDetailPath(): void
    {
        $datasetId = 101;
        $expectedPath = "/admin/datasets/{$datasetId}";

        $result = $this->urlService->generateDatasetDetailPath($datasetId);

        $this->assertEquals($expectedPath, $result);
    }

    public function testGenerateDocumentManagementUrlWithRouteGenerator(): void
    {
        $datasetId = 123;
        $params = ['page' => 2];
        $expectedUrl = '/datasets/123/documents?page=2';

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->once())
            ->method('generate')
            ->with('dataset_documents_index', ['datasetId' => $datasetId, ...$params])
            ->willReturn($expectedUrl)
        ;

        $service = new RAGFlowUrlService($urlGenerator);

        $result = $service->generateDocumentManagementUrl($datasetId, $params);

        $this->assertEquals($expectedUrl, $result);
    }

    public function testGenerateDocumentManagementUrlWithoutRouteGenerator(): void
    {
        $datasetId = 123;
        $params = ['page' => 2];
        $expectedPath = "/admin/datasets/{$datasetId}/documents?page=2";

        $service = new RAGFlowUrlService(null);

        $result = $service->generateDocumentManagementUrl($datasetId, $params);

        $this->assertEquals($expectedPath, $result);
    }

    public function testGenerateDocumentManagementUrlWithRouteNotFoundException(): void
    {
        $datasetId = 123;
        $params = ['filter' => 'active'];

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->once())
            ->method('generate')
            ->willThrowException(new RouteNotFoundException())
        ;

        $service = new RAGFlowUrlService($urlGenerator);

        $result = $service->generateDocumentManagementUrl($datasetId, $params);

        $this->assertEquals("/admin/datasets/{$datasetId}/documents?filter=active", $result);
    }

    public function testGenerateKnowledgeGraphUrlWithRouteGenerator(): void
    {
        $datasetId = 456;
        $params = ['tab' => 'overview'];
        $expectedUrl = '/datasets/456/knowledge-graph?tab=overview';

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->once())
            ->method('generate')
            ->with('dataset_knowledge_graph', ['datasetId' => $datasetId, ...$params])
            ->willReturn($expectedUrl)
        ;

        $service = new RAGFlowUrlService($urlGenerator);

        $result = $service->generateKnowledgeGraphUrl($datasetId, $params);

        $this->assertEquals($expectedUrl, $result);
    }

    public function testGenerateKnowledgeGraphUrlWithoutRouteGenerator(): void
    {
        $datasetId = 456;
        $params = ['view' => 'graph'];
        $expectedPath = "/admin/datasets/{$datasetId}/knowledge-graph?view=graph";

        $service = new RAGFlowUrlService(null);

        $result = $service->generateKnowledgeGraphUrl($datasetId, $params);

        $this->assertEquals($expectedPath, $result);
    }

    public function testGenerateKnowledgeGraphUrlWithRouteNotFoundException(): void
    {
        $datasetId = 456;

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->once())
            ->method('generate')
            ->willThrowException(new RouteNotFoundException())
        ;

        $service = new RAGFlowUrlService($urlGenerator);

        $result = $service->generateKnowledgeGraphUrl($datasetId);

        $this->assertEquals("/admin/datasets/{$datasetId}/knowledge-graph", $result);
    }

    public function testGenerateDocumentUploadUrlWithRouteGenerator(): void
    {
        $datasetId = 789;
        $params = ['type' => 'pdf'];
        $expectedUrl = '/datasets/789/documents/upload?type=pdf';

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->once())
            ->method('generate')
            ->with('dataset_documents_upload', ['datasetId' => $datasetId, ...$params])
            ->willReturn($expectedUrl)
        ;

        $service = new RAGFlowUrlService($urlGenerator);

        $result = $service->generateDocumentUploadUrl($datasetId, $params);

        $this->assertEquals($expectedUrl, $result);
    }

    public function testGenerateDocumentUploadUrlWithoutRouteGenerator(): void
    {
        $datasetId = 789;
        $expectedPath = "/admin/datasets/{$datasetId}/documents/upload";

        $service = new RAGFlowUrlService(null);

        $result = $service->generateDocumentUploadUrl($datasetId);

        $this->assertEquals($expectedPath, $result);
    }

    public function testGenerateDocumentUploadUrlWithRouteNotFoundException(): void
    {
        $datasetId = 789;
        $params = ['batch' => 'true'];

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->once())
            ->method('generate')
            ->willThrowException(new RouteNotFoundException())
        ;

        $service = new RAGFlowUrlService($urlGenerator);

        $result = $service->generateDocumentUploadUrl($datasetId, $params);

        $this->assertEquals("/admin/datasets/{$datasetId}/documents/upload?batch=true", $result);
    }

    public function testHasRouteWithoutUrlGenerator(): void
    {
        $service = new RAGFlowUrlService(null);

        $result = $service->hasRoute('any_route');

        $this->assertFalse($result);
    }

    public function testHasRouteWithValidRoute(): void
    {
        $routeName = 'dataset_documents_index';
        $expectedUrl = '/datasets/123/documents';

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->once())
            ->method('generate')
            ->with($routeName)
            ->willReturn($expectedUrl)
        ;

        $service = new RAGFlowUrlService($urlGenerator);

        $result = $service->hasRoute($routeName);

        $this->assertTrue($result);
    }

    public function testHasRouteWithInvalidRoute(): void
    {
        $routeName = 'non_existent_route';

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->once())
            ->method('generate')
            ->with($routeName)
            ->willThrowException(new RouteNotFoundException())
        ;

        $service = new RAGFlowUrlService($urlGenerator);

        $result = $service->hasRoute($routeName);

        $this->assertFalse($result);
    }

    public function testGetRouteAvailabilityWithoutUrlGenerator(): void
    {
        $service = new RAGFlowUrlService(null);

        $result = $service->getRouteAvailability();

        $expected = [
            'dataset_documents_index' => false,
            'dataset_documents_upload' => false,
            'dataset_knowledge_graph' => false,
        ];

        $this->assertEquals($expected, $result);
    }

    public function testGetRouteAvailabilityWithMixedRouteStatus(): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        // 使用 returnCallback 来处理连续调用
        $callCount = 0;
        $urlGenerator->expects($this->exactly(3))
            ->method('generate')
            ->willReturnCallback(function ($routeName) use (&$callCount) {
                ++$callCount;
                switch ($callCount) {
                    case 1: // dataset_documents_index
                        return '/datasets/123/documents';
                    case 2: // dataset_documents_upload
                        return '/datasets/123/upload';
                    case 3: // dataset_knowledge_graph
                        throw new RouteNotFoundException();
                    default:
                        throw new RouteNotFoundException();
                }
            })
        ;

        $service = new RAGFlowUrlService($urlGenerator);

        $result = $service->getRouteAvailability();

        $expected = [
            'dataset_documents_index' => true,
            'dataset_documents_upload' => true,
            'dataset_knowledge_graph' => false,
        ];

        $this->assertEquals($expected, $result);
    }

    public function testRequiresRouteConfigurationWhenAllRoutesAvailable(): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->exactly(3))
            ->method('generate')
            ->willReturn('/some-url')
        ;

        $service = new RAGFlowUrlService($urlGenerator);

        $result = $service->requiresRouteConfiguration();

        $this->assertFalse($result);
    }

    public function testRequiresRouteConfigurationWhenNoRoutesAvailable(): void
    {
        $service = new RAGFlowUrlService(null);

        $result = $service->requiresRouteConfiguration();

        $this->assertTrue($result);
    }

    public function testRequiresRouteConfigurationWhenSomeRoutesAvailable(): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        // 使用 returnCallback 来处理连续调用
        $callCount = 0;
        $urlGenerator->expects($this->exactly(3))
            ->method('generate')
            ->willReturnCallback(function ($routeName) use (&$callCount) {
                ++$callCount;
                switch ($callCount) {
                    case 1: // dataset_documents_index
                        return '/datasets/123/documents';
                    case 2: // dataset_documents_upload
                        throw new RouteNotFoundException();
                    case 3: // dataset_knowledge_graph
                        throw new RouteNotFoundException();
                    default:
                        throw new RouteNotFoundException();
                }
            })
        ;

        $service = new RAGFlowUrlService($urlGenerator);

        $result = $service->requiresRouteConfiguration();

        $this->assertFalse($result); // 至少有一个路由可用就不需要配置
    }
}
