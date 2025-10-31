# RAGFlow API DTO 迁移指南

## 概述

为了解决 PHPStan 类型安全问题，我们引入了统一的 API 响应 DTO 系统。该系统提供类型安全的 API 响应处理，同时保持向后兼容性。

## 主要组件

### 1. ApiResponseDto<T>
统一的 API 响应基础类，提供类型安全的响应处理。

```php
// 新的 DTO 方式
$response = $apiClient->createAgent($data);
if ($response->isSuccess()) {
    $agentData = $response->getData(); // AgentDataDto 类型
    echo $agentData->getTitle(); // 类型安全
}

// 向后兼容的数组方式
if (LegacyResponseWrapper::isSuccess($response)) {
    $data = LegacyResponseWrapper::getData($response);
    echo $data['title']; // 兼容性支持
}
```

### 2. 业务数据 DTO
- `AgentDataDto` - Agent 数据
- `ConversationDataDto` - Conversation 数据
- `DatasetDataDto` - Dataset 数据

### 3. 响应工厂系统
- `AbstractResponseFactory` - 抽象工厂基类
- `AgentResponseFactory` - Agent 响应工厂
- `ResponseFactoryResolver` - 工厂解析器

## 迁移步骤

### 第一阶段：基础架构（已完成）
- [x] 创建 DTO 基础类
- [x] 修改 `RAGFlowApiClient::formatResponse()` 返回 DTO
- [x] 更新 Agent 相关 API 方法
- [x] 更新 `AgentApiService` 使用 DTO

### 第二阶段：扩展到其他模块
- [ ] 为 Conversation 创建响应工厂
- [ ] 为 Dataset 创建响应工厂
- [ ] 更新相关服务类

### 第三阶段：渐进式迁移
- [ ] 逐个文件替换数组访问为 DTO 属性访问
- [ ] 添加静态分析检查
- [ ] 移除向后兼容代码

## 使用示例

### 创建 Agent（新方式）
```php
$apiClient = $clientFactory->createClient($instance);
$response = $apiClient->createAgent([
    'title' => 'My Agent',
    'description' => 'Test agent',
    'dsl' => $dslConfig
]);

if ($response->isSuccess()) {
    $agentData = $response->getData();
    $agentId = $agentData->getId();
    $title = $agentData->getTitle();

    // 类型安全的属性访问
    $status = $agentData->getStatus();
    $createdAt = $agentData->getCreatedAt();
} else {
    $errorCode = $response->getCode();
    $errorMessage = $response->getMessage();
}
```

### 创建 Agent（向后兼容）
```php
$apiClient = $clientFactory->createClient($instance);
$response = $apiClient->createAgent($data);

// DTO 支持 ArrayAccess，可以继续使用数组方式
if (0 === $response['code']) {
    $agentData = $response['data'];
    $agentId = $agentData['id'];
    $title = $agentData['title'];
}
```

### 使用 LegacyResponseWrapper
```php
use Tourze\RAGFlowApiBundle\DTO\LegacyResponseWrapper;

$response = $apiClient->createAgent($data);

if (LegacyResponseWrapper::isSuccess($response)) {
    $data = LegacyResponseWrapper::getData($response);
    // 继续使用现有的数组访问逻辑
}
```

## 类型注解

新的 DTO 系统提供了完整的类型注解支持：

```php
/**
 * @return ApiResponseDto<AgentDataDto>
 */
public function createAgent(array $data): ApiResponseDto
{
    // 实现
}

/**
 * @return ApiResponseDto<array<AgentDataDto>>
 */
public function getAgentList(): ApiResponseDto
{
    // 实现
}
```

## 测试

运行 DTO 测试：
```bash
php vendor/bin/phpunit packages/rag-flow-api-bundle/tests/DTO/
```

## 迁移检查清单

- [ ] 检查现有代码中的数组访问模式
- [ ] 识别需要迁移的文件
- [ ] 逐步替换为 DTO 属性访问
- [ ] 运行 PHPStan 确认类型安全
- [ ] 运行测试确保功能正确

## 注意事项

1. **向后兼容性**：所有 DTO 都实现了 `ArrayAccess` 接口，现有代码无需立即修改
2. **类型安全**：使用 DTO 属性访问可以避免 `offsetAccess.nonOffsetAccessible` 错误
3. **渐进式迁移**：可以按模块逐步迁移，不需要一次性全部修改
4. **测试覆盖**：所有 DTO 类都有完整的单元测试