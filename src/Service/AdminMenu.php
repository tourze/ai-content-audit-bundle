<?php

namespace AIContentAuditBundle\Service;

use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Entity\Report;
use AIContentAuditBundle\Entity\RiskKeyword;
use AIContentAuditBundle\Entity\ViolationRecord;
use Knp\Menu\ItemInterface;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;

/**
 * AI内容审核菜单服务
 */
class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private readonly LinkGeneratorInterface $linkGenerator,
    )
    {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (!$item->getChild('内容审核')) {
            $item->addChild('内容审核');
        }

        $contentMenu = $item->getChild('内容审核');

        // 生成内容菜单
        $contentMenu->addChild('生成内容')
            ->setUri($this->linkGenerator->getCurdListPage(GeneratedContent::class))
            ->setAttribute('icon', 'fas fa-file-alt');

        // 举报管理菜单
        $contentMenu->addChild('举报管理')
            ->setUri($this->linkGenerator->getCurdListPage(Report::class))
            ->setAttribute('icon', 'fas fa-flag');

        // 风险关键词菜单
        $contentMenu->addChild('风险关键词')
            ->setUri($this->linkGenerator->getCurdListPage(RiskKeyword::class))
            ->setAttribute('icon', 'fas fa-key');

        // 违规记录菜单
        $contentMenu->addChild('违规记录')
            ->setUri($this->linkGenerator->getCurdListPage(ViolationRecord::class))
            ->setAttribute('icon', 'fas fa-ban');
    }
}
