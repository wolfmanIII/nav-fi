<?php

namespace App\Entity;

use App\Repository\IncomePassengersDetailsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IncomePassengersDetailsRepository::class)]
class IncomePassengersDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private ?Income $income = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $origin = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $destination = null;

    #[ORM\Column(nullable: true)]
    private ?int $departureDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $departureYear = null;

    #[ORM\Column(nullable: true)]
    private ?int $arrivalDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $arrivalYear = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $classOrBerth = null;

    #[ORM\Column(nullable: true)]
    private ?int $qty = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $passengerNames = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $passengerContact = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $baggageAllowance = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $extraBaggage = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 2, nullable: true)]
    private ?string $fareTotal = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $paymentTerms = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $refundChangePolicy = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIncome(): ?Income
    {
        return $this->income;
    }

    public function setIncome(Income $income): static
    {
        $this->income = $income;

        return $this;
    }

    public function getOrigin(): ?string
    {
        return $this->origin;
    }

    public function setOrigin(?string $origin): static
    {
        $this->origin = $origin;

        return $this;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(?string $destination): static
    {
        $this->destination = $destination;

        return $this;
    }

    public function getDepartureDay(): ?int
    {
        return $this->departureDay;
    }

    public function setDepartureDay(?int $departureDay): static
    {
        $this->departureDay = $departureDay;

        return $this;
    }

    public function getDepartureYear(): ?int
    {
        return $this->departureYear;
    }

    public function setDepartureYear(?int $departureYear): static
    {
        $this->departureYear = $departureYear;

        return $this;
    }

    public function getArrivalDay(): ?int
    {
        return $this->arrivalDay;
    }

    public function setArrivalDay(?int $arrivalDay): static
    {
        $this->arrivalDay = $arrivalDay;

        return $this;
    }

    public function getArrivalYear(): ?int
    {
        return $this->arrivalYear;
    }

    public function setArrivalYear(?int $arrivalYear): static
    {
        $this->arrivalYear = $arrivalYear;

        return $this;
    }

    public function getClassOrBerth(): ?string
    {
        return $this->classOrBerth;
    }

    public function setClassOrBerth(?string $classOrBerth): static
    {
        $this->classOrBerth = $classOrBerth;

        return $this;
    }

    public function getQty(): ?int
    {
        return $this->qty;
    }

    public function setQty(?int $qty): static
    {
        $this->qty = $qty;

        return $this;
    }

    public function getPassengerNames(): ?string
    {
        return $this->passengerNames;
    }

    public function setPassengerNames(?string $passengerNames): static
    {
        $this->passengerNames = $passengerNames;

        return $this;
    }

    public function getPassengerContact(): ?string
    {
        return $this->passengerContact;
    }

    public function setPassengerContact(?string $passengerContact): static
    {
        $this->passengerContact = $passengerContact;

        return $this;
    }

    public function getBaggageAllowance(): ?string
    {
        return $this->baggageAllowance;
    }

    public function setBaggageAllowance(?string $baggageAllowance): static
    {
        $this->baggageAllowance = $baggageAllowance;

        return $this;
    }

    public function getExtraBaggage(): ?string
    {
        return $this->extraBaggage;
    }

    public function setExtraBaggage(?string $extraBaggage): static
    {
        $this->extraBaggage = $extraBaggage;

        return $this;
    }

    public function getFareTotal(): ?string
    {
        return $this->fareTotal;
    }

    public function setFareTotal(?string $fareTotal): static
    {
        $this->fareTotal = $fareTotal;

        return $this;
    }

    public function getPaymentTerms(): ?string
    {
        return $this->paymentTerms;
    }

    public function setPaymentTerms(?string $paymentTerms): static
    {
        $this->paymentTerms = $paymentTerms;

        return $this;
    }

    public function getRefundChangePolicy(): ?string
    {
        return $this->refundChangePolicy;
    }

    public function setRefundChangePolicy(?string $refundChangePolicy): static
    {
        $this->refundChangePolicy = $refundChangePolicy;

        return $this;
    }
}
