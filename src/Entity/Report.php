<?php

declare(strict_types=1);

namespace AIContentAuditBundle\Entity;

use AIContentAuditBundle\Enum\ProcessStatus;
use AIContentAuditBundle\Repository\ReportRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReportRepository::class)]
#[ORM\Table(name: 'ims_ai_audit_report', options: ['comment' => '举报表'])]
class Report implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false, options: ['comment' => '举报用户ID'])]
    #[Assert\NotBlank(message: '举报用户ID不能为空')]
    #[Assert\Length(max: 255, maxMessage: '举报用户ID长度不能超过255个字符')]
    private mixed $reporterUser = null;

    #[ORM\ManyToOne(inversedBy: 'reports')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: '被举报内容不能为空')]
    private ?GeneratedContent $reportedContent = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '举报时间'])]
    #[Assert\NotNull(message: '举报时间不能为空')]
    private ?\DateTimeImmutable $reportTime = null;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '举报理由'])]
    #[Assert\NotBlank(message: '举报理由不能为空')]
    #[Assert\Length(max: 65535, maxMessage: '举报理由长度不能超过65535个字符')]
    private ?string $reportReason = null;

    #[ORM\Column(type: Types::STRING, length: 50, enumType: ProcessStatus::class, options: ['comment' => '处理状态（待审核、审核中、已处理）'])]
    #[Assert\NotNull(message: '处理状态不能为空')]
    #[Assert\Choice(callback: [ProcessStatus::class, 'cases'], message: '处理状态必须是有效的处理状态')]
    private ?ProcessStatus $processStatus = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '处理时间'])]
    #[Assert\Type(type: \DateTimeImmutable::class, message: '处理时间必须是有效的时间类型')]
    private ?\DateTimeImmutable $processTime = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '处理结果'])]
    #[Assert\Length(max: 65535, maxMessage: '处理结果长度不能超过65535个字符')]
    private ?string $processResult = null;

    public function __construct()
    {
        $this->reportTime = new \DateTimeImmutable();
        $this->processStatus = ProcessStatus::PENDING;
    }

    public function __toString(): string
    {
        $userId = $this->reporterUser;
        $userDisplay = is_string($userId) || is_int($userId) ? (string) $userId : 'unknown';

        return sprintf('举报ID:%d - 用户:%s', $this->id ?? 0, $userDisplay);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|int|null
     */
    public function getReporterUser(): string|int|null
    {
        // 确保返回值符合声明的类型
        if (is_string($this->reporterUser) || is_int($this->reporterUser)) {
            return $this->reporterUser;
        }

        return null;
    }

    /**
     * @param string|int|null $reporterUser
     */
    public function setReporterUser(mixed $reporterUser): void
    {
        $this->reporterUser = $reporterUser;
    }

    public function getReportedContent(): ?GeneratedContent
    {
        return $this->reportedContent;
    }

    public function setReportedContent(?GeneratedContent $reportedContent): void
    {
        $this->reportedContent = $reportedContent;
    }

    public function getReportTime(): ?\DateTimeImmutable
    {
        return $this->reportTime;
    }

    public function setReportTime(\DateTimeImmutable $reportTime): void
    {
        $this->reportTime = $reportTime;
    }

    public function getReportReason(): ?string
    {
        return $this->reportReason;
    }

    public function setReportReason(string $reportReason): void
    {
        $this->reportReason = $reportReason;
    }

    public function getProcessStatus(): ?ProcessStatus
    {
        return $this->processStatus;
    }

    public function setProcessStatus(ProcessStatus $processStatus): void
    {
        $this->processStatus = $processStatus;
    }

    public function getProcessTime(): ?\DateTimeImmutable
    {
        return $this->processTime;
    }

    public function setProcessTime(?\DateTimeImmutable $processTime): void
    {
        $this->processTime = $processTime;
    }

    public function getProcessResult(): ?string
    {
        return $this->processResult;
    }

    public function setProcessResult(?string $processResult): void
    {
        $this->processResult = $processResult;
    }

    /**
     * 检查是否已处理
     */
    public function isProcessed(): bool
    {
        return ProcessStatus::COMPLETED === $this->processStatus;
    }

    /**
     * 检查是否待处理
     */
    public function isPending(): bool
    {
        return ProcessStatus::PENDING === $this->processStatus;
    }
}
