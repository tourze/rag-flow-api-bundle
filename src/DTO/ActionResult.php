<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\DTO;

/**
 * 动作结果
 */
final class ActionResult
{
    private function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly string $type = 'info',
    ) {
    }

    public static function success(string $message): self
    {
        return new self(true, $message, 'success');
    }

    public static function error(string $message): self
    {
        return new self(false, $message, 'danger');
    }

    public static function info(string $message): self
    {
        return new self(true, $message, 'info');
    }
}
