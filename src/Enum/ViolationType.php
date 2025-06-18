<?php

namespace AIContentAuditBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 违规类型枚举
 */
enum ViolationType: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case MACHINE_HIGH_RISK = '机器识别高风险内容';
    case MANUAL_DELETE = '人工审核删除';
    case USER_REPORT = '用户举报';
    case REPEATED_VIOLATION = '重复违规';

    /**
     * 获取显示标签
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::MACHINE_HIGH_RISK => '机器识别高风险内容',
            self::MANUAL_DELETE => '人工审核删除',
            self::USER_REPORT => '用户举报',
            self::REPEATED_VIOLATION => '重复违规',
        };
    }
} 