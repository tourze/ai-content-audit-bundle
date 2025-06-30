<?php

namespace AIContentAuditBundle\Controller\Admin;

use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Enum\AuditResult;
use AIContentAuditBundle\Enum\RiskLevel;
use AIContentAuditBundle\Exception\InvalidAuditResultException;
use AIContentAuditBundle\Repository\GeneratedContentRepository;
use AIContentAuditBundle\Service\ContentAuditService;
use Doctrine\ORM\EntityManagerInterface;
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
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(attribute: 'ROLE_ADMIN')]
class GeneratedContentCrudController extends AbstractCrudController
{
    public function __construct(
        private ContentAuditService $contentAuditService,
        private readonly GeneratedContentRepository $contentRepository,
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
            ->setSearchFields(['inputText', 'outputText', 'user.username'])
            ->setDefaultSort(['machineAuditTime' => 'DESC'])
            ->setPaginatorPageSize(20);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('user', '用户'))
            ->add(ChoiceFilter::new('machineAuditResult', '机器审核结果')
                ->setChoices($this->getRiskLevelChoices()))
            ->add(ChoiceFilter::new('manualAuditResult', '人工审核结果')
                ->setChoices($this->getAuditResultChoices()))
            ->add(DateTimeFilter::new('machineAuditTime', '机器审核时间'))
            ->add(DateTimeFilter::new('manualAuditTime', '人工审核时间'))
            ->add(TextFilter::new('inputText', '用户输入文本'))
            ->add(TextFilter::new('outputText', 'AI输出文本'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnDetail();
        yield AssociationField::new('user', '用户');
        yield TextareaField::new('inputText', '用户输入文本')
            ->hideOnIndex();
        yield TextareaField::new('outputText', 'AI输出文本')
            ->hideOnIndex();
        yield ChoiceField::new('machineAuditResult', '机器审核结果')
            ->setChoices(array_combine(
                array_map(fn(RiskLevel $level) => $level->getLabel(), RiskLevel::cases()),
                array_map(fn(RiskLevel $level) => $level->value, RiskLevel::cases())
            ))
            ->renderAsBadges([
                '无风险' => 'success',
                '低风险' => 'success',
                '中风险' => 'warning',
                '高风险' => 'danger',
            ]);
        yield DateTimeField::new('machineAuditTime', '机器审核时间');
        yield ChoiceField::new('manualAuditResult', '人工审核结果')
            ->setChoices(array_combine(
                array_map(fn(AuditResult $result) => $result->getLabel(), AuditResult::cases()),
                array_map(fn(AuditResult $result) => $result->value, AuditResult::cases())
            ))
            ->renderAsBadges([
                '通过' => 'success',
                '修改' => 'warning',
                '删除' => 'danger',
            ]);
        yield DateTimeField::new('manualAuditTime', '人工审核时间');
    }

    public function configureActions(Actions $actions): Actions
    {
        $audit = Action::new('audit', '审核', 'fa fa-check')
            ->linkToCrudAction('audit')
            ->displayIf(static function (GeneratedContent $entity) {
                return $entity->needsManualAudit();
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $audit)
            ->add(Crud::PAGE_DETAIL, $audit)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_DETAIL, Action::DELETE)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_SUPER_ADMIN');
    }

    public function audit(EntityManagerInterface $entityManager, int $id): Response
    {
        $content = $this->contentRepository->find($id);

        if ($content === null) {
            throw new NotFoundHttpException('内容不存在');
        }

        // 构建表单视图，显示内容详情并提供审核操作选项
        return $this->render('admin/audit_content.html.twig', [
            'content' => $content,
            'auditOptions' => $this->getAuditResultChoices(),
            'backUrl' => $this->generateUrl('admin', [
                'crudAction' => 'index',
                'crudControllerFqcn' => self::class,
            ]),
        ]);
    }

    /**
     * 提交内容审核
     */
    public function submitAudit(Request $request, int $contentId): Response
    {
        $content = $this->contentRepository->find($contentId);

        if ($content === null) {
            throw new NotFoundHttpException('内容不存在');
        }

        // 获取审核结果
        $auditResultValue = $request->get('auditResult');
        if ($auditResultValue === null) {
            throw new InvalidAuditResultException('审核结果不能为空');
        }

        $auditResult = AuditResult::from($auditResultValue);
        $operator = $this->getUser()?->getUserIdentifier() ?? 'system';

        // 执行人工审核
        $this->contentAuditService->manualAudit($content, $auditResult, $operator);

        // 重定向到列表页面
        return $this->redirectToRoute('admin', [
            'crudAction' => 'index',
            'crudControllerFqcn' => self::class,
        ]);
    }

    /**
     * 获取风险等级选项
     */
    private function getRiskLevelChoices(): array
    {
        $choices = [];
        foreach (RiskLevel::cases() as $riskLevel) {
            $choices[$riskLevel->getLabel()] = $riskLevel->value;
        }
        return $choices;
    }

    /**
     * 获取审核结果选项
     */
    private function getAuditResultChoices(): array
    {
        $choices = [];
        foreach (AuditResult::cases() as $auditResult) {
            $choices[$auditResult->getLabel()] = $auditResult->value;
        }
        return $choices;
    }
}
