<?php

namespace App\Entity;

use App\Repository\MortgageInstallmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: MortgageInstallmentRepository::class)]
class MortgageInstallment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 36)]
    private ?string $code = null;

    #[ORM\Column]
    private ?int $paymentDay = null;

    #[ORM\Column]
    private ?int $paymentYear = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $payment = null;

    #[ORM\ManyToOne(inversedBy: 'mortgageRates')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Mortgage $mortgage = null;

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

    public function getPaymentDay(): ?int
    {
        return $this->paymentDay;
    }

    public function setPaymentDay(int $paymentDay): static
    {
        $this->paymentDay = $paymentDay;

        return $this;
    }

    public function getPaymentYear(): ?int
    {
        return $this->paymentYear;
    }

    public function setPaymentYear(int $paymentYear): static
    {
        $this->paymentYear = $paymentYear;

        return $this;
    }

    public function getPayment(): ?string
    {
        return $this->payment;
    }

    public function setPayment(string $payment): static
    {
        $this->payment = $payment;

        return $this;
    }

    public function getMortgage(): ?Mortgage
    {
        return $this->mortgage;
    }

    public function setMortgage(?Mortgage $mortgage): static
    {
        $this->mortgage = $mortgage;

        return $this;
    }
}
