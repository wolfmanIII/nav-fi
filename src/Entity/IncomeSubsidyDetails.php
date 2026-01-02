<?php

namespace App\Entity;

use App\Repository\IncomeSubsidyDetailsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IncomeSubsidyDetailsRepository::class)]
class IncomeSubsidyDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'subsidyDetails')]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private ?Income $income = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $programRef = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $origin = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $destination = null;

    #[ORM\Column(nullable: true)]
    private ?int $startDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $startYear = null;

    #[ORM\Column(nullable: true)]
    private ?int $endDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $endYear = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $serviceLevel = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 2, nullable: true)]
    private ?string $subsidyAmount = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $paymentTerms = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $milestones = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reportingRequirements = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $nonComplianceTerms = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $proofRequirements = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $cancellationTerms = null;

    // Getters/setters
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

    public function getProgramRef(): ?string
    {
        return $this->programRef;
    }

    public function setProgramRef(?string $programRef): static
    {
        $this->programRef = $programRef;
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

    public function getServiceLevel(): ?string
    {
        return $this->serviceLevel;
    }

    public function setServiceLevel(?string $serviceLevel): static
    {
        $this->serviceLevel = $serviceLevel;
        return $this;
    }

    public function getSubsidyAmount(): ?string
    {
        return $this->subsidyAmount;
    }

    public function setSubsidyAmount(?string $subsidyAmount): static
    {
        $this->subsidyAmount = $subsidyAmount;
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

    public function getMilestones(): ?string
    {
        return $this->milestones;
    }

    public function setMilestones(?string $milestones): static
    {
        $this->milestones = $milestones;
        return $this;
    }

    public function getReportingRequirements(): ?string
    {
        return $this->reportingRequirements;
    }

    public function setReportingRequirements(?string $reportingRequirements): static
    {
        $this->reportingRequirements = $reportingRequirements;
        return $this;
    }

    public function getNonComplianceTerms(): ?string
    {
        return $this->nonComplianceTerms;
    }

    public function setNonComplianceTerms(?string $nonComplianceTerms): static
    {
        $this->nonComplianceTerms = $nonComplianceTerms;
        return $this;
    }

    public function getProofRequirements(): ?string
    {
        return $this->proofRequirements;
    }

    public function setProofRequirements(?string $proofRequirements): static
    {
        $this->proofRequirements = $proofRequirements;
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
