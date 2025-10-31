<?php

declare(strict_types=1);

namespace AIContentAuditBundle\Controller\Admin;

use AIContentAuditBundle\Entity\RiskKeyword;
use AIContentAuditBundle\Enum\RiskLevel;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @extends AbstractCrudController<RiskKeyword>
 */
#[AdminCrud(
    routePath: '/ai-content-audit/risk-keyword',
    routeName: 'ai_content_audit_risk_keyword',
)]
#[IsGranted(attribute: 'ROLE_ADMIN')]
final class RiskKeywordCrudController extends AbstractCrudController
{
    public function __construct()
    {
    }

    public static function getEntityFqcn(): string
    {
        return RiskKeyword::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('风险关键词')
            ->setEntityLabelInPlural('风险关键词')
            ->setSearchFields(['keyword', 'category', 'description'])
            ->setDefaultSort(['updateTime' => 'DESC'])
            ->setPaginatorPageSize(50)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('keyword', '关键词'))
            ->add(ChoiceFilter::new('riskLevel', '风险等级')
                ->setChoices($this->getRiskLevelChoices()))
            ->add(TextFilter::new('category', '分类'))
            ->add(TextFilter::new('addedBy', '添加人'))
            ->add(TextFilter::new('updateTime', '更新时间'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->onlyOnDetail();
        yield TextField::new('keyword', '关键词')
            ->setMaxLength(100)
            ->setRequired(true)
        ;
        yield ChoiceField::new('riskLevel', '风险等级')
            ->setChoices($this->getRiskLevelChoices())
            ->setRequired(true)
            ->renderAsBadges([
                RiskLevel::LOW_RISK->value => 'success',
                RiskLevel::MEDIUM_RISK->value => 'warning',
                RiskLevel::HIGH_RISK->value => 'danger',
            ])
        ;
        yield TextField::new('category', '分类')
            ->setRequired(false)
        ;
        yield TextareaField::new('description', '说明')
            ->setRequired(false)
            ->hideOnIndex()
        ;
        yield TextField::new('addedBy', '添加人')
            ->setRequired(false)
            ->hideOnIndex()
        ;
        yield DateTimeField::new('updateTime', '更新时间')
            ->setFormTypeOption('disabled', true)
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
        ;
    }

    public function createEntity(string $entityFqcn): RiskKeyword
    {
        $keyword = new RiskKeyword();
        $keyword->setUpdateTime(new \DateTimeImmutable());

        // 设置当前登录用户为添加人
        $user = $this->getUser();
        if (null !== $user) {
            $keyword->setAddedBy($user->getUserIdentifier());
        }

        return $keyword;
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        // 更新关键词时，同时更新updateTime字段
        if ($entityInstance instanceof RiskKeyword) {
            $entityInstance->setUpdateTime(new \DateTimeImmutable());
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    /**
     * 获取风险等级选项
     * @return array<string, RiskLevel>
     */
    private function getRiskLevelChoices(): array
    {
        $choices = [];
        foreach (RiskLevel::cases() as $riskLevel) {
            if (RiskLevel::NO_RISK !== $riskLevel) {
                $choices[$riskLevel->getLabel()] = $riskLevel;
            }
        }

        return $choices;
    }
}
