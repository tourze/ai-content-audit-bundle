<?php

namespace AIContentAuditBundle\Entity;

use AIContentAuditBundle\Enum\ProcessStatus;
use AIContentAuditBundle\Repository\ReportRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: ReportRepository::class)]
#[ORM\Table(name: 'ims_ai_audit_report', options: ['comment' => '举报表'])]
class Report implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?UserInterface $reporterUser = null;

    #[ORM\ManyToOne(inversedBy: 'reports')]
    #[ORM\JoinColumn(nullable: false)]
    private ?GeneratedContent $reportedContent = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '举报时间'])]
    private ?\DateTimeImmutable $reportTime = null;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '举报理由'])]
    private ?string $reportReason = null;

    #[ORM\Column(type: Types::STRING, length: 50, enumType: ProcessStatus::class, options: ['comment' => '处理状态（待审核、审核中、已处理）'])]
    private ?ProcessStatus $processStatus = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '处理时间'])]
    private ?\DateTimeImmutable $processTime = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '处理结果'])]
    private ?string $processResult = null;

    public function __construct()
    {
        $this->reportTime = new \DateTimeImmutable();
        $this->processStatus = ProcessStatus::PENDING;
    }

    public function __toString(): string
    {
        return sprintf('举报ID:%d - 用户:%s', $this->id ?? 0, $this->reporterUser ?? 'unknown');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReporterUser(): ?UserInterface
    {
        return $this->reporterUser;
    }

    public function setReporterUser(?UserInterface $reporterUser): static
    {
        $this->reporterUser = $reporterUser;

        return $this;
    }

    public function getReportedContent(): ?GeneratedContent
    {
        return $this->reportedContent;
    }

    public function setReportedContent(?GeneratedContent $reportedContent): static
    {
        $this->reportedContent = $reportedContent;

        return $this;
    }

    public function getReportTime(): ?\DateTimeImmutable
    {
        return $this->reportTime;
    }

    public function setReportTime(\DateTimeImmutable $reportTime): static
    {
        $this->reportTime = $reportTime;

        return $this;
    }

    public function getReportReason(): ?string
    {
        return $this->reportReason;
    }

    public function setReportReason(string $reportReason): static
    {
        $this->reportReason = $reportReason;

        return $this;
    }

    public function getProcessStatus(): ?ProcessStatus
    {
        return $this->processStatus;
    }

    public function setProcessStatus(ProcessStatus $processStatus): static
    {
        $this->processStatus = $processStatus;

        return $this;
    }

    public function getProcessTime(): ?\DateTimeImmutable
    {
        return $this->processTime;
    }

    public function setProcessTime(?\DateTimeImmutable $processTime): static
    {
        $this->processTime = $processTime;

        return $this;
    }

    public function getProcessResult(): ?string
    {
        return $this->processResult;
    }

    public function setProcessResult(?string $processResult): static
    {
        $this->processResult = $processResult;

        return $this;
    }

    /**
     * 检查是否已处理
     */
    public function isProcessed(): bool
    {
        return $this->processStatus === ProcessStatus::COMPLETED;
    }

    /**
     * 检查是否待处理
     */
    public function isPending(): bool
    {
        return $this->processStatus === ProcessStatus::PENDING;
    }
}
