<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Exception;

use RuntimeException;

class NotFoundException extends \RuntimeException
{
    public function __construct(string $message = 'Resource not found', int $code = 404, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
