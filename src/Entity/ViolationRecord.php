<?php

declare(strict_types=1);

namespace AIContentAuditBundle\Entity;

use AIContentAuditBundle\Enum\ViolationType;
use AIContentAuditBundle\Repository\ViolationRecordRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ViolationRecordRepository::class)]
#[ORM\Table(name: 'ims_ai_audit_violation_record', options: ['comment' => '违规记录表'])]
class ViolationRecord implements \Stringable
{
    /**
     * @var int|null
     * @phpstan-var positive-int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['comment' => '主键ID'])]
    private ?int $id;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false, options: ['comment' => '违规用户ID'])]
    #[Assert\NotBlank(message: '违规用户ID不能为空')]
    #[Assert\Length(max: 255, maxMessage: '违规用户ID长度不能超过255个字符')]
    private mixed $user = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '违规时间'])]
    #[Assert\NotNull(message: '违规时间不能为空')]
    private ?\DateTimeImmutable $violationTime = null;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '违规内容'])]
    #[Assert\NotBlank(message: '违规内容不能为空')]
    #[Assert\Length(max: 65535, maxMessage: '违规内容长度不能超过65535个字符')]
    private ?string $violationContent = null;

    #[ORM\Column(type: Types::STRING, length: 50, enumType: ViolationType::class, options: ['comment' => '违规类型'])]
    #[Assert\NotNull(message: '违规类型不能为空')]
    #[Assert\Choice(callback: [ViolationType::class, 'cases'], message: '违规类型必须是有效的违规类型')]
    private ?ViolationType $violationType = null;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '处理结果（如删除内容、标记用户、封号等）'])]
    #[Assert\NotBlank(message: '处理结果不能为空')]
    #[Assert\Length(max: 65535, maxMessage: '处理结果长度不能超过65535个字符')]
    private ?string $processResult = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '处理时间'])]
    #[Assert\NotNull(message: '处理时间不能为空')]
    private ?\DateTimeImmutable $processTime = null;

    #[ORM\Column(length: 255, options: ['comment' => '处理人员'])]
    #[Assert\NotBlank(message: '处理人员不能为空')]
    #[Assert\Length(max: 255, maxMessage: '处理人员长度不能超过255个字符')]
    private ?string $processedBy = null;

    public function __construct()
    {
        $this->violationTime = new \DateTimeImmutable();
        $this->processTime = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        $userId = $this->user;
        $userDisplay = is_string($userId) || is_int($userId) ? (string) $userId : 'unknown';

        return sprintf('违规ID:%d - 用户:%s - 类型:%s',
            $this->id ?? 0,
            $userDisplay,
            $this->violationType?->getLabel() ?? '未知'
        );
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

    public function getViolationTime(): ?\DateTimeImmutable
    {
        return $this->violationTime;
    }

    public function setViolationTime(\DateTimeImmutable $violationTime): void
    {
        $this->violationTime = $violationTime;
    }

    public function getViolationContent(): ?string
    {
        return $this->violationContent;
    }

    public function setViolationContent(string $violationContent): void
    {
        $this->violationContent = $violationContent;
    }

    public function getViolationType(): ?ViolationType
    {
        return $this->violationType;
    }

    public function setViolationType(ViolationType $violationType): void
    {
        $this->violationType = $violationType;
    }

    public function getProcessResult(): ?string
    {
        return $this->processResult;
    }

    public function setProcessResult(string $processResult): void
    {
        $this->processResult = $processResult;
    }

    public function getProcessTime(): ?\DateTimeImmutable
    {
        return $this->processTime;
    }

    public function setProcessTime(\DateTimeImmutable $processTime): void
    {
        $this->processTime = $processTime;
    }

    public function getProcessedBy(): ?string
    {
        return $this->processedBy;
    }

    public function setProcessedBy(string $processedBy): void
    {
        $this->processedBy = $processedBy;
    }
}
