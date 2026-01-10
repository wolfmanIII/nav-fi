<?php

namespace App\Entity;

use App\Repository\IncomeFreightDetailsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IncomeFreightDetailsRepository::class)]
class IncomeFreightDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'freightDetails')]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private ?Income $income = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $origin = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $destination = null;

    #[ORM\Column(nullable: true)]
    private ?int $pickupDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $pickupYear = null;

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

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $cargoDescription = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $cargoQty = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 2, nullable: true)]
    private ?string $declaredValue = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $paymentTerms = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 2, nullable: true)]
    private ?string $liabilityLimit = null;

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

    public function getPickupDay(): ?int
    {
        return $this->pickupDay;
    }

    public function setPickupDay(?int $pickupDay): static
    {
        $this->pickupDay = $pickupDay;

        return $this;
    }

    public function getPickupYear(): ?int
    {
        return $this->pickupYear;
    }

    public function setPickupYear(?int $pickupYear): static
    {
        $this->pickupYear = $pickupYear;

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

    public function getCargoDescription(): ?string
    {
        return $this->cargoDescription;
    }

    public function setCargoDescription(?string $cargoDescription): static
    {
        $this->cargoDescription = $cargoDescription;

        return $this;
    }

    public function getCargoQty(): ?string
    {
        return $this->cargoQty;
    }

    public function setCargoQty(?string $cargoQty): static
    {
        $this->cargoQty = $cargoQty;

        return $this;
    }

    public function getDeclaredValue(): ?string
    {
        return $this->declaredValue;
    }

    public function setDeclaredValue(?string $declaredValue): static
    {
        $this->declaredValue = $declaredValue;

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

    public function getLiabilityLimit(): ?string
    {
        return $this->liabilityLimit;
    }

    public function setLiabilityLimit(?string $liabilityLimit): static
    {
        $this->liabilityLimit = $liabilityLimit;

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
}
