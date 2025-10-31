<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 文档状态枚举
 *
 * 替换魔法字符串常量，提升代码类型安全和可维护性
 */
enum DocumentStatus: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case PENDING = 'pending';
    case UPLOADING = 'uploading';
    case UPLOADED = 'uploaded';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case SYNCED = 'synced';
    case SYNC_FAILED = 'sync_failed';

    /**
     * 获取状态的中文描述
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待处理',
            self::UPLOADING => '上传中',
            self::UPLOADED => '已上传',
            self::PROCESSING => '处理中',
            self::COMPLETED => '已完成',
            self::FAILED => '失败',
            self::SYNCED => '已同步',
            self::SYNC_FAILED => '同步失败',
        };
    }

    /**
     * 获取状态对应的CSS类名
     */
    public function getCssClass(): string
    {
        return match ($this) {
            self::PENDING => 'secondary',
            self::UPLOADING => 'warning',
            self::UPLOADED => 'info',
            self::PROCESSING => 'warning',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
            self::SYNCED => 'success',
            self::SYNC_FAILED => 'danger',
        };
    }

    /**
     * 判断是否为失败状态
     */
    public function isFailed(): bool
    {
        return match ($this) {
            self::FAILED, self::SYNC_FAILED => true,
            default => false,
        };
    }

    /**
     * 判断是否为处理中状态
     */
    public function isProcessing(): bool
    {
        return match ($this) {
            self::UPLOADING, self::PROCESSING => true,
            default => false,
        };
    }

    /**
     * 判断是否为完成状态
     */
    public function isCompleted(): bool
    {
        return match ($this) {
            self::COMPLETED, self::UPLOADED => true,
            default => false,
        };
    }

    /**
     * 判断是否需要重传
     */
    public function needsRetry(): bool
    {
        return $this->isFailed();
    }

    /**
     * 获取所有状态值的数组（用于验证和选择框）
     *
     * @return string[]
     */
    public static function getValues(): array
    {
        return array_map(fn (self $status) => $status->value, self::cases());
    }

    /**
     * 获取状态选择映射（中文标签 => 英文值）
     *
     * @return array<string, string>
     */
    public static function getChoices(): array
    {
        $choices = [];
        foreach (self::cases() as $status) {
            $choices[$status->getLabel()] = $status->value;
        }

        return $choices;
    }

    /**
     * 从值创建状态枚举（兼容数字和字符串）
     *
     * @param string|int|null $value 状态值
     * @return self|null 返回对应的枚举实例，如果无法匹配则返回null
     */
    public static function fromValue(string|int|null $value): ?self
    {
        if (null === $value) {
            return null;
        }

        // 处理数字状态码兼容性
        $numericMapping = [
            0 => self::PENDING,
            1 => self::UPLOADED,        // 兼容状态值 1
            2 => self::PROCESSING,
            3 => self::COMPLETED,
            4 => self::FAILED,
        ];

        // 如果是数字，使用映射表
        if (is_int($value) && isset($numericMapping[$value])) {
            return $numericMapping[$value];
        }

        // 如果是字符串数字，先转换为整数再查找
        if (is_string($value) && is_numeric($value)) {
            $intValue = (int) $value;
            if (isset($numericMapping[$intValue])) {
                return $numericMapping[$intValue];
            }
        }

        // 如果是字符串，先检查API状态映射
        if (is_string($value)) {
            // API状态到枚举值的映射
            $apiStatusMapping = [
                'parsing' => self::PROCESSING,
                'parsed' => self::COMPLETED,
                'parse_failed' => self::FAILED,
            ];

            if (isset($apiStatusMapping[$value])) {
                return $apiStatusMapping[$value];
            }

            // 直接匹配枚举值
            return self::tryFrom($value);
        }

        return null;
    }

    /**
     * 将枚举转换为数字状态码（用于API兼容）
     */
    public function toNumeric(): int
    {
        return match ($this) {
            self::PENDING => 0,
            self::UPLOADED => 1,
            self::PROCESSING => 2,
            self::COMPLETED => 3,
            self::FAILED => 4,
            self::UPLOADING => 0,      // 映射到待处理
            self::SYNCED => 3,         // 映射到已完成
            self::SYNC_FAILED => 4,    // 映射到失败
        };
    }
}
