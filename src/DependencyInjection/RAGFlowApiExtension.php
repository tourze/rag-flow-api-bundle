<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

class RAGFlowApiExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }
}
