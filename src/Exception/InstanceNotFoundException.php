<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Exception;

class InstanceNotFoundException extends \InvalidArgumentException
{
    public function __construct(string $instanceName)
    {
        parent::__construct(sprintf('Instance "%s" not found', $instanceName));
    }
}
