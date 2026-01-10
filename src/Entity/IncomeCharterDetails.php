<?php

namespace App\Entity;

use App\Repository\IncomeCharterDetailsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IncomeCharterDetailsRepository::class)]
class IncomeCharterDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'charterDetails')]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private ?Income $income = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $areaOrRoute = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $purpose = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $manifestSummary = null;

    #[ORM\Column(nullable: true)]
    private ?int $startDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $startYear = null;

    #[ORM\Column(nullable: true)]
    private ?int $endDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $endYear = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $deliveryProofRef = null;

    #[ORM\Column(nullable: true)]
    private ?int $deliveryProofDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $deliveryProofYear = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $deliveryProofReceivedBy = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $paymentTerms = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 2, nullable: true)]
    private ?string $deposit = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $extras = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $damageTerms = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $cancellationTerms = null;

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

    public function getAreaOrRoute(): ?string
    {
        return $this->areaOrRoute;
    }

    public function setAreaOrRoute(?string $areaOrRoute): static
    {
        $this->areaOrRoute = $areaOrRoute;

        return $this;
    }

    public function getPurpose(): ?string
    {
        return $this->purpose;
    }

    public function setPurpose(?string $purpose): static
    {
        $this->purpose = $purpose;

        return $this;
    }

    public function getManifestSummary(): ?string
    {
        return $this->manifestSummary;
    }

    public function setManifestSummary(?string $manifestSummary): static
    {
        $this->manifestSummary = $manifestSummary;

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

    public function getDeposit(): ?string
    {
        return $this->deposit;
    }

    public function setDeposit(?string $deposit): static
    {
        $this->deposit = $deposit;

        return $this;
    }

    public function getExtras(): ?string
    {
        return $this->extras;
    }

    public function setExtras(?string $extras): static
    {
        $this->extras = $extras;

        return $this;
    }

    public function getDamageTerms(): ?string
    {
        return $this->damageTerms;
    }

    public function setDamageTerms(?string $damageTerms): static
    {
        $this->damageTerms = $damageTerms;

        return $this;
    }

    public function getCancellationTerms(): ?string
    {
        return $this->cancellationTerms;
    }

    public function setCancellationTerms(?string $cancellationTerms): static
    {
        $this->cancellationTerms = $cancellationTerms;

        return $this;
    }

    public function getStartDay(): ?int
    {
        return $this->startDay;
    }

    public function setStartDay(?int $startDay): static
    {
        $this->startDay = $startDay;

        return $this;
    }

    public function getStartYear(): ?int
    {
        return $this->startYear;
    }

    public function setStartYear(?int $startYear): static
    {
        $this->startYear = $startYear;

        return $this;
    }

    public function getEndDay(): ?int
    {
        return $this->endDay;
    }

    public function setEndDay(?int $endDay): static
    {
        $this->endDay = $endDay;

        return $this;
    }

    public function getEndYear(): ?int
    {
        return $this->endYear;
    }

    public function setEndYear(?int $endYear): static
    {
        $this->endYear = $endYear;

        return $this;
    }

    public function getDeliveryProofRef(): ?string
    {
        return $this->deliveryProofRef;
    }

    public function setDeliveryProofRef(?string $deliveryProofRef): static
    {
        $this->deliveryProofRef = $deliveryProofRef;

        return $this;
    }

    public function getDeliveryProofDay(): ?int
    {
        return $this->deliveryProofDay;
    }

    public function setDeliveryProofDay(?int $deliveryProofDay): static
    {
        $this->deliveryProofDay = $deliveryProofDay;

        return $this;
    }

    public function getDeliveryProofYear(): ?int
    {
        return $this->deliveryProofYear;
    }

    public function setDeliveryProofYear(?int $deliveryProofYear): static
    {
        $this->deliveryProofYear = $deliveryProofYear;

        return $this;
    }

    public function getDeliveryProofReceivedBy(): ?string
    {
        return $this->deliveryProofReceivedBy;
    }

    public function setDeliveryProofReceivedBy(?string $deliveryProofReceivedBy): static
    {
        $this->deliveryProofReceivedBy = $deliveryProofReceivedBy;

        return $this;
    }
}
