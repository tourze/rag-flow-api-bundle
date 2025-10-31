<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Client;

use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Service\ChunkService;
use Tourze\RAGFlowApiBundle\Service\ConversationService;
use Tourze\RAGFlowApiBundle\Service\DatasetService;
use Tourze\RAGFlowApiBundle\Service\DocumentService;

interface RAGFlowApiClientInterface
{
    public function datasets(): DatasetService;

    public function documents(): DocumentService;

    public function chunks(): ChunkService;

    public function conversations(): ConversationService;

    public function getInstance(): RAGFlowInstance;
}
