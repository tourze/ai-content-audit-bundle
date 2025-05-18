<?php

namespace AIContentAuditBundle\Entity;

use AIContentAuditBundle\Enum\RiskLevel;
use AIContentAuditBundle\Repository\RiskKeywordRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RiskKeywordRepository::class)]
#[ORM\Table(name: 'ims_ai_audit_risk_keyword', options: ['comment' => '风险关键词库表'])]
#[ORM\Index(name: 'idx_risk_keyword_keyword', fields: ['keyword'])]
class RiskKeyword implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, options: ['comment' => '风险关键词'])]
    private ?string $keyword = null;

    #[ORM\Column(type: Types::STRING, length: 50, enumType: RiskLevel::class, options: ['comment' => '关键词对应的风险等级（低、中、高）'])]
    private ?RiskLevel $riskLevel = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '关键词更新时间'])]
    private ?\DateTimeImmutable $updateTime = null;

    #[ORM\Column(length: 255, nullable: true, options: ['comment' => '关键词分类'])]
    private ?string $category = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '说明'])]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true, options: ['comment' => '添加人'])]
    private ?string $addedBy = null;

    public function __construct()
    {
        $this->updateTime = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return $this->keyword ?? '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKeyword(): ?string
    {
        return $this->keyword;
    }

    public function setKeyword(string $keyword): static
    {
        $this->keyword = $keyword;

        return $this;
    }

    public function getRiskLevel(): ?RiskLevel
    {
        return $this->riskLevel;
    }

    public function setRiskLevel(RiskLevel $riskLevel): static
    {
        $this->riskLevel = $riskLevel;

        return $this;
    }

    public function getUpdateTime(): ?\DateTimeImmutable
    {
        return $this->updateTime;
    }

    public function setUpdateTime(\DateTimeImmutable $updateTime): static
    {
        $this->updateTime = $updateTime;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getAddedBy(): ?string
    {
        return $this->addedBy;
    }

    public function setAddedBy(?string $addedBy): static
    {
        $this->addedBy = $addedBy;

        return $this;
    }
} 