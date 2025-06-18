<?php

namespace AIContentAuditBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 审核结果枚举
 */
enum AuditResult: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case PASS = '通过';
    case MODIFY = '修改';
    case DELETE = '删除';

    /**
     * 获取显示标签
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::PASS => '通过',
            self::MODIFY => '修改',
            self::DELETE => '删除',
        };
    }

    /**
     * 获取显示样式
     */
    public function getStyle(): string
    {
        return match ($this) {
            self::PASS => 'success',
            self::MODIFY => 'warning',
            self::DELETE => 'danger',
        };
    }
} 