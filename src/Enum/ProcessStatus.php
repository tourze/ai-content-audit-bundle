<?php

namespace AIContentAuditBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 处理状态枚举
 */
enum ProcessStatus: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case PENDING = '待审核';
    case PROCESSING = '审核中';
    case COMPLETED = '已处理';

    /**
     * 获取显示标签
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待审核',
            self::PROCESSING => '审核中',
            self::COMPLETED => '已处理',
        };
    }

    /**
     * 获取显示样式
     */
    public function getStyle(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PROCESSING => 'info',
            self::COMPLETED => 'success',
        };
    }
} 