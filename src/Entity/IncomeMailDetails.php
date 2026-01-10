<?php

namespace App\Entity;

use App\Repository\IncomeMailDetailsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IncomeMailDetailsRepository::class)]
class IncomeMailDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'mailDetails')]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private ?Income $income = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $origin = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $destination = null;

    #[ORM\Column(nullable: true)]
    private ?int $dispatchDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $dispatchYear = null;

    #[ORM\Column(nullable: true)]
    private ?int $deliveryDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $deliveryYear = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $deliveryProofRef = null;

    #[ORM\Column(nullable: true)]
    private ?int $deliveryProofDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $deliveryProofYear = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $deliveryProofReceivedBy = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mailType = null;

    #[ORM\Column(nullable: true)]
    private ?int $packageCount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 2, nullable: true)]
    private ?string $totalMass = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $securityLevel = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sealCodes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $paymentTerms = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $proofOfDelivery = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 2, nullable: true)]
    private ?string $liabilityLimit = null;

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

    public function getDispatchDay(): ?int
    {
        return $this->dispatchDay;
    }

    public function setDispatchDay(?int $dispatchDay): static
    {
        $this->dispatchDay = $dispatchDay;
        return $this;
    }

    public function getDispatchYear(): ?int
    {
        return $this->dispatchYear;
    }

    public function setDispatchYear(?int $dispatchYear): static
    {
        $this->dispatchYear = $dispatchYear;
        return $this;
    }

    public function getDeliveryDay(): ?int
    {
        return $this->deliveryDay;
    }

    public function setDeliveryDay(?int $deliveryDay): static
    {
        $this->deliveryDay = $deliveryDay;
        return $this;
    }

    public function getDeliveryYear(): ?int
    {
        return $this->deliveryYear;
    }

    public function setDeliveryYear(?int $deliveryYear): static
    {
        $this->deliveryYear = $deliveryYear;
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

    public function getMailType(): ?string
    {
        return $this->mailType;
    }

    public function setMailType(?string $mailType): static
    {
        $this->mailType = $mailType;
        return $this;
    }

    public function getPackageCount(): ?int
    {
        return $this->packageCount;
    }

    public function setPackageCount(?int $packageCount): static
    {
        $this->packageCount = $packageCount;
        return $this;
    }

    public function getTotalMass(): ?string
    {
        return $this->totalMass;
    }

    public function setTotalMass(?string $totalMass): static
    {
        $this->totalMass = $totalMass;
        return $this;
    }

    public function getSecurityLevel(): ?string
    {
        return $this->securityLevel;
    }

    public function setSecurityLevel(?string $securityLevel): static
    {
        $this->securityLevel = $securityLevel;
        return $this;
    }

    public function getSealCodes(): ?string
    {
        return $this->sealCodes;
    }

    public function setSealCodes(?string $sealCodes): static
    {
        $this->sealCodes = $sealCodes;
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

    public function getProofOfDelivery(): ?string
    {
        return $this->proofOfDelivery;
    }

    public function setProofOfDelivery(?string $proofOfDelivery): static
    {
        $this->proofOfDelivery = $proofOfDelivery;
        return $this;
    }

    public function getLiabilityLimit(): ?string
    {
        return $this->liabilityLimit;
    }

    public function setLiabilityLimit(?string $liabilityLimit): static
    {
        $this->liabilityLimit = $liabilityLimit;
        return $this;
    }
}
