<?php

namespace AIContentAuditBundle\Enum;

/**
 * 处理状态枚举
 */
enum ProcessStatus: string
{
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