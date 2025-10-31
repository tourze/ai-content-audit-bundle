<?php

declare(strict_types=1);

namespace AIContentAuditBundle\Enum;

use Tourze\EnumExtra\BadgeInterface;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 风险等级枚举
 */
enum RiskLevel: string implements Labelable, Itemable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;

    case NO_RISK = '无风险';
    case LOW_RISK = '低风险';
    case MEDIUM_RISK = '中风险';
    case HIGH_RISK = '高风险';

    public function getLabel(): string
    {
        return match ($this) {
            self::NO_RISK => '无风险',
            self::LOW_RISK => '低风险',
            self::MEDIUM_RISK => '中风险',
            self::HIGH_RISK => '高风险',
        };
    }

    /**
     * 获取风险等级的数字顺序
     */
    public function getOrder(): int
    {
        return match ($this) {
            self::NO_RISK => 0,
            self::LOW_RISK => 1,
            self::MEDIUM_RISK => 2,
            self::HIGH_RISK => 3,
        };
    }

    /**
     * 获取两个风险等级中较高的等级
     */
    public static function getHigher(self $level1, self $level2): self
    {
        return $level1->getOrder() >= $level2->getOrder() ? $level1 : $level2;
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

    public function getBadge(): string
    {
        return match ($this) {
            self::NO_RISK => self::SUCCESS,
            self::LOW_RISK => self::WARNING,
            self::MEDIUM_RISK => self::INFO,
            self::HIGH_RISK => self::DANGER,
        };
    }
}
