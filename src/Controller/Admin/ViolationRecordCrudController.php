<?php

declare(strict_types=1);

namespace AIContentAuditBundle\Controller\Admin;

use AIContentAuditBundle\Entity\ViolationRecord;
use AIContentAuditBundle\Enum\ViolationType;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @extends AbstractCrudController<ViolationRecord>
 */
#[AdminCrud(
    routePath: '/ai-content-audit/violation-record',
    routeName: 'ai_content_audit_violation_record',
)]
#[IsGranted(attribute: 'ROLE_ADMIN')]
final class ViolationRecordCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return ViolationRecord::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('违规记录')
            ->setEntityLabelInPlural('违规记录')
            ->setSearchFields(['user', 'violationContent', 'processResult', 'processedBy'])
            ->setDefaultSort(['violationTime' => 'DESC'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('user', '用户'))
            ->add(ChoiceFilter::new('violationType', '违规类型')
                ->setChoices($this->getViolationTypeChoices()))
            ->add(TextFilter::new('violationContent', '违规内容'))
            ->add(TextFilter::new('processResult', '处理结果'))
            ->add(TextFilter::new('processedBy', '处理人员'))
            ->add(DateTimeFilter::new('violationTime', '违规时间'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->onlyOnDetail();
        yield TextField::new('user', '用户');
        yield DateTimeField::new('violationTime', '违规时间');
        yield TextareaField::new('violationContent', '违规内容')
            ->hideOnIndex()
        ;
        yield ChoiceField::new('violationType', '违规类型')
            ->setChoices(array_combine(
                array_map(fn (ViolationType $type) => $type->getLabel(), ViolationType::cases()),
                ViolationType::cases()
            ))
            ->renderAsBadges([
                ViolationType::MACHINE_HIGH_RISK->value => 'danger',
                ViolationType::MANUAL_DELETE->value => 'warning',
                ViolationType::USER_REPORT->value => 'info',
                ViolationType::REPEATED_VIOLATION->value => 'dark',
            ])
        ;
        yield TextareaField::new('processResult', '处理结果');
        yield DateTimeField::new('processTime', '处理时间');
        yield TextField::new('processedBy', '处理人员');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW, Action::DELETE, Action::EDIT)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
        ;
    }

    /**
     * 获取违规类型选项
     * @return array<string, ViolationType>
     */
    private function getViolationTypeChoices(): array
    {
        $choices = [];
        foreach (ViolationType::cases() as $violationType) {
            $choices[$violationType->getLabel()] = $violationType;
        }

        return $choices;
    }
}
