<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Routing\RouteCollection;
use Tourze\RAGFlowApiBundle\Controller\Api\Agent\BatchSyncController as AgentBatchSyncController;
use Tourze\RAGFlowApiBundle\Controller\Api\Agent\CreateController as AgentCreateController;
use Tourze\RAGFlowApiBundle\Controller\Api\Agent\DeleteController as AgentDeleteController;
use Tourze\RAGFlowApiBundle\Controller\Api\Agent\DetailController as AgentDetailController;
use Tourze\RAGFlowApiBundle\Controller\Api\Agent\ListController as AgentListController;
use Tourze\RAGFlowApiBundle\Controller\Api\Agent\StatsController as AgentStatsController;
use Tourze\RAGFlowApiBundle\Controller\Api\Agent\SyncController as AgentSyncController;
use Tourze\RAGFlowApiBundle\Controller\Api\Agent\UpdateController as AgentUpdateController;
use Tourze\RAGFlowApiBundle\Controller\Api\Chat\ChatCompletionController;
use Tourze\RAGFlowApiBundle\Controller\Api\Chat\GetHistoryController as ChatGetHistoryController;
use Tourze\RAGFlowApiBundle\Controller\Api\Chat\OpenAIChatCompletionController;
use Tourze\RAGFlowApiBundle\Controller\Api\Chat\SendMessageController as ChatSendMessageController;
use Tourze\RAGFlowApiBundle\Controller\Api\ChatAssistant\CreateController as ChatAssistantCreateController;
use Tourze\RAGFlowApiBundle\Controller\Api\ChatAssistant\CreateSessionController as ChatAssistantCreateSessionController;
use Tourze\RAGFlowApiBundle\Controller\Api\ChatAssistant\DeleteController as ChatAssistantDeleteController;
use Tourze\RAGFlowApiBundle\Controller\Api\ChatAssistant\ListController as ChatAssistantListController;
use Tourze\RAGFlowApiBundle\Controller\Api\ChatAssistant\UpdateController as ChatAssistantUpdateController;
use Tourze\RAGFlowApiBundle\Controller\Api\Chunk\AddController as ChunkAddController;
use Tourze\RAGFlowApiBundle\Controller\Api\Chunk\DeleteController as ChunkDeleteController;
use Tourze\RAGFlowApiBundle\Controller\Api\Chunk\RetrieveController as ChunkRetrieveController;
use Tourze\RAGFlowApiBundle\Controller\Api\Chunk\UpdateController as ChunkUpdateController;
use Tourze\RAGFlowApiBundle\Controller\Api\Conversation\CreateController as ConversationCreateController;
use Tourze\RAGFlowApiBundle\Controller\Api\Conversation\CreateSessionController as ConversationCreateSessionController;
use Tourze\RAGFlowApiBundle\Controller\Api\Conversation\DeleteController as ConversationDeleteController;
use Tourze\RAGFlowApiBundle\Controller\Api\Conversation\GetHistoryController as ConversationGetHistoryController;
use Tourze\RAGFlowApiBundle\Controller\Api\Conversation\ListController as ConversationListController;
use Tourze\RAGFlowApiBundle\Controller\Api\Conversation\SendMessageController as ConversationSendMessageController;
use Tourze\RAGFlowApiBundle\Controller\Api\Conversation\UpdateController as ConversationUpdateController;
use Tourze\RAGFlowApiBundle\Controller\Api\Dataset\CreateController as DatasetCreateController;
use Tourze\RAGFlowApiBundle\Controller\Api\Dataset\DeleteController as DatasetDeleteController;
use Tourze\RAGFlowApiBundle\Controller\Api\Dataset\GetKnowledgeGraphController;
use Tourze\RAGFlowApiBundle\Controller\Api\Dataset\ListController as DatasetListController;
use Tourze\RAGFlowApiBundle\Controller\Api\Dataset\UpdateController as DatasetUpdateController;
use Tourze\RAGFlowApiBundle\Controller\Api\DatasetDocument\BatchDeleteController as DatasetDocumentBatchDeleteController;
use Tourze\RAGFlowApiBundle\Controller\Api\DatasetDocument\DeleteController as DatasetDocumentDeleteController;
use Tourze\RAGFlowApiBundle\Controller\Api\DatasetDocument\GetParseStatusController as DatasetDocumentGetParseStatusController;
use Tourze\RAGFlowApiBundle\Controller\Api\DatasetDocument\GetStatsController as DatasetDocumentGetStatsController;
use Tourze\RAGFlowApiBundle\Controller\Api\DatasetDocument\ListController as DatasetDocumentListController;
use Tourze\RAGFlowApiBundle\Controller\Api\DatasetDocument\ParseController as DatasetDocumentParseController;
use Tourze\RAGFlowApiBundle\Controller\Api\DatasetDocument\UploadController as DatasetDocumentUploadController;
use Tourze\RAGFlowApiBundle\Controller\Api\Document\DeleteController as DocumentDeleteController;
use Tourze\RAGFlowApiBundle\Controller\Api\Document\DetailController as DocumentDetailController;
use Tourze\RAGFlowApiBundle\Controller\Api\Document\GetParseStatusController as DocumentGetParseStatusController;
use Tourze\RAGFlowApiBundle\Controller\Api\Document\ListController as DocumentListController;
use Tourze\RAGFlowApiBundle\Controller\Api\Document\ParseController as DocumentParseController;
use Tourze\RAGFlowApiBundle\Controller\Api\Document\RetryUploadController as DocumentRetryUploadController;
use Tourze\RAGFlowApiBundle\Controller\Api\Document\UpdateController as DocumentUpdateController;
use Tourze\RAGFlowApiBundle\Controller\Api\Document\UploadController as DocumentUploadController;
use Tourze\RAGFlowApiBundle\Controller\Api\KnowledgeGraph\GetByDatasetController as KnowledgeGraphGetByDatasetController;
use Tourze\RAGFlowApiBundle\Controller\Api\KnowledgeGraph\GetEntityRelationsController;
use Tourze\RAGFlowApiBundle\Controller\Api\KnowledgeGraph\GetStatsController as KnowledgeGraphGetStatsController;
use Tourze\RAGFlowApiBundle\Controller\Api\KnowledgeGraph\SearchEntitiesController;
use Tourze\RAGFlowApiBundle\Controller\Api\System\HealthCheckController;
use Tourze\RAGFlowApiBundle\Controller\Api\System\StatusController;
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

        // System controllers
        $this->collection->addCollection($this->controllerLoader->load(HealthCheckController::class));
        $this->collection->addCollection($this->controllerLoader->load(StatusController::class));

        // Agent controllers
        $this->collection->addCollection($this->controllerLoader->load(AgentListController::class));
        $this->collection->addCollection($this->controllerLoader->load(AgentDetailController::class));
        $this->collection->addCollection($this->controllerLoader->load(AgentCreateController::class));
        $this->collection->addCollection($this->controllerLoader->load(AgentUpdateController::class));
        $this->collection->addCollection($this->controllerLoader->load(AgentDeleteController::class));
        $this->collection->addCollection($this->controllerLoader->load(AgentSyncController::class));
        $this->collection->addCollection($this->controllerLoader->load(AgentBatchSyncController::class));
        $this->collection->addCollection($this->controllerLoader->load(AgentStatsController::class));

        // Chat controllers
        $this->collection->addCollection($this->controllerLoader->load(ChatCompletionController::class));
        $this->collection->addCollection($this->controllerLoader->load(OpenAIChatCompletionController::class));
        $this->collection->addCollection($this->controllerLoader->load(ChatSendMessageController::class));
        $this->collection->addCollection($this->controllerLoader->load(ChatGetHistoryController::class));

        // ChatAssistant controllers
        $this->collection->addCollection($this->controllerLoader->load(ChatAssistantListController::class));
        $this->collection->addCollection($this->controllerLoader->load(ChatAssistantCreateController::class));
        $this->collection->addCollection($this->controllerLoader->load(ChatAssistantUpdateController::class));
        $this->collection->addCollection($this->controllerLoader->load(ChatAssistantDeleteController::class));
        $this->collection->addCollection($this->controllerLoader->load(ChatAssistantCreateSessionController::class));

        // Chunk controllers
        $this->collection->addCollection($this->controllerLoader->load(ChunkRetrieveController::class));
        $this->collection->addCollection($this->controllerLoader->load(ChunkAddController::class));
        $this->collection->addCollection($this->controllerLoader->load(ChunkUpdateController::class));
        $this->collection->addCollection($this->controllerLoader->load(ChunkDeleteController::class));

        // Conversation controllers
        $this->collection->addCollection($this->controllerLoader->load(ConversationListController::class));
        $this->collection->addCollection($this->controllerLoader->load(ConversationCreateController::class));
        $this->collection->addCollection($this->controllerLoader->load(ConversationUpdateController::class));
        $this->collection->addCollection($this->controllerLoader->load(ConversationDeleteController::class));
        $this->collection->addCollection($this->controllerLoader->load(ConversationCreateSessionController::class));
        $this->collection->addCollection($this->controllerLoader->load(ConversationSendMessageController::class));
        $this->collection->addCollection($this->controllerLoader->load(ConversationGetHistoryController::class));

        // Dataset controllers
        $this->collection->addCollection($this->controllerLoader->load(DatasetListController::class));
        $this->collection->addCollection($this->controllerLoader->load(DatasetCreateController::class));
        $this->collection->addCollection($this->controllerLoader->load(DatasetUpdateController::class));
        $this->collection->addCollection($this->controllerLoader->load(DatasetDeleteController::class));
        $this->collection->addCollection($this->controllerLoader->load(GetKnowledgeGraphController::class));

        // DatasetDocument controllers
        $this->collection->addCollection($this->controllerLoader->load(DatasetDocumentListController::class));
        $this->collection->addCollection($this->controllerLoader->load(DatasetDocumentUploadController::class));
        $this->collection->addCollection($this->controllerLoader->load(DatasetDocumentBatchDeleteController::class));
        $this->collection->addCollection($this->controllerLoader->load(DatasetDocumentGetStatsController::class));
        $this->collection->addCollection($this->controllerLoader->load(DatasetDocumentDeleteController::class));
        $this->collection->addCollection($this->controllerLoader->load(DatasetDocumentParseController::class));
        $this->collection->addCollection($this->controllerLoader->load(DatasetDocumentGetParseStatusController::class));

        // Document controllers
        $this->collection->addCollection($this->controllerLoader->load(DocumentListController::class));
        $this->collection->addCollection($this->controllerLoader->load(DocumentUploadController::class));
        $this->collection->addCollection($this->controllerLoader->load(DocumentDetailController::class));
        $this->collection->addCollection($this->controllerLoader->load(DocumentUpdateController::class));
        $this->collection->addCollection($this->controllerLoader->load(DocumentDeleteController::class));
        $this->collection->addCollection($this->controllerLoader->load(DocumentRetryUploadController::class));
        $this->collection->addCollection($this->controllerLoader->load(DocumentParseController::class));
        $this->collection->addCollection($this->controllerLoader->load(DocumentGetParseStatusController::class));

        // KnowledgeGraph controllers
        $this->collection->addCollection($this->controllerLoader->load(KnowledgeGraphGetByDatasetController::class));
        $this->collection->addCollection($this->controllerLoader->load(SearchEntitiesController::class));
        $this->collection->addCollection($this->controllerLoader->load(GetEntityRelationsController::class));
        $this->collection->addCollection($this->controllerLoader->load(KnowledgeGraphGetStatsController::class));
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
