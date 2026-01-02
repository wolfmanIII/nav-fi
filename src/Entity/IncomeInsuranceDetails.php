<?php

namespace App\Entity;

use App\Repository\IncomeInsuranceDetailsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IncomeInsuranceDetailsRepository::class)]
class IncomeInsuranceDetails
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
    private ?string $policyNumber = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $incidentRef = null;

    #[ORM\Column(nullable: true)]
    private ?int $incidentDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $incidentYear = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $incidentLocation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $incidentCause = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lossType = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 2, nullable: true)]
    private ?string $verifiedLoss = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 2, nullable: true)]
    private ?string $deductible = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $paymentTerms = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $acceptanceEffect = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $subrogationTerms = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $coverageNotes = null;

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

    public function getPolicyNumber(): ?string
    {
        return $this->policyNumber;
    }

    public function setPolicyNumber(?string $policyNumber): static
    {
        $this->policyNumber = $policyNumber;
        return $this;
    }

    public function getIncidentRef(): ?string
    {
        return $this->incidentRef;
    }

    public function setIncidentRef(?string $incidentRef): static
    {
        $this->incidentRef = $incidentRef;
        return $this;
    }

    public function getIncidentDay(): ?int
    {
        return $this->incidentDay;
    }

    public function setIncidentDay(?int $incidentDay): static
    {
        $this->incidentDay = $incidentDay;
        return $this;
    }

    public function getIncidentYear(): ?int
    {
        return $this->incidentYear;
    }

    public function setIncidentYear(?int $incidentYear): static
    {
        $this->incidentYear = $incidentYear;
        return $this;
    }

    public function getIncidentLocation(): ?string
    {
        return $this->incidentLocation;
    }

    public function setIncidentLocation(?string $incidentLocation): static
    {
        $this->incidentLocation = $incidentLocation;
        return $this;
    }

    public function getIncidentCause(): ?string
    {
        return $this->incidentCause;
    }

    public function setIncidentCause(?string $incidentCause): static
    {
        $this->incidentCause = $incidentCause;
        return $this;
    }

    public function getLossType(): ?string
    {
        return $this->lossType;
    }

    public function setLossType(?string $lossType): static
    {
        $this->lossType = $lossType;
        return $this;
    }

    public function getVerifiedLoss(): ?string
    {
        return $this->verifiedLoss;
    }

    public function setVerifiedLoss(?string $verifiedLoss): static
    {
        $this->verifiedLoss = $verifiedLoss;
        return $this;
    }

    public function getDeductible(): ?string
    {
        return $this->deductible;
    }

    public function setDeductible(?string $deductible): static
    {
        $this->deductible = $deductible;
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

    public function getAcceptanceEffect(): ?string
    {
        return $this->acceptanceEffect;
    }

    public function setAcceptanceEffect(?string $acceptanceEffect): static
    {
        $this->acceptanceEffect = $acceptanceEffect;
        return $this;
    }

    public function getSubrogationTerms(): ?string
    {
        return $this->subrogationTerms;
    }

    public function setSubrogationTerms(?string $subrogationTerms): static
    {
        $this->subrogationTerms = $subrogationTerms;
        return $this;
    }

    public function getCoverageNotes(): ?string
    {
        return $this->coverageNotes;
    }

    public function setCoverageNotes(?string $coverageNotes): static
    {
        $this->coverageNotes = $coverageNotes;
        return $this;
    }
}
