<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\PHPUnitSymfonyKernelTest\DoctrineTrait;
use Tourze\PHPUnitSymfonyKernelTest\ServiceLocatorTrait;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Exception\ApiKeyDecryptionException;

/**
 * @internal
 */
#[CoversClass(RAGFlowInstance::class)]
class RAGFlowInstanceTest extends AbstractEntityTestCase
{
    use DoctrineTrait;
    use ServiceLocatorTrait;

    protected function setUp(): void
    {
        parent::setUp();

        // 测试初始化逻辑
    }

    protected function createEntity(): RAGFlowInstance
    {
        $instance = new RAGFlowInstance();
        $instance->setName('test-instance');
        $instance->setApiUrl('http://ragflow-test.mixpwr.com/');
        $instance->setApiKey('test_key');
        $instance->setEnabled(true);

        return $instance;
    }

    public function testCreateInstance(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('test-instance');
        $instance->setApiUrl('http://ragflow-test.mixpwr.com/');
        $instance->setApiKey('test_key');
        $instance->setEnabled(true);

        $this->assertEquals('test-instance', $instance->getName());
        $this->assertEquals('http://ragflow-test.mixpwr.com/', $instance->getApiUrl());
        $this->assertTrue($instance->isEnabled());
        $this->assertInstanceOf(\DateTimeImmutable::class, $instance->getCreateTime());
    }

    public function testApiKeyEncryption(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('test-instance');
        $plainKey = 'test_api_key';

        $encryptedKey = $instance->encryptApiKey($plainKey);
        $this->assertNotEquals($plainKey, $encryptedKey);

        $decryptedKey = $instance->decryptApiKey($encryptedKey);
        $this->assertEquals($plainKey, $decryptedKey);
    }

    public function testApiKeyDecryptionFailure(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('test-instance');

        // 创建一个无效的加密密钥（格式正确但解密会失败）
        $invalidKey = base64_encode('invalid_data');

        $this->expectException(ApiKeyDecryptionException::class);
        $instance->decryptApiKey($invalidKey);
    }

    public function testToString(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('test-instance-name');

        $this->assertEquals('test-instance-name', (string) $instance);
    }

    public function testGetIdInitiallyNull(): void
    {
        $instance = new RAGFlowInstance();
        $this->assertNull($instance->getId());
    }

    public function testCreateTimeSetInConstructor(): void
    {
        $beforeCreation = new \DateTimeImmutable();
        $instance = new RAGFlowInstance();
        $afterCreation = new \DateTimeImmutable();

        $createTime = $instance->getCreateTime();
        $this->assertInstanceOf(\DateTimeImmutable::class, $createTime);
        $this->assertGreaterThanOrEqual($beforeCreation, $createTime);
        $this->assertLessThanOrEqual($afterCreation, $createTime);
    }

    public function testSetAndGetCreateTime(): void
    {
        $instance = new RAGFlowInstance();
        $customTime = new \DateTimeImmutable('2024-01-01 12:00:00');

        $instance->setCreateTime($customTime);
        $this->assertEquals($customTime, $instance->getCreateTime());
    }

    public function testLastHealthCheckInitiallyNull(): void
    {
        $instance = new RAGFlowInstance();
        $this->assertNull($instance->getLastHealthCheck());
    }

    public function testSetAndGetLastHealthCheck(): void
    {
        $instance = new RAGFlowInstance();
        $healthCheckTime = new \DateTimeImmutable('2024-01-01 15:30:00');

        $instance->setLastHealthCheck($healthCheckTime);
        $this->assertEquals($healthCheckTime, $instance->getLastHealthCheck());

        // 测试设置为null
        $instance->setLastHealthCheck(null);
        $this->assertNull($instance->getLastHealthCheck());
    }

    public function testHealthyDefaultTrue(): void
    {
        $instance = new RAGFlowInstance();
        $this->assertTrue($instance->isHealthy());
    }

    public function testSetAndGetHealthy(): void
    {
        $instance = new RAGFlowInstance();

        $instance->setHealthy(false);
        $this->assertFalse($instance->isHealthy());

        $instance->setHealthy(true);
        $this->assertTrue($instance->isHealthy());
    }

    public function testDefaultValues(): void
    {
        $instance = new RAGFlowInstance();

        $this->assertEquals(30, $instance->getTimeout());
        $this->assertTrue($instance->isEnabled());
        $this->assertTrue($instance->isHealthy());
        $this->assertNull($instance->getDescription());
        $this->assertNull($instance->getLastHealthCheck());
    }

    public function testApiKeyDecryptionWithInvalidBase64(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('test-instance');

        // 使用一个实际无效的base64字符串 - 包含无效字符且无法解码
        $this->expectException(ApiKeyDecryptionException::class);
        $this->expectExceptionMessage('Invalid base64 encoding');
        $instance->decryptApiKey('invalid-base64!@#$%^&*()');
    }

    public function testApiKeyDecryptionWithShortData(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('test-instance');

        // 创建一个太短的数据（少于nonce长度）
        $shortData = base64_encode('short');

        $this->expectException(ApiKeyDecryptionException::class);
        $this->expectExceptionMessage('Invalid encrypted data length');
        $instance->decryptApiKey($shortData);
    }

    public function testApiKeyDecryptionWithEmptyString(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('test-instance');

        $this->expectException(ApiKeyDecryptionException::class);
        $this->expectExceptionMessage('Invalid encrypted data length');
        $instance->decryptApiKey('');
    }

    public function testEncryptionDecryptionConsistency(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('test-instance');

        $plainKey = 'test_api_key_123';
        $encrypted = $instance->encryptApiKey($plainKey);
        $decrypted = $instance->decryptApiKey($encrypted);

        $this->assertEquals($plainKey, $decrypted);
        $this->assertNotEquals($plainKey, $encrypted);
    }

    public function testEncryptionProducesDifferentResults(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('test-instance');

        $plainKey = 'test_api_key';
        $encrypted1 = $instance->encryptApiKey($plainKey);
        $encrypted2 = $instance->encryptApiKey($plainKey);

        // 由于使用了随机nonce，相同明文应该产生不同的密文
        $this->assertNotEquals($encrypted1, $encrypted2);

        // 但都应该能正确解密
        $this->assertEquals($plainKey, $instance->decryptApiKey($encrypted1));
        $this->assertEquals($plainKey, $instance->decryptApiKey($encrypted2));
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'name' => ['name', 'test-instance'];
        yield 'apiUrl' => ['apiUrl', 'http://ragflow-test.mixpwr.com/'];
        yield 'apiKey' => ['apiKey', 'test_key'];
        yield 'description' => ['description', 'Test description'];
        yield 'enabled' => ['enabled', true];
        yield 'timeout' => ['timeout', 30];
        yield 'healthy' => ['healthy', true];
    }
}
