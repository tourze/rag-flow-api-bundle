<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Client\RAGFlowApiClient;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Service\RAGFlowApiClientFactory;

/**
 * @internal
 */
#[CoversClass(RAGFlowApiClientFactory::class)]
#[RunTestsInSeparateProcesses]
class RAGFlowApiClientFactoryTest extends AbstractIntegrationTestCase
{
    private RAGFlowApiClientFactory $factory;

    protected function onSetUp(): void
    {
        $this->factory = self::getService('Tourze\RAGFlowApiBundle\Service\RAGFlowApiClientFactory');
    }

    public function testCreateClient(): void
    {
        $uniqueSuffix = uniqid('', true);
        $instance = new RAGFlowInstance();
        $instance->setName('test-instance-' . $uniqueSuffix);
        $instance->setApiUrl('http://ragflow-test.mixpwr.com/');
        $instance->setApiKey('test_key-' . uniqid('', true));
        $instance->setEnabled(true);

        $client = $this->factory->createClient($instance);

        $this->assertInstanceOf(RAGFlowApiClient::class, $client);
        $this->assertEquals('test-instance-' . $uniqueSuffix, $client->getInstance()->getName());
        $this->assertEquals('http://ragflow-test.mixpwr.com/', $client->getInstance()->getApiUrl());
    }
}
