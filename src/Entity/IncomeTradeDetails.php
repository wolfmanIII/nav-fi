<?php

namespace App\Entity;

use App\Repository\IncomeTradeDetailsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IncomeTradeDetailsRepository::class)]
class IncomeTradeDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private ?Income $income = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $transferPoint = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $transferCondition = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $goodsDescription = null;

    #[ORM\Column(nullable: true)]
    private ?int $qty = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $grade = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $batchIds = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 2, nullable: true)]
    private ?string $unitPrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 2, nullable: true)]
    private ?string $paymentTerms = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $deliveryMethod = null;

    #[ORM\Column(nullable: true)]
    private ?int $deliveryDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $deliveryYear = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $asIsOrWarranty = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $warrantyText = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $claimWindow = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $returnPolicy = null;

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

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getTransferPoint(): ?string
    {
        return $this->transferPoint;
    }

    public function setTransferPoint(?string $transferPoint): static
    {
        $this->transferPoint = $transferPoint;
        return $this;
    }

    public function getTransferCondition(): ?string
    {
        return $this->transferCondition;
    }

    public function setTransferCondition(?string $transferCondition): static
    {
        $this->transferCondition = $transferCondition;
        return $this;
    }

    public function getGoodsDescription(): ?string
    {
        return $this->goodsDescription;
    }

    public function setGoodsDescription(?string $goodsDescription): static
    {
        $this->goodsDescription = $goodsDescription;
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

    public function getGrade(): ?string
    {
        return $this->grade;
    }

    public function setGrade(?string $grade): static
    {
        $this->grade = $grade;
        return $this;
    }

    public function getBatchIds(): ?string
    {
        return $this->batchIds;
    }

    public function setBatchIds(?string $batchIds): static
    {
        $this->batchIds = $batchIds;
        return $this;
    }

    public function getUnitPrice(): ?string
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(?string $unitPrice): static
    {
        $this->unitPrice = $unitPrice;
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

    public function getDeliveryMethod(): ?string
    {
        return $this->deliveryMethod;
    }

    public function setDeliveryMethod(?string $deliveryMethod): static
    {
        $this->deliveryMethod = $deliveryMethod;
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

    public function getAsIsOrWarranty(): ?string
    {
        return $this->asIsOrWarranty;
    }

    public function setAsIsOrWarranty(?string $asIsOrWarranty): static
    {
        $this->asIsOrWarranty = $asIsOrWarranty;
        return $this;
    }

    public function getWarrantyText(): ?string
    {
        return $this->warrantyText;
    }

    public function setWarrantyText(?string $warrantyText): static
    {
        $this->warrantyText = $warrantyText;
        return $this;
    }

    public function getClaimWindow(): ?string
    {
        return $this->claimWindow;
    }

    public function setClaimWindow(?string $claimWindow): static
    {
        $this->claimWindow = $claimWindow;
        return $this;
    }

    public function getReturnPolicy(): ?string
    {
        return $this->returnPolicy;
    }

    public function setReturnPolicy(?string $returnPolicy): static
    {
        $this->returnPolicy = $returnPolicy;
        return $this;
    }
}
