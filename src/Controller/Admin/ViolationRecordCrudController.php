<?php

namespace AIContentAuditBundle\Controller\Admin;

use AIContentAuditBundle\Entity\ViolationRecord;
use AIContentAuditBundle\Enum\ViolationType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ViolationRecordCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ViolationRecord::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('违规记录')
            ->setEntityLabelInPlural('违规记录')
            ->setSearchFields(['user.username', 'violationContent', 'processResult', 'processedBy'])
            ->setDefaultSort(['violationTime' => 'DESC'])
            ->setPaginatorPageSize(20);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('user', '用户'))
            ->add(ChoiceFilter::new('violationType', '违规类型')
                ->setChoices($this->getViolationTypeChoices()))
            ->add(TextFilter::new('violationContent', '违规内容'))
            ->add(TextFilter::new('processResult', '处理结果'))
            ->add(TextFilter::new('processedBy', '处理人员'))
            ->add(DateTimeFilter::new('violationTime', '违规时间'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnDetail();
        yield AssociationField::new('user', '用户');
        yield DateTimeField::new('violationTime', '违规时间');
        yield TextareaField::new('violationContent', '违规内容')
            ->hideOnIndex();
        yield ChoiceField::new('violationType', '违规类型')
            ->setChoices(array_combine(
                array_map(fn(ViolationType $type) => $type->getLabel(), ViolationType::cases()),
                array_map(fn(ViolationType $type) => $type->value, ViolationType::cases())
            ))
            ->renderAsBadges([
                '机器识别高风险内容' => 'danger',
                '人工审核删除' => 'warning',
                '用户举报' => 'info',
                '重复违规' => 'dark',
            ]);
        yield TextareaField::new('processResult', '处理结果');
        yield DateTimeField::new('processTime', '处理时间');
        yield TextField::new('processedBy', '处理人员');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_DETAIL, Action::DELETE)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN');
    }

    /**
     * 获取违规类型选项
     */
    private function getViolationTypeChoices(): array
    {
        $choices = [];
        foreach (ViolationType::cases() as $violationType) {
            $choices[$violationType->getLabel()] = $violationType->value;
        }
        return $choices;
    }
}
