<?php

namespace App\Entity;

use App\Repository\IncomeSalvageDetailsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IncomeSalvageDetailsRepository::class)]
class IncomeSalvageDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private ?Income $income = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $claimId = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $caseRef = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $source = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $siteLocation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $recoveredItemsSummary = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 2, nullable: true)]
    private ?string $qtyValue = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $hazards = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $paymentTerms = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $splitTerms = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rightsBasis = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $awardTrigger = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $disputeProcess = null;

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

    public function getClaimId(): ?string
    {
        return $this->claimId;
    }

    public function setClaimId(?string $claimId): static
    {
        $this->claimId = $claimId;
        return $this;
    }

    public function getCaseRef(): ?string
    {
        return $this->caseRef;
    }

    public function setCaseRef(?string $caseRef): static
    {
        $this->caseRef = $caseRef;
        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): static
    {
        $this->source = $source;
        return $this;
    }

    public function getSiteLocation(): ?string
    {
        return $this->siteLocation;
    }

    public function setSiteLocation(?string $siteLocation): static
    {
        $this->siteLocation = $siteLocation;
        return $this;
    }

    public function getRecoveredItemsSummary(): ?string
    {
        return $this->recoveredItemsSummary;
    }

    public function setRecoveredItemsSummary(?string $recoveredItemsSummary): static
    {
        $this->recoveredItemsSummary = $recoveredItemsSummary;
        return $this;
    }

    public function getQtyValue(): ?string
    {
        return $this->qtyValue;
    }

    public function setQtyValue(?string $qtyValue): static
    {
        $this->qtyValue = $qtyValue;
        return $this;
    }

    public function getHazards(): ?string
    {
        return $this->hazards;
    }

    public function setHazards(?string $hazards): static
    {
        $this->hazards = $hazards;
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

    public function getSplitTerms(): ?string
    {
        return $this->splitTerms;
    }

    public function setSplitTerms(?string $splitTerms): static
    {
        $this->splitTerms = $splitTerms;
        return $this;
    }

    public function getRightsBasis(): ?string
    {
        return $this->rightsBasis;
    }

    public function setRightsBasis(?string $rightsBasis): static
    {
        $this->rightsBasis = $rightsBasis;
        return $this;
    }

    public function getAwardTrigger(): ?string
    {
        return $this->awardTrigger;
    }

    public function setAwardTrigger(?string $awardTrigger): static
    {
        $this->awardTrigger = $awardTrigger;
        return $this;
    }

    public function getDisputeProcess(): ?string
    {
        return $this->disputeProcess;
    }

    public function setDisputeProcess(?string $disputeProcess): static
    {
        $this->disputeProcess = $disputeProcess;
        return $this;
    }
}
