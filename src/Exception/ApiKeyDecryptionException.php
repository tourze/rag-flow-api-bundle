<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Exception;

use InvalidArgumentException;

class ApiKeyDecryptionException extends \InvalidArgumentException
{
    public function __construct(string $message = 'Failed to decrypt API key', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
