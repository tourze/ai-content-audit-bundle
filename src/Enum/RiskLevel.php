<?php

namespace AIContentAuditBundle\Enum;

/**
 * 风险等级枚举
 */
enum RiskLevel: string
{
    case NO_RISK = '无风险';
    case LOW_RISK = '低风险';
    case MEDIUM_RISK = '中风险';
    case HIGH_RISK = '高风险';

    /**
     * 获取显示标签
     */
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
     * 获取风险等级顺序值，用于比较
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
     * 比较两个风险等级，返回较高的一个
     */
    public static function getHigher(self $level1, self $level2): self
    {
        return $level1->getOrder() >= $level2->getOrder() ? $level1 : $level2;
    }
} 