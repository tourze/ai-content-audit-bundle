<?php

namespace AIContentAuditBundle\Controller\Admin;

use AIContentAuditBundle\Entity\RiskKeyword;
use AIContentAuditBundle\Enum\RiskLevel;
use Doctrine\ORM\EntityManagerInterface;
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

#[IsGranted('ROLE_ADMIN')]
class RiskKeywordCrudController extends AbstractCrudController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
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
            ->setPaginatorPageSize(50);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('keyword', '关键词'))
            ->add(ChoiceFilter::new('riskLevel', '风险等级')
                ->setChoices($this->getRiskLevelChoices()))
            ->add(TextFilter::new('category', '分类'))
            ->add(TextFilter::new('addedBy', '添加人'))
            ->add(TextFilter::new('updateTime', '更新时间'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnDetail();
        yield TextField::new('keyword', '关键词')
            ->setMaxLength(100)
            ->setRequired(true);
        yield ChoiceField::new('riskLevel', '风险等级')
            ->setChoices(array_combine(
                array_map(fn(RiskLevel $level) => $level->getLabel(), RiskLevel::cases()),
                array_map(fn(RiskLevel $level) => $level->value, RiskLevel::cases())
            ))
            ->setRequired(true)
            ->renderAsBadges([
                '低风险' => 'success',
                '中风险' => 'warning',
                '高风险' => 'danger',
            ]);
        yield TextField::new('category', '分类')
            ->setRequired(false);
        yield TextareaField::new('description', '说明')
            ->setRequired(false)
            ->hideOnIndex();
        yield TextField::new('addedBy', '添加人')
            ->setRequired(false)
            ->hideOnIndex();
        yield DateTimeField::new('updateTime', '更新时间')
            ->setFormTypeOption('disabled', true);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setLabel('添加关键词');
            })
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN');
    }

    public function createEntity(string $entityFqcn): RiskKeyword
    {
        $keyword = new RiskKeyword();
        $keyword->setUpdateTime(new \DateTimeImmutable());
        
        // 设置当前登录用户为添加人
        if ($this->getUser()) {
            $keyword->setAddedBy($this->getUser()->getUserIdentifier());
        }
        
        return $keyword;
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        // 更新关键词时，同时更新updateTime字段
        $entityInstance->setUpdateTime(new \DateTimeImmutable());
        
        parent::updateEntity($entityManager, $entityInstance);
    }

    /**
     * 获取风险等级选项
     */
    private function getRiskLevelChoices(): array
    {
        $choices = [];
        foreach (RiskLevel::cases() as $riskLevel) {
            if ($riskLevel !== RiskLevel::NO_RISK) {
                $choices[$riskLevel->getLabel()] = $riskLevel->value;
            }
        }
        return $choices;
    }
}
