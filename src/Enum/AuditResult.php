<?php

declare(strict_types=1);

namespace AIContentAuditBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 审核结果枚举
 */
enum AuditResult: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case PASS = '通过';
    case MODIFY = '修改';
    case DELETE = '删除';

    public function getLabel(): string
    {
        return $this->value;
    }

    /**
     * 获取样式类名
     */
    public function getStyle(): string
    {
        return match ($this) {
            self::PASS => 'success',
            self::MODIFY => 'warning',
            self::DELETE => 'danger',
        };
    }

    /**
     * 获取所有枚举的选项数组（用于下拉列表等）
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function toSelectItems(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $result[] = [
                'value' => $case->value,
                'label' => $case->getLabel(),
            ];
        }

        return $result;
    }
}
