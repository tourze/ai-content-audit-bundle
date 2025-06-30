<?php

namespace AIContentAuditBundle\Entity;

use AIContentAuditBundle\Enum\AuditResult;
use AIContentAuditBundle\Enum\RiskLevel;
use AIContentAuditBundle\Repository\GeneratedContentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GeneratedContentRepository::class)]
#[ORM\Table(name: 'ims_ai_audit_generated_content', options: ['comment' => 'AI生成内容表'])]
class GeneratedContent implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false, options: ['comment' => '用户ID'])]
    private int|string|null $user = null;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '用户输入文本'])]
    private ?string $inputText = null;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => 'AI输出文本'])]
    private ?string $outputText = null;

    #[ORM\Column(type: Types::STRING, length: 50, enumType: RiskLevel::class, options: ['comment' => '机器审核结果（无风险、低风险、中风险、高风险）'])]
    private ?RiskLevel $machineAuditResult = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '机器审核时间'])]
    private ?\DateTimeImmutable $machineAuditTime = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, enumType: AuditResult::class, options: ['comment' => '人工审核结果（通过、修改、删除）'])]
    private ?AuditResult $manualAuditResult = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '人工审核时间'])]
    private ?\DateTimeImmutable $manualAuditTime = null;

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

    public function getUser(): int|string|null
    {
        return $this->user;
    }

    public function setUser(int|string|null $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getInputText(): ?string
    {
        return $this->inputText;
    }

    public function setInputText(string $inputText): static
    {
        $this->inputText = $inputText;

        return $this;
    }

    public function getOutputText(): ?string
    {
        return $this->outputText;
    }

    public function setOutputText(string $outputText): static
    {
        $this->outputText = $outputText;

        return $this;
    }

    public function getMachineAuditResult(): ?RiskLevel
    {
        return $this->machineAuditResult;
    }

    public function setMachineAuditResult(RiskLevel $machineAuditResult): static
    {
        $this->machineAuditResult = $machineAuditResult;

        return $this;
    }

    public function getMachineAuditTime(): ?\DateTimeImmutable
    {
        return $this->machineAuditTime;
    }

    public function setMachineAuditTime(\DateTimeImmutable $machineAuditTime): static
    {
        $this->machineAuditTime = $machineAuditTime;

        return $this;
    }

    public function getManualAuditResult(): ?AuditResult
    {
        return $this->manualAuditResult;
    }

    public function setManualAuditResult(?AuditResult $manualAuditResult): static
    {
        $this->manualAuditResult = $manualAuditResult;

        return $this;
    }

    public function getManualAuditTime(): ?\DateTimeImmutable
    {
        return $this->manualAuditTime;
    }

    public function setManualAuditTime(?\DateTimeImmutable $manualAuditTime): static
    {
        $this->manualAuditTime = $manualAuditTime;

        return $this;
    }

    /**
     * @return Collection<int, Report>
     */
    public function getReports(): Collection
    {
        return $this->reports;
    }

    public function addReport(Report $report): static
    {
        if (!$this->reports->contains($report)) {
            $this->reports->add($report);
            $report->setReportedContent($this);
        }

        return $this;
    }

    public function removeReport(Report $report): static
    {
        if ($this->reports->removeElement($report)) {
            // set the owning side to null (unless already changed)
            if ($report->getReportedContent() === $this) {
                $report->setReportedContent(null);
            }
        }

        return $this;
    }

    /**
     * 检查是否需要人工审核
     */
    public function needsManualAudit(): bool
    {
        return $this->machineAuditResult === RiskLevel::MEDIUM_RISK && $this->manualAuditResult === null;
    }
}
