<?php

declare(strict_types=1);

namespace AIContentAuditBundle\Controller\Admin;

use AIContentAuditBundle\Entity\Report;
use AIContentAuditBundle\Enum\ProcessStatus;
use AIContentAuditBundle\Service\ReportService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Context\AdminContextInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @extends AbstractCrudController<Report>
 */
#[AdminCrud(
    routePath: '/ai-content-audit/report',
    routeName: 'ai_content_audit_report',
)]
#[IsGranted(attribute: 'ROLE_ADMIN')]
final class ReportCrudController extends AbstractCrudController
{

    public function __construct(
        private readonly ReportService $reportService,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    public static function getEntityFqcn(): string
    {
        return Report::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('举报')
            ->setEntityLabelInPlural('举报')
            ->setSearchFields(['reportReason', 'processResult', 'reporterUser.username'])
            ->setDefaultSort(['reportTime' => 'DESC'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        $processStatusChoices = $this->getProcessStatusChoices();

        return $filters
            ->add(TextFilter::new('reporterUser', '举报用户'))
            ->add(TextFilter::new('reportedContent', '被举报内容'))
            ->add(TextFilter::new('reportReason', '举报理由'))
            ->add(ChoiceFilter::new('processStatus', '处理状态')
                ->setChoices($processStatusChoices))
            ->add(TextFilter::new('processResult', '处理结果'))
            ->add(DateTimeFilter::new('reportTime', '举报时间'))
        ;
    }

    /**
     * @return array<string, ProcessStatus>
     */
    private function getProcessStatusChoices(): array
    {
        $choices = [];
        foreach (ProcessStatus::cases() as $status) {
            $choices[$status->getLabel()] = $status;
        }

        return $choices;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->onlyOnDetail();
        yield TextField::new('reporterUser', '举报用户');
        yield TextField::new('reportedContent', '被举报内容');
        yield DateTimeField::new('reportTime', '举报时间');
        yield TextareaField::new('reportReason', '举报理由');
        yield $this->createProcessStatusField();
        yield DateTimeField::new('processTime', '处理时间');
        yield TextareaField::new('processResult', '处理结果')
            ->hideOnIndex()
        ;
    }

    private function createProcessStatusField(): ChoiceField
    {
        $labels = array_map(fn (ProcessStatus $status) => $status->getLabel(), ProcessStatus::cases());

        return ChoiceField::new('processStatus', '处理状态')
            ->setChoices(array_combine($labels, ProcessStatus::cases()))
            ->renderAsBadges([
                ProcessStatus::PENDING->value => 'warning',
                ProcessStatus::PROCESSING->value => 'info',
                ProcessStatus::COMPLETED->value => 'success',
            ])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $processAction = Action::new('process', '处理举报', 'fa fa-gavel')
            ->linkToCrudAction('processReport')
            ->displayIf(static function (Report $report) {
                return $report->isPending();
            })
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $processAction)
            ->add(Crud::PAGE_DETAIL, $processAction)
            ->disable(Action::NEW, Action::DELETE, Action::EDIT)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
        ;
    }

    /**
     * 处理举报
     */
    #[AdminAction(
        routePath: '{entityId}/processReport',
        routeName: 'processReport',
    )]
    public function processReport(AdminContext $context): Response
    {
        $report = $this->getReportFromContext($context);
        $user = $this->getUser();
        if (null === $user) {
            throw $this->createAccessDeniedException('用户未登录');
        }
        $this->reportService->startProcessing($report, $user->getUserIdentifier());

        return $this->render('@AIContentAudit/admin/process_report.html.twig', [
            'report' => $report,
            'backUrl' => $this->generateIndexUrl(),
        ]);
    }

    private function getReportFromContext(AdminContext $context): Report
    {
        $report = $context->getEntity()->getInstance();

        if (null === $report) {
            throw $this->createNotFoundException('举报不存在');
        }

        if (!$report instanceof Report) {
            throw $this->createNotFoundException('实体类型错误');
        }

        return $report;
    }

    /**
     * 提交举报处理
     */
    public function submitProcess(Request $request): Response
    {
        $reportId = $request->query->getInt('entityId');
        $report = $this->validateReportId($reportId);

        if (!$this->validateCsrfToken($request, $reportId)) {
            return $this->handleInvalidCsrfToken($reportId);
        }

        $processData = $this->extractProcessData($request);
        if (null === $processData) {
            return $this->handleInvalidProcessData($reportId);
        }

        return $this->handleProcessSubmission($report, $processData, $reportId);
    }

    private function handleInvalidCsrfToken(int $reportId): Response
    {
        $this->addFlash('danger', '无效的CSRF令牌');

        return $this->redirectToProcessPage($reportId);
    }

    private function handleInvalidProcessData(int $reportId): Response
    {
        $this->addFlash('danger', '请填写处理结果和选择处理动作');

        return $this->redirectToProcessPage($reportId);
    }

    private function validateReportId(int $reportId): Report
    {
        if ($reportId <= 0) {
            throw $this->createNotFoundException('无效的举报ID');
        }

        $report = $this->reportService->findReport($reportId);
        if (null === $report) {
            throw $this->createNotFoundException('举报不存在');
        }

        return $report;
    }

    private function validateCsrfToken(Request $request, int $reportId): bool
    {
        $token = $request->request->get('_token');
        $tokenString = is_string($token) ? $token : null;

        return $this->isCsrfTokenValid('submit_process_' . $reportId, $tokenString);
    }

    private function extractProcessData(Request $request): ?string
    {
        $processResult = $request->request->get('processResult');
        $action = $request->request->get('action');

        $processResultString = is_string($processResult) ? trim($processResult) : '';
        $actionString = is_string($action) ? trim($action) : '';

        if ('' === $processResultString || '' === $actionString) {
            return null;
        }

        return $processResultString;
    }

    private function handleProcessSubmission(Report $report, string $processResultString, int $reportId): Response
    {
        try {
            return $this->executeProcessing($report, $processResultString);
        } catch (\Exception $e) {
            $this->addFlash('danger', '处理失败: ' . $e->getMessage());

            return $this->redirectToProcessPage($reportId);
        }
    }

    private function executeProcessing(Report $report, string $processResultString): Response
    {
        $user = $this->getUser();
        if (null === $user) {
            throw $this->createAccessDeniedException('用户未登录');
        }

        $this->reportService->completeProcessing(
            $report,
            $processResultString,
            $user->getUserIdentifier()
        );

        $this->addFlash('success', '举报处理完成');

        return $this->redirect($this->generateIndexUrl());
    }

    private function generateIndexUrl(): string
    {
        return $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl()
        ;
    }

    private function redirectToProcessPage(int $reportId): Response
    {
        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction('processReport')
            ->set('entityId', $reportId)
            ->generateUrl()
        ;

        return $this->redirect($url);
    }

}
