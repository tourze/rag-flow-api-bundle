<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Routing\RouteCollection;
use Tourze\RAGFlowApiBundle\Controller\Api\AgentController;
use Tourze\RAGFlowApiBundle\Controller\Api\ChatAssistantController;
use Tourze\RAGFlowApiBundle\Controller\Api\ChatController;
use Tourze\RAGFlowApiBundle\Controller\Api\ChunkController;
use Tourze\RAGFlowApiBundle\Controller\Api\ConversationController;
use Tourze\RAGFlowApiBundle\Controller\Api\DatasetController;
use Tourze\RAGFlowApiBundle\Controller\Api\DatasetDocumentApiController;
use Tourze\RAGFlowApiBundle\Controller\Api\DocumentController;
use Tourze\RAGFlowApiBundle\Controller\Api\KnowledgeGraphController;
use Tourze\RAGFlowApiBundle\Controller\Api\SystemController;
use Tourze\RoutingAutoLoaderBundle\Service\RoutingAutoLoaderInterface;

#[AutoconfigureTag(name: 'routing.loader')]
class AttributeControllerLoader extends Loader implements RoutingAutoLoaderInterface
{
    private AttributeRouteControllerLoader $controllerLoader;

    private RouteCollection $collection;

    public function __construct()
    {
        parent::__construct();
        $this->controllerLoader = new AttributeRouteControllerLoader();

        $this->collection = new RouteCollection();
        $this->collection->addCollection($this->controllerLoader->load(SystemController::class));
        $this->collection->addCollection($this->controllerLoader->load(DatasetController::class));
        $this->collection->addCollection($this->controllerLoader->load(DatasetDocumentApiController::class));
        $this->collection->addCollection($this->controllerLoader->load(DocumentController::class));
        $this->collection->addCollection($this->controllerLoader->load(ChunkController::class));
        $this->collection->addCollection($this->controllerLoader->load(ConversationController::class));
        $this->collection->addCollection($this->controllerLoader->load(ChatAssistantController::class));
        $this->collection->addCollection($this->controllerLoader->load(ChatController::class));
        $this->collection->addCollection($this->controllerLoader->load(KnowledgeGraphController::class));
        $this->collection->addCollection($this->controllerLoader->load(AgentController::class));
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        return $this->collection;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return false;
    }

    public function autoload(): RouteCollection
    {
        return $this->collection;
    }
}
