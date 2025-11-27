<?php

namespace App\Entity;

use App\Repository\MortgageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: MortgageRepository::class)]
class Mortgage
{
    private const SHIP_SHARE_VALUE = 1000000;

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

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private ?bool $signed = null;

    public function __construct()
    {
        $this->setCode(Uuid::v7());
        $this->setSigned(0);
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

    public function setShipShares(?int $shipShares): static
    {
        $this->shipShares = $shipShares;

        return $this;
    }

    public function getAdvancePayment(): ?string
    {
        return $this->advancePayment;
    }

    public function setAdvancePayment(?string $advancePayment): static
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

    public function calculateShipCost()
    {
        $shipCost = 0.00;
        $shipCost = $this->getShip()->getPrice();
        $shipCost = $shipCost - ($this->getShipShares() * self::SHIP_SHARE_VALUE);
        if ($this->getAdvancePayment()) {
            $shipCost = $shipCost - $this->getAdvancePayment();
        }

        if ($this->getDiscount()) {
            $discount = $this->getShip()->getPrice() * $this->getDiscount() / 100;
            $shipCost = $shipCost - $discount;
        }

        return $shipCost;

    }

    public function calculate()
    {
        $shipCost = $this->calculateShipCost();
        $monthlyPayment =
            $shipCost
            * $this->getInterestRate()->getPriceMultiplier()
            / $this->getInterestRate()->getDuration()
            / 12
        ;

        $annualPayment = $monthlyPayment * 12;

        $insuranceMonthlyPayment = 0.00;
        $insuranceAnnualPayment = 0.00;
        if ($this->getInsurance()) {
            $insuranceMonthlyPayment = $this->calculateInsuranceCost();
            $insuranceAnnualPayment = $insuranceMonthlyPayment * 12;
        }

        $totalMonthlyPayment = $monthlyPayment + $insuranceMonthlyPayment;
        $totalAnnualPayment = $annualPayment + $insuranceAnnualPayment;

        $totalMortgage = $shipCost * $this->getInterestRate()->getPriceMultiplier();

        return [
            'ship_cost' => round($shipCost, 2, PHP_ROUND_HALF_DOWN),
            'mortgage_monthly' => round($monthlyPayment, 2, PHP_ROUND_HALF_DOWN),
            'mortgage_annual' => round($annualPayment, 2 , PHP_ROUND_HALF_DOWN),
            'insurance_monthly' => round($insuranceMonthlyPayment, 2 , PHP_ROUND_HALF_DOWN),
            'insurance_annual' => round($insuranceAnnualPayment, 2, PHP_ROUND_HALF_DOWN),
            'total_monthly_payment' => round($totalMonthlyPayment, 2, PHP_ROUND_HALF_DOWN),
            'total_annual_payment' => round($totalAnnualPayment, 2, PHP_ROUND_HALF_DOWN),
            'total_mortgage' => round($totalMortgage, 2, PHP_ROUND_HALF_DOWN),
        ];
    }

    public function calculateInsuranceCost()
    {
        return $this->getShip()->getPrice()
            / 100
            * $this->getInsurance()->getAnnualCost()
            / 12
        ;
    }

    public function isSigned(): ?bool
    {
        return $this->signed;
    }

    public function setSigned(bool $signed): static
    {
        $this->signed = $signed;

        return $this;
    }
}
