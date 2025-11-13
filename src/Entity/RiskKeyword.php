<?php

declare(strict_types=1);

namespace AIContentAuditBundle\Entity;

use AIContentAuditBundle\Enum\RiskLevel;
use AIContentAuditBundle\Repository\RiskKeywordRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;

#[ORM\Entity(repositoryClass: RiskKeywordRepository::class)]
#[ORM\Table(name: 'ims_ai_audit_risk_keyword', options: ['comment' => '风险关键词库表'])]
class RiskKeyword implements \Stringable
{
    /**
     * @var int|null
     * @phpstan-var positive-int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['comment' => '主键ID'])]
    private ?int $id;

    #[ORM\Column(length: 100, options: ['comment' => '风险关键词'])]
    #[Assert\NotBlank(message: '风险关键词不能为空')]
    #[Assert\Length(max: 100, maxMessage: '风险关键词长度不能超过100个字符')]
    #[IndexColumn]
    private ?string $keyword = null;

    #[ORM\Column(type: Types::STRING, length: 50, enumType: RiskLevel::class, options: ['comment' => '关键词对应的风险等级（低、中、高）'])]
    #[Assert\NotNull(message: '风险等级不能为空')]
    #[Assert\Choice(callback: [RiskLevel::class, 'cases'], message: '风险等级必须是有效的风险等级')]
    private ?RiskLevel $riskLevel = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '关键词更新时间'])]
    #[Assert\NotNull(message: '更新时间不能为空')]
    private ?\DateTimeImmutable $updateTime = null;

    #[ORM\Column(length: 255, nullable: true, options: ['comment' => '关键词分类'])]
    #[Assert\Length(max: 255, maxMessage: '关键词分类长度不能超过255个字符')]
    private ?string $category = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '说明'])]
    #[Assert\Length(max: 65535, maxMessage: '说明长度不能超过65535个字符')]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true, options: ['comment' => '添加人'])]
    #[Assert\Length(max: 255, maxMessage: '添加人长度不能超过255个字符')]
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

    public function setKeyword(string $keyword): void
    {
        $this->keyword = $keyword;
    }

    public function getRiskLevel(): ?RiskLevel
    {
        return $this->riskLevel;
    }

    public function setRiskLevel(RiskLevel $riskLevel): void
    {
        $this->riskLevel = $riskLevel;
    }

    public function getUpdateTime(): ?\DateTimeImmutable
    {
        return $this->updateTime;
    }

    public function setUpdateTime(\DateTimeImmutable $updateTime): void
    {
        $this->updateTime = $updateTime;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): void
    {
        $this->category = $category;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getAddedBy(): ?string
    {
        return $this->addedBy;
    }

    public function setAddedBy(?string $addedBy): void
    {
        $this->addedBy = $addedBy;
    }
}
