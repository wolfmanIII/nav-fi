<?php

namespace App\Entity;

use App\Repository\IncomeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: IncomeRepository::class)]
class Income
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 36)]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column]
    private ?int $signingDay = null;

    #[ORM\Column]
    private ?int $signingYear = null;

    #[ORM\Column(nullable: true)]
    private ?int $paymentDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $paymentYear = null;

    #[ORM\Column(nullable: true)]
    private ?int $cancelDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $cancelYear = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?IncomeCategory $incomeCategory = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Ship $ship = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    public function __construct()
    {
        $this->setCode(Uuid::v7());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getSigningDay(): ?int
    {
        return $this->signingDay;
    }

    public function setSigningDay(int $signingDay): static
    {
        $this->signingDay = $signingDay;

        return $this;
    }

    public function getSigningYear(): ?int
    {
        return $this->signingYear;
    }

    public function setSigningYear(int $signingYear): static
    {
        $this->signingYear = $signingYear;

        return $this;
    }

    public function getPaymentDay(): ?int
    {
        return $this->paymentDay;
    }

    public function setPaymentDay(?int $paymentDay): static
    {
        $this->paymentDay = $paymentDay;

        return $this;
    }

    public function getPaymentYear(): ?int
    {
        return $this->paymentYear;
    }

    public function setPaymentYear(?int $paymentYear): static
    {
        $this->paymentYear = $paymentYear;

        return $this;
    }

    public function getCancelDay(): ?int
    {
        return $this->cancelDay;
    }

    public function setCancelDay(?int $cancelDay): static
    {
        $this->cancelDay = $cancelDay;

        return $this;
    }

    public function getCancelYear(): ?int
    {
        return $this->cancelYear;
    }

    public function setCancelYear(?int $cancelYear): static
    {
        $this->cancelYear = $cancelYear;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getIncomeCategory(): ?IncomeCategory
    {
        return $this->incomeCategory;
    }

    public function setIncomeCategory(?IncomeCategory $incomeCategory): static
    {
        $this->incomeCategory = $incomeCategory;

        return $this;
    }

    public function getShip(): ?Ship
    {
        return $this->ship;
    }

    public function setShip(?Ship $ship): static
    {
        $this->ship = $ship;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
