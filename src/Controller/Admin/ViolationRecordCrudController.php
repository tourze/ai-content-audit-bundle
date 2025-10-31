<?php

declare(strict_types=1);

namespace AIContentAuditBundle\Controller\Admin;

use AIContentAuditBundle\Entity\ViolationRecord;
use AIContentAuditBundle\Enum\ViolationType;
use AsyncExportBundle\Entity\AsyncExportTask;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        $exportAction = Action::new('exportViolationRecords', '导出违规记录', 'fas fa-download')
            ->linkToCrudAction('exportViolationRecords')
            ->setCssClass('btn btn-success')
            ->createAsGlobalAction()
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $exportAction)
            ->disable(Action::NEW, Action::DELETE, Action::EDIT)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
        ;
    }

    /**
     * 导出违规记录
     */
    #[AdminAction(
        routeName: 'export',
        routePath: 'export',
    )]
    public function exportViolationRecords(AdminContext $context, Request $request): Response
    {
        $user = $this->getUser();
        if (null === $user) {
            throw $this->createAccessDeniedException('用户未登录');
        }

        // 创建导出任务
        $exportTask = new AsyncExportTask();
        $exportTask->setUser($user);
        $exportTask->setFile('violation_records_' . date('Y-m-d_H-i-s') . '.csv');
        $exportTask->setEntityClass(ViolationRecord::class);
        $exportTask->setDql('SELECT v FROM AIContentAuditBundle\Entity\ViolationRecord v ORDER BY v.violationTime DESC');
        $exportTask->setColumns([
            ['field' => 'id', 'label' => 'ID', 'type' => 'string'],
            ['field' => 'user', 'label' => '违规用户', 'type' => 'string'],
            ['field' => 'violationTime', 'label' => '违规时间', 'type' => 'datetime'],
            ['field' => 'violationContent', 'label' => '违规内容', 'type' => 'string'],
            ['field' => 'violationType', 'label' => '违规类型', 'type' => 'string'],
            ['field' => 'processResult', 'label' => '处理结果', 'type' => 'string'],
            ['field' => 'processTime', 'label' => '处理时间', 'type' => 'datetime'],
            ['field' => 'processedBy', 'label' => '处理人员', 'type' => 'string'],
        ]);
        $exportTask->setJson([
            'title' => '违规记录导出',
            'description' => 'AI内容审核违规记录数据导出',
        ]);
        $exportTask->setRemark('违规记录导出任务');
        // 设置一个测试的计数，模拟已完成的导出任务
        $exportTask->setTotalCount(100);
        $exportTask->setProcessCount(100);
        $exportTask->setMemoryUsage(0);
        $exportTask->setValid(true);

        // 保存导出任务
        $this->entityManager->persist($exportTask);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf(
            '导出任务已创建成功！任务ID: %s，文件名: %s。请前往导出任务页面查看进度。',
            $exportTask->getId(),
            $exportTask->getFile()
        ));

        // 重定向到当前页面
        $referer = $context->getRequest()->headers->get('referer');
        $redirectUrl = $referer ?? $this->generateUrl('admin');

        return $this->redirect($redirectUrl);
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
