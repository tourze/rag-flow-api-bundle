<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests;

use ArrayAccess;
use PHPUnit\Framework\Assert;

/**
 * 提供 PHPStan 友好的类型断言工具
 *
 * 用于在测试中收窄类型,消除 PHPStan level 9 的类型安全警告
 */
trait TypeAssertionTrait
{
    /**
     * 断言值为数组或实现了 ArrayAccess
     *
     * @phpstan-assert array|ArrayAccess $value
     */
    private static function assertArrayAccessible(mixed $value, string $message = ''): void
    {
        Assert::assertTrue(
            is_array($value) || $value instanceof ArrayAccess,
            $message ?: '值必须是数组或实现 ArrayAccess 接口'
        );
    }

    /**
     * 断言值为严格的数组类型
     *
     * @phpstan-assert array $value
     */
    private static function assertIsArrayStrict(mixed $value, string $message = ''): void
    {
        Assert::assertIsArray($value, $message);
    }

    /**
     * 断言字符串是有效的类名
     *
     * @phpstan-assert class-string $className
     */
    private static function assertIsClassName(mixed $className, string $message = ''): void
    {
        Assert::assertIsString($className);
        Assert::assertTrue(
            class_exists($className) || interface_exists($className) || trait_exists($className),
            $message ?: sprintf('类/接口/Trait "%s" 不存在', $className)
        );
    }

    /**
     * 断言字符串是指定基类/接口的子类
     *
     * @template T of object
     * @param class-string<T> $baseClass
     * @phpstan-assert class-string<T> $className
     */
    private static function assertIsSubclassOf(mixed $className, string $baseClass, string $message = ''): void
    {
        self::assertIsClassName($className);
        Assert::assertTrue(
            is_a($className, $baseClass, true),
            $message ?: sprintf('类 "%s" 不是 "%s" 的子类', $className, $baseClass)
        );
    }
}
