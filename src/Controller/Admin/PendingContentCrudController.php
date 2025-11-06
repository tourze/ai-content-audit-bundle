<?php

declare(strict_types=1);

namespace AIContentAuditBundle\Controller\Admin;

use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Enum\AuditResult;
use AIContentAuditBundle\Enum\RiskLevel;
use AIContentAuditBundle\Exception\EntityNotFoundException;
use AIContentAuditBundle\Exception\InvalidRepositoryArgumentException;
use AIContentAuditBundle\Service\ContentAuditService;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
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
 * @extends AbstractCrudController<GeneratedContent>
 */
#[AdminCrud(
    routePath: '/ai-content-audit/pending-content',
    routeName: 'ai_content_audit_pending_content',
)]
#[IsGranted(attribute: 'ROLE_ADMIN')]
final class PendingContentCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly ContentAuditService $contentAuditService,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return GeneratedContent::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('待审核内容')
            ->setEntityLabelInPlural('待审核内容')
            ->setSearchFields(['inputText', 'outputText', 'user'])
            ->setDefaultSort(['machineAuditTime' => 'ASC'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('user', '用户'))
            ->add(DateTimeFilter::new('machineAuditTime', '机器审核时间'))
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
                array_map(fn (RiskLevel $level) => $level->value, RiskLevel::cases())
            ))
            ->renderAsBadges([
                '无风险' => 'success',
                '低风险' => 'success',
                '中风险' => 'warning',
                '高风险' => 'danger',
            ])
        ;
        yield DateTimeField::new('machineAuditTime', '机器审核时间');
        yield ChoiceField::new('manualAuditResult', '人工审核结果')
            ->setChoices(array_combine(
                array_map(fn (AuditResult $result) => $result->getLabel(), AuditResult::cases()),
                array_map(fn (AuditResult $result) => $result->value, AuditResult::cases())
            ))
            ->renderAsBadges([
                '通过' => 'success',
                '修改' => 'warning',
                '删除' => 'danger',
            ])
        ;
        yield DateTimeField::new('manualAuditTime', '人工审核时间');
    }

    public function configureActions(Actions $actions): Actions
    {
        // 审核动作 - 直接在待审核列表页面进行审核
        $quickAuditPass = Action::new('quickAuditPass', '通过', 'fa fa-check')
            ->linkToCrudAction('quickAuditPass')
            ->setCssClass('btn btn-success btn-sm')
            ->displayIf(static function (GeneratedContent $entity) {
                return null === $entity->getManualAuditResult();
            })
        ;

        $quickAuditReject = Action::new('quickAuditReject', '拒绝', 'fa fa-times')
            ->linkToCrudAction('quickAuditReject')
            ->setCssClass('btn btn-danger btn-sm')
            ->displayIf(static function (GeneratedContent $entity) {
                return null === $entity->getManualAuditResult();
            })
        ;

        $detailAudit = Action::new('detailAudit', '详细审核', 'fa fa-eye')
            ->linkToCrudAction('detailAudit')
            ->setCssClass('btn btn-info btn-sm')
            ->displayIf(static function (GeneratedContent $entity) {
                return null === $entity->getManualAuditResult();
            })
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, $quickAuditPass)
            ->add(Crud::PAGE_INDEX, $quickAuditReject)
            ->add(Crud::PAGE_INDEX, $detailAudit)
            ->add(Crud::PAGE_DETAIL, $quickAuditPass)
            ->add(Crud::PAGE_DETAIL, $quickAuditReject)
            ->disable(Action::NEW)
            ->disable(Action::EDIT)
            ->disable(Action::DELETE)
        ;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        // 只在INDEX页面应用过滤条件：只显示中风险且未人工审核的内容
        $adminContext = $this->getContext();
        $crud = $adminContext?->getCrud();
        if (null !== $crud && Crud::PAGE_INDEX === $crud->getCurrentPage()) {
            $queryBuilder
                ->andWhere('entity.machineAuditResult = :risk')
                ->andWhere('entity.manualAuditResult IS NULL')
                ->setParameter('risk', RiskLevel::MEDIUM_RISK->value)
                ->orderBy('entity.machineAuditTime', 'ASC')
            ;
        }

        return $queryBuilder;
    }

    public function find(mixed $entityId): ?GeneratedContent
    {
        // 直接使用 EntityManager 查找实体，绕过查询构建器的过滤条件
        /** @var Registry $doctrine */
        $doctrine = $this->container->get('doctrine');
        $entityManager = $doctrine->getManagerForClass(GeneratedContent::class);
        if (null === $entityManager) {
            return null;
        }
        $entity = $entityManager->find(GeneratedContent::class, $entityId);

        return $entity instanceof GeneratedContent ? $entity : null;
    }

    /**
     * 快速审核通过
     */
    #[AdminAction(routePath: '/quick-audit-pass', routeName: 'quick_audit_pass')]
    public function quickAuditPass(AdminContext $context): RedirectResponse
    {
        $content = $context->getEntity()->getInstance();
        assert($content instanceof GeneratedContent);

        // 执行审核通过
        $operator = $this->getUser()?->getUserIdentifier() ?? 'system';
        $this->contentAuditService->manualAudit($content, AuditResult::PASS, $operator);

        $this->addFlash('success', '内容审核通过');

        return $this->redirect($context->getRequest()->headers->get('referer') ?? $this->generateUrl('admin'));
    }

    /**
     * 快速审核拒绝
     */
    #[AdminAction(routeName: 'quick_audit_reject', routePath: '/quick-audit-reject')]
    public function quickAuditReject(AdminContext $context): RedirectResponse
    {
        $content = $context->getEntity()->getInstance();
        assert($content instanceof GeneratedContent);

        // 执行审核拒绝（删除）
        $operator = $this->getUser()?->getUserIdentifier() ?? 'system';
        $this->contentAuditService->manualAudit($content, AuditResult::DELETE, $operator);

        $this->addFlash('success', '内容已标记为删除');

        return $this->redirect($context->getRequest()->headers->get('referer') ?? $this->generateUrl('admin'));
    }

    /**
     * 审核动作 - 跳转到详情页面查看完整内容
     */
    #[AdminAction(routePath: '{entityId}/audit', routeName: 'audit')]
    public function audit(Request $request): RedirectResponse
    {
        $entityIdValue = $request->attributes->get('entityId') ?? $request->query->get('entityId');
        assert(is_string($entityIdValue) || is_numeric($entityIdValue), 'Entity ID must be numeric');
        $entityId = (int) $entityIdValue;
        if ($entityId <= 0) {
            throw new \EasyCorp\Bundle\EasyAdminBundle\Exception\EntityNotFoundException();
        }

        // 使用 AdminUrlGenerator 生成正确的后台 URL（适配自定义后台前缀/多Dashboard）
        $url = clone $this->adminUrlGenerator;
        $targetUrl = $url
            ->unsetAll()
            ->setController(GeneratedContentCrudController::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($entityId)
            ->generateUrl()
        ;

        return $this->redirect($targetUrl);
    }

    /**
     * 详细审核 - 跳转到详情页面查看完整内容
     */
    #[AdminAction(routePath: '{entityId}/detail-audit', routeName: 'detail_audit')]
    public function detailAudit(Request $request): RedirectResponse
    {
        $entityIdValue = $request->attributes->get('entityId') ?? $request->query->get('entityId');
        assert(is_string($entityIdValue) || is_numeric($entityIdValue), 'Entity ID must be numeric');
        $entityId = (int) $entityIdValue;
        if ($entityId <= 0) {
            throw new \EasyCorp\Bundle\EasyAdminBundle\Exception\EntityNotFoundException();
        }

        $url = clone $this->adminUrlGenerator;
        $targetUrl = $url
            ->unsetAll()
            ->setController(GeneratedContentCrudController::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($entityId)
            ->generateUrl()
        ;

        return $this->redirect($targetUrl);
    }
}
