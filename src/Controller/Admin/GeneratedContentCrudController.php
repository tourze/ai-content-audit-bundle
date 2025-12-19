<?php

declare(strict_types=1);

namespace AIContentAuditBundle\Controller\Admin;

use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Enum\AuditResult;
use AIContentAuditBundle\Enum\RiskLevel;
use AIContentAuditBundle\Exception\InvalidAuditResultException;
use AIContentAuditBundle\Service\ContentAuditService;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
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
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @extends AbstractCrudController<GeneratedContent>
 */
#[AdminCrud(
    routePath: '/ai-content-audit/generated-content',
    routeName: 'ai_content_audit_generated_content',
)]
#[IsGranted(attribute: 'ROLE_ADMIN')]
final class GeneratedContentCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly ContentAuditService $contentAuditService,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return GeneratedContent::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('生成内容')
            ->setEntityLabelInPlural('生成内容')
            ->setSearchFields(['inputText', 'outputText', 'user'])
            ->setDefaultSort(['machineAuditTime' => 'DESC'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('user', '用户'))
            ->add(ChoiceFilter::new('machineAuditResult', '机器审核结果')
                ->setChoices($this->getRiskLevelChoices()))
            ->add(ChoiceFilter::new('manualAuditResult', '人工审核结果')
                ->setChoices($this->getAuditResultChoices()))
            ->add(DateTimeFilter::new('machineAuditTime', '机器审核时间'))
            ->add(DateTimeFilter::new('manualAuditTime', '人工审核时间'))
            ->add(TextFilter::new('inputText', '用户输入文本'))
            ->add(TextFilter::new('outputText', 'AI输出文本'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->onlyOnDetail();
        yield TextField::new('user', '用户');
        yield TextareaField::new('inputText', '用户输入文本')
            ->hideOnIndex()
        ;
        yield TextareaField::new('outputText', 'AI输出文本')
            ->hideOnIndex()
        ;
        yield ChoiceField::new('machineAuditResult', '机器审核结果')
            ->setChoices(array_combine(
                array_map(fn (RiskLevel $level) => $level->getLabel(), RiskLevel::cases()),
                RiskLevel::cases()
            ))
            ->renderAsBadges([
                RiskLevel::NO_RISK->value => 'success',
                RiskLevel::LOW_RISK->value => 'success',
                RiskLevel::MEDIUM_RISK->value => 'warning',
                RiskLevel::HIGH_RISK->value => 'danger',
            ])
        ;
        yield DateTimeField::new('machineAuditTime', '机器审核时间');
        yield ChoiceField::new('manualAuditResult', '人工审核结果')
            ->setChoices(array_combine(
                array_map(fn (AuditResult $result) => $result->getLabel(), AuditResult::cases()),
                AuditResult::cases()
            ))
            ->renderAsBadges([
                AuditResult::PASS->value => 'success',
                AuditResult::MODIFY->value => 'warning',
                AuditResult::DELETE->value => 'danger',
            ])
        ;
        yield DateTimeField::new('manualAuditTime', '人工审核时间');
    }

    public function configureActions(Actions $actions): Actions
    {
        $audit = Action::new('audit', '审核', 'fa fa-check')
            ->linkToCrudAction('audit')
            ->displayIf(static function (GeneratedContent $entity) {
                return $entity->needsManualAudit();
            })
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, $audit)
            ->add(Crud::PAGE_DETAIL, $audit)
            ->disable(Action::NEW)
            ->disable(Action::DELETE)
            ->disable(Action::EDIT)
        ;
    }

    #[AdminAction(
        routePath: '{entityId}/audit',
        routeName: 'audit',
    )]
    public function audit(AdminContext $context): Response
    {
        $entityInstance = $context->getEntity()->getInstance();
        if (!$entityInstance instanceof GeneratedContent) {
            throw new NotFoundHttpException('内容不存在');
        }

        $content = $entityInstance;
        $request = $context->getRequest();

        // 处理表单提交（POST 到当前页面）
        if (null !== $request && $request->isMethod('POST')) {
            $auditResultValue = $request->request->get('auditResult');
            if (null === $auditResultValue || '' === $auditResultValue) {
                throw new InvalidAuditResultException('审核结果不能为空');
            }

            if (!is_string($auditResultValue) && !is_int($auditResultValue)) {
                throw new InvalidAuditResultException('审核结果格式无效');
            }

            $auditResult = AuditResult::from($auditResultValue);
            $operator = $this->getUser()?->getUserIdentifier() ?? 'system';
            $this->contentAuditService->manualAudit($content, $auditResult, $operator);

            // 成功后返回列表
            $referer = $request->headers->get('referer');

            return $this->redirect(is_string($referer) ? $referer : $this->generateUrl('admin', [
                'crudAction' => 'index',
                'crudControllerFqcn' => self::class,
            ]));
        }

        // 构建表单视图，显示内容详情并提供审核操作选项
        return $this->render('@AIContentAudit/admin/audit_content.html.twig', [
            'content' => $content,
            'auditOptions' => $this->getAuditResultChoices(),
            'backUrl' => $this->generateUrl('admin', [
                'crudAction' => 'index',
                'crudControllerFqcn' => self::class,
            ]),
        ]);
    }

    /**
     * 获取风险等级选项
     * @return array<string, RiskLevel>
     */
    private function getRiskLevelChoices(): array
    {
        $choices = [];
        foreach (RiskLevel::cases() as $riskLevel) {
            $choices[$riskLevel->getLabel()] = $riskLevel;
        }

        return $choices;
    }

    /**
     * 获取审核结果选项
     * @return array<string, AuditResult>
     */
    private function getAuditResultChoices(): array
    {
        $choices = [];
        foreach (AuditResult::cases() as $auditResult) {
            $choices[$auditResult->getLabel()] = $auditResult;
        }

        return $choices;
    }
}
