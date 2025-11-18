<?php

namespace App\Entity;

use App\Repository\MortgageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MortgageRepository::class)]
class Mortgage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 36)]
    private ?string $code = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'mortgages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Ship $ship = null;

    #[ORM\Column]
    private ?int $startDay = null;

    #[ORM\Column]
    private ?int $startYear = null;

    #[ORM\Column(nullable: true)]
    private ?int $shipShares = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 2, nullable: true)]
    private ?string $advancePayment = null;

    #[ORM\ManyToOne(inversedBy: 'mortgages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?InterestRate $interestRate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 2, nullable: true)]
    private ?string $discount = null;

    #[ORM\ManyToOne(inversedBy: 'mortgages')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Insurance $insurance = null;

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    public function getStartDay(): ?int
    {
        return $this->startDay;
    }

    public function setStartDay(int $startDay): static
    {
        $this->startDay = $startDay;

        return $this;
    }

    public function getStartYear(): ?int
    {
        return $this->startYear;
    }

    public function setStartYear(int $startYear): static
    {
        $this->startYear = $startYear;

        return $this;
    }

    public function getShipShares(): ?int
    {
        return $this->shipShares;
    }

    public function setShipShares(int $shipShares): static
    {
        $this->shipShares = $shipShares;

        return $this;
    }

    public function getAdvancePayment(): ?string
    {
        return $this->advancePayment;
    }

    public function setAdvancePayment(string $advancePayment): static
    {
        $this->advancePayment = $advancePayment;

        return $this;
    }

    public function getInterestRate(): ?InterestRate
    {
        return $this->interestRate;
    }

    public function setInterestRate(?InterestRate $interestRate): static
    {
        $this->interestRate = $interestRate;

        return $this;
    }

    public function getDiscount(): ?string
    {
        return $this->discount;
    }

    public function setDiscount(?string $discount): static
    {
        $this->discount = $discount;

        return $this;
    }

    public function getInsurance(): ?Insurance
    {
        return $this->insurance;
    }

    public function setInsurance(?Insurance $insurance): static
    {
        $this->insurance = $insurance;

        return $this;
    }
}
