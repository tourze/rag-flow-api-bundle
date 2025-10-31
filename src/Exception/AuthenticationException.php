<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Exception;

use InvalidArgumentException;

class AuthenticationException extends \InvalidArgumentException
{
    public function __construct(string $message = 'Authentication failed', int $code = 401, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
