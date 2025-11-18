<?php

declare(strict_types=1);

namespace AIContentAuditBundle\Entity;

use AIContentAuditBundle\Enum\AuditResult;
use AIContentAuditBundle\Enum\RiskLevel;
use AIContentAuditBundle\Repository\GeneratedContentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GeneratedContentRepository::class)]
#[ORM\Table(name: 'ims_ai_audit_generated_content', options: ['comment' => 'AI生成内容表'])]
class GeneratedContent implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false, options: ['comment' => '用户ID'])]
    #[Assert\NotBlank(message: '用户ID不能为空')]
    #[Assert\Length(max: 255, maxMessage: '用户ID长度不能超过255个字符')]
    private mixed $user = null;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '用户输入文本'])]
    #[Assert\NotBlank(message: '用户输入文本不能为空')]
    #[Assert\Length(max: 65535, maxMessage: '用户输入文本长度不能超过65535个字符')]
    private ?string $inputText = null;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => 'AI输出文本'])]
    #[Assert\NotBlank(message: 'AI输出文本不能为空')]
    #[Assert\Length(max: 65535, maxMessage: 'AI输出文本长度不能超过65535个字符')]
    private ?string $outputText = null;

    #[ORM\Column(type: Types::STRING, length: 50, enumType: RiskLevel::class, options: ['comment' => '机器审核结果（无风险、低风险、中风险、高风险）'])]
    #[Assert\NotNull(message: '机器审核结果不能为空')]
    #[Assert\Choice(callback: [RiskLevel::class, 'cases'], message: '机器审核结果必须是有效的风险等级')]
    private ?RiskLevel $machineAuditResult = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '机器审核时间'])]
    #[Assert\NotNull(message: '机器审核时间不能为空')]
    private ?\DateTimeImmutable $machineAuditTime = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, enumType: AuditResult::class, options: ['comment' => '人工审核结果（通过、修改、删除）'])]
    #[Assert\Choice(callback: [AuditResult::class, 'cases'], message: '人工审核结果必须是有效的审核结果')]
    private ?AuditResult $manualAuditResult = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '人工审核时间'])]
    #[Assert\Type(type: \DateTimeImmutable::class, message: '人工审核时间必须是有效的时间类型')]
    private ?\DateTimeImmutable $manualAuditTime = null;

    /**
     * @var Collection<int, Report>
     */
    #[ORM\OneToMany(targetEntity: Report::class, mappedBy: 'reportedContent')]
    private Collection $reports;

    public function __construct()
    {
        $this->machineAuditTime = new \DateTimeImmutable();
        $this->reports = new ArrayCollection();
    }

    public function __toString(): string
    {
        return substr($this->inputText ?? '', 0, 30) . '...';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|int|null
     */
    public function getUser(): string|int|null
    {
        // 确保返回值符合声明的类型
        if (is_string($this->user) || is_int($this->user)) {
            return $this->user;
        }

        return null;
    }

    /**
     * @param string|int|null $user
     */
    public function setUser(mixed $user): void
    {
        $this->user = $user;
    }

    public function getInputText(): ?string
    {
        return $this->inputText;
    }

    public function setInputText(string $inputText): void
    {
        $this->inputText = $inputText;
    }

    public function getOutputText(): ?string
    {
        return $this->outputText;
    }

    public function setOutputText(string $outputText): void
    {
        $this->outputText = $outputText;
    }

    public function getMachineAuditResult(): ?RiskLevel
    {
        return $this->machineAuditResult;
    }

    public function setMachineAuditResult(RiskLevel $machineAuditResult): void
    {
        $this->machineAuditResult = $machineAuditResult;
    }

    public function getMachineAuditTime(): ?\DateTimeImmutable
    {
        return $this->machineAuditTime;
    }

    public function setMachineAuditTime(\DateTimeImmutable $machineAuditTime): void
    {
        $this->machineAuditTime = $machineAuditTime;
    }

    public function getManualAuditResult(): ?AuditResult
    {
        return $this->manualAuditResult;
    }

    public function setManualAuditResult(?AuditResult $manualAuditResult): void
    {
        $this->manualAuditResult = $manualAuditResult;
    }

    public function getManualAuditTime(): ?\DateTimeImmutable
    {
        return $this->manualAuditTime;
    }

    public function setManualAuditTime(?\DateTimeImmutable $manualAuditTime): void
    {
        $this->manualAuditTime = $manualAuditTime;
    }

    /**
     * @return Collection<int, Report>
     */
    public function getReports(): Collection
    {
        return $this->reports;
    }

    public function addReport(Report $report): void
    {
        if (!$this->reports->contains($report)) {
            $this->reports->add($report);
            $report->setReportedContent($this);
        }
    }

    public function removeReport(Report $report): void
    {
        if ($this->reports->removeElement($report)) {
            // set the owning side to null (unless already changed)
            if ($report->getReportedContent() === $this) {
                $report->setReportedContent(null);
            }
        }
    }

    /**
     * 检查是否需要人工审核
     */
    public function needsManualAudit(): bool
    {
        return RiskLevel::MEDIUM_RISK === $this->machineAuditResult && null === $this->manualAuditResult;
    }
}
