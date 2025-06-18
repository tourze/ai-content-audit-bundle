<?php

namespace AIContentAuditBundle\Entity;

use AIContentAuditBundle\Enum\ViolationType;
use AIContentAuditBundle\Repository\ViolationRecordRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: ViolationRecordRepository::class)]
#[ORM\Table(name: 'ims_ai_audit_violation_record', options: ['comment' => '违规记录表'])]
class ViolationRecord implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?UserInterface $user = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '违规时间'])]
    private ?\DateTimeImmutable $violationTime = null;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '违规内容'])]
    private ?string $violationContent = null;

    #[ORM\Column(type: Types::STRING, length: 50, enumType: ViolationType::class, options: ['comment' => '违规类型'])]
    private ?ViolationType $violationType = null;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '处理结果（如删除内容、标记用户、封号等）'])]
    private ?string $processResult = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '处理时间'])]
    private ?\DateTimeImmutable $processTime = null;

    #[ORM\Column(length: 255, options: ['comment' => '处理人员'])]
    private ?string $processedBy = null;

    public function __construct()
    {
        $this->violationTime = new \DateTimeImmutable();
        $this->processTime = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return sprintf('违规ID:%d - 用户:%s - 类型:%s',
            $this->id ?? 0,
            $this->user?->getUserIdentifier() ?? 'unknown',
            $this->violationType?->getLabel() ?? '未知'
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getViolationTime(): ?\DateTimeImmutable
    {
        return $this->violationTime;
    }

    public function setViolationTime(\DateTimeImmutable $violationTime): static
    {
        $this->violationTime = $violationTime;

        return $this;
    }

    public function getViolationContent(): ?string
    {
        return $this->violationContent;
    }

    public function setViolationContent(string $violationContent): static
    {
        $this->violationContent = $violationContent;

        return $this;
    }

    public function getViolationType(): ?ViolationType
    {
        return $this->violationType;
    }

    public function setViolationType(ViolationType $violationType): static
    {
        $this->violationType = $violationType;

        return $this;
    }

    public function getProcessResult(): ?string
    {
        return $this->processResult;
    }

    public function setProcessResult(string $processResult): static
    {
        $this->processResult = $processResult;

        return $this;
    }

    public function getProcessTime(): ?\DateTimeImmutable
    {
        return $this->processTime;
    }

    public function setProcessTime(\DateTimeImmutable $processTime): static
    {
        $this->processTime = $processTime;

        return $this;
    }

    public function getProcessedBy(): ?string
    {
        return $this->processedBy;
    }

    public function setProcessedBy(string $processedBy): static
    {
        $this->processedBy = $processedBy;

        return $this;
    }
}
