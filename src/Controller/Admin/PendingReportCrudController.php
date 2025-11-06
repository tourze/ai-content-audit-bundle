<?php

declare(strict_types=1);

namespace AIContentAuditBundle\Controller\Admin;

use AIContentAuditBundle\Entity\Report;
use AIContentAuditBundle\Enum\ProcessStatus;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Exception\EntityNotFoundException;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @extends AbstractCrudController<Report>
 */
#[AdminCrud(
    routePath: '/ai-content-audit/pending-report',
    routeName: 'ai_content_audit_pending_report',
)]
#[IsGranted(attribute: 'ROLE_ADMIN')]
final class PendingReportCrudController extends AbstractCrudController
{
    public function __construct(private readonly AdminUrlGenerator $adminUrlGenerator)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Report::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('待处理举报')
            ->setEntityLabelInPlural('待处理举报')
            ->setSearchFields(['reportReason', 'processResult', 'reporterUser.username'])
            ->setDefaultSort(['reportTime' => 'ASC'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('reporterUser', '举报用户'))
            ->add(TextFilter::new('reportedContent', '被举报内容'))
            ->add(TextFilter::new('reportReason', '举报理由'))
            ->add(DateTimeFilter::new('reportTime', '举报时间'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->onlyOnDetail();
        yield TextField::new('reporterUser', '举报用户');
        yield TextField::new('reportedContent', '被举报内容');
        yield DateTimeField::new('reportTime', '举报时间');
        yield TextareaField::new('reportReason', '举报理由');
        yield ChoiceField::new('processStatus', '处理状态')
            ->setChoices(array_combine(
                array_map(fn (ProcessStatus $status) => $status->getLabel(), ProcessStatus::cases()),
                ProcessStatus::cases()
            ))
            ->renderAsBadges([
                ProcessStatus::PENDING->value => 'warning',
                ProcessStatus::PROCESSING->value => 'info',
                ProcessStatus::COMPLETED->value => 'success',
            ])
        ;
        yield DateTimeField::new('processTime', '处理时间');
        yield TextareaField::new('processResult', '处理结果')
            ->hideOnIndex()
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $processAction = Action::new('process', '处理举报', 'fa fa-gavel')
            ->linkToCrudAction('process')
            ->displayIf(static function (Report $report) {
                return $report->isPending();
            })
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $processAction)
            ->add(Crud::PAGE_DETAIL, $processAction)
            ->disable(Action::NEW, Action::DELETE)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
        ;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        return $queryBuilder
            ->andWhere('entity.processStatus = :status')
            ->setParameter('status', '待审核')
            ->orderBy('entity.reportTime', 'ASC')
        ;
    }

    #[AdminAction(
        routeName: 'ai_content_audit_pending_report_process',
        routePath: '{entityId}/process',
    )]
    public function process(Request $request): RedirectResponse
    {
        $entityIdValue = $request->attributes->get('entityId') ?? $request->query->get('entityId');
        assert(is_string($entityIdValue) || is_numeric($entityIdValue), 'Entity ID must be numeric');
        $entityId = (int) $entityIdValue;
        if ($entityId <= 0) {
            throw new EntityNotFoundException();
        }

        // 使用 AdminUrlGenerator 生成正确的后台 URL（适配自定义后台前缀/多Dashboard）
        $url = clone $this->adminUrlGenerator;
        $targetUrl = $url
            ->unsetAll()
            ->setController(self::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($entityId)
            ->generateUrl()
        ;

        return $this->redirect($targetUrl);
    }
}
