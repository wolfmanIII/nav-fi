<?php

namespace App\Entity;

use App\Repository\IncomeRepository;
use App\Entity\LocalLaw;
use App\Entity\IncomeCharterDetails;
use App\Entity\IncomeSubsidyDetails;
use App\Entity\IncomeFreightDetails;
use App\Entity\IncomePassengersDetails;
use App\Entity\IncomeServicesDetails;
use App\Entity\IncomeInsuranceDetails;
use App\Entity\IncomeMailDetails;
use App\Entity\IncomeInterestDetails;
use App\Entity\IncomeTradeDetails;
use App\Entity\IncomeSalvageDetails;
use App\Entity\IncomePrizeDetails;
use App\Entity\IncomeContractDetails;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: IncomeRepository::class)]
#[ORM\Index(name: 'idx_income_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_income_asset', columns: ['asset_id'])]
#[ORM\Index(name: 'idx_income_category', columns: ['income_category_id'])]
class Income
{
    public const STATUS_DRAFT = 'Draft';
    public const STATUS_SIGNED = 'Signed';
    public const STATUS_CANCELLED = 'Cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 36)]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $patronAlias = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[ORM\Column(nullable: true)]
    private ?int $signingDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $signingYear = null;

    #[ORM\Column(nullable: true)]
    private ?int $paymentDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $paymentYear = null;

    #[ORM\Column(nullable: true)]
    private ?int $cancelDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $cancelYear = null;

    #[ORM\Column(nullable: true)]
    private ?int $expirationDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $expirationYear = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Company $company = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?LocalLaw $localLaw = null;

    #[ORM\OneToOne(mappedBy: 'income', cascade: ['persist', 'remove'])]
    private ?IncomeCharterDetails $charterDetails = null;

    #[ORM\OneToOne(mappedBy: 'income', cascade: ['persist', 'remove'])]
    private ?IncomeSubsidyDetails $subsidyDetails = null;

    #[ORM\OneToOne(mappedBy: 'income', cascade: ['persist', 'remove'])]
    private ?IncomeFreightDetails $freightDetails = null;

    #[ORM\OneToOne(mappedBy: 'income', cascade: ['persist', 'remove'])]
    private ?IncomePassengersDetails $passengersDetails = null;

    #[ORM\OneToOne(mappedBy: 'income', cascade: ['persist', 'remove'])]
    private ?IncomeServicesDetails $servicesDetails = null;

    #[ORM\OneToOne(mappedBy: 'income', cascade: ['persist', 'remove'])]
    private ?IncomeInsuranceDetails $insuranceDetails = null;

    #[ORM\OneToOne(mappedBy: 'income', cascade: ['persist', 'remove'])]
    private ?IncomeMailDetails $mailDetails = null;

    #[ORM\OneToOne(mappedBy: 'income', cascade: ['persist', 'remove'])]
    private ?IncomeInterestDetails $interestDetails = null;

    #[ORM\OneToOne(mappedBy: 'income', cascade: ['persist', 'remove'])]
    private ?IncomeTradeDetails $tradeDetails = null;

    #[ORM\OneToOne(mappedBy: 'income', cascade: ['persist', 'remove'])]
    private ?IncomeSalvageDetails $salvageDetails = null;

    #[ORM\OneToOne(mappedBy: 'income', cascade: ['persist', 'remove'])]
    private ?IncomePrizeDetails $prizeDetails = null;

    #[ORM\OneToOne(mappedBy: 'income', cascade: ['persist', 'remove'])]
    private ?IncomeContractDetails $contractDetails = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $signingLocation = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?IncomeCategory $incomeCategory = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Asset $asset = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    public function __construct()
    {
        $this->setCode(Uuid::v7());
        $this->status = self::STATUS_DRAFT;
    }

    public function getCharterDetails(): ?IncomeCharterDetails
    {
        return $this->charterDetails;
    }

    public function setCharterDetails(?IncomeCharterDetails $charterDetails): static
    {
        $this->charterDetails = $charterDetails;
        if ($charterDetails && $charterDetails->getIncome() !== $this) {
            $charterDetails->setIncome($this);
        }

        return $this;
    }

    public function getSubsidyDetails(): ?IncomeSubsidyDetails
    {
        return $this->subsidyDetails;
    }

    public function setSubsidyDetails(?IncomeSubsidyDetails $subsidyDetails): static
    {
        $this->subsidyDetails = $subsidyDetails;
        if ($subsidyDetails && $subsidyDetails->getIncome() !== $this) {
            $subsidyDetails->setIncome($this);
        }

        return $this;
    }

    public function getFreightDetails(): ?IncomeFreightDetails
    {
        return $this->freightDetails;
    }

    public function setFreightDetails(?IncomeFreightDetails $freightDetails): static
    {
        $this->freightDetails = $freightDetails;
        if ($freightDetails && $freightDetails->getIncome() !== $this) {
            $freightDetails->setIncome($this);
        }

        return $this;
    }

    public function getPassengersDetails(): ?IncomePassengersDetails
    {
        return $this->passengersDetails;
    }

    public function setPassengersDetails(?IncomePassengersDetails $passengersDetails): static
    {
        $this->passengersDetails = $passengersDetails;
        if ($passengersDetails && $passengersDetails->getIncome() !== $this) {
            $passengersDetails->setIncome($this);
        }

        return $this;
    }

    public function getServicesDetails(): ?IncomeServicesDetails
    {
        return $this->servicesDetails;
    }

    public function setServicesDetails(?IncomeServicesDetails $servicesDetails): static
    {
        $this->servicesDetails = $servicesDetails;
        if ($servicesDetails && $servicesDetails->getIncome() !== $this) {
            $servicesDetails->setIncome($this);
        }

        return $this;
    }

    public function getInsuranceDetails(): ?IncomeInsuranceDetails
    {
        return $this->insuranceDetails;
    }

    public function setInsuranceDetails(?IncomeInsuranceDetails $insuranceDetails): static
    {
        $this->insuranceDetails = $insuranceDetails;
        if ($insuranceDetails && $insuranceDetails->getIncome() !== $this) {
            $insuranceDetails->setIncome($this);
        }

        return $this;
    }

    public function getMailDetails(): ?IncomeMailDetails
    {
        return $this->mailDetails;
    }

    public function setMailDetails(?IncomeMailDetails $mailDetails): static
    {
        $this->mailDetails = $mailDetails;
        if ($mailDetails && $mailDetails->getIncome() !== $this) {
            $mailDetails->setIncome($this);
        }

        return $this;
    }

    public function getInterestDetails(): ?IncomeInterestDetails
    {
        return $this->interestDetails;
    }

    public function setInterestDetails(?IncomeInterestDetails $interestDetails): static
    {
        $this->interestDetails = $interestDetails;
        if ($interestDetails && $interestDetails->getIncome() !== $this) {
            $interestDetails->setIncome($this);
        }

        return $this;
    }

    public function getTradeDetails(): ?IncomeTradeDetails
    {
        return $this->tradeDetails;
    }

    public function setTradeDetails(?IncomeTradeDetails $tradeDetails): static
    {
        $this->tradeDetails = $tradeDetails;
        if ($tradeDetails && $tradeDetails->getIncome() !== $this) {
            $tradeDetails->setIncome($this);
        }

        return $this;
    }

    public function getSalvageDetails(): ?IncomeSalvageDetails
    {
        return $this->salvageDetails;
    }

    public function setSalvageDetails(?IncomeSalvageDetails $salvageDetails): static
    {
        $this->salvageDetails = $salvageDetails;
        if ($salvageDetails && $salvageDetails->getIncome() !== $this) {
            $salvageDetails->setIncome($this);
        }

        return $this;
    }

    public function getPrizeDetails(): ?IncomePrizeDetails
    {
        return $this->prizeDetails;
    }

    public function setPrizeDetails(?IncomePrizeDetails $prizeDetails): static
    {
        $this->prizeDetails = $prizeDetails;
        if ($prizeDetails && $prizeDetails->getIncome() !== $this) {
            $prizeDetails->setIncome($this);
        }

        return $this;
    }

    public function getContractDetails(): ?IncomeContractDetails
    {
        return $this->contractDetails;
    }

    public function setContractDetails(?IncomeContractDetails $contractDetails): static
    {
        $this->contractDetails = $contractDetails;
        if ($contractDetails && $contractDetails->getIncome() !== $this) {
            $contractDetails->setIncome($this);
        }

        return $this;
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getPatronAlias(): ?string
    {
        return $this->patronAlias;
    }

    public function setPatronAlias(?string $patronAlias): static
    {
        $this->patronAlias = $patronAlias;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function isSigned(): bool
    {
        return $this->status === self::STATUS_SIGNED;
    }

    public function getSigningDay(): ?int
    {
        return $this->signingDay;
    }

    public function setSigningDay(?int $signingDay): static
    {
        $this->signingDay = $signingDay;

        return $this;
    }

    public function getSigningYear(): ?int
    {
        return $this->signingYear;
    }

    public function setSigningYear(?int $signingYear): static
    {
        $this->signingYear = $signingYear;

        return $this;
    }

    public function getPaymentDay(): ?int
    {
        return $this->paymentDay;
    }

    public function setPaymentDay(?int $paymentDay): static
    {
        $this->paymentDay = $paymentDay;

        return $this;
    }

    public function getPaymentYear(): ?int
    {
        return $this->paymentYear;
    }

    public function setPaymentYear(?int $paymentYear): static
    {
        $this->paymentYear = $paymentYear;

        return $this;
    }

    public function getCancelDay(): ?int
    {
        return $this->cancelDay;
    }

    public function setCancelDay(?int $cancelDay): static
    {
        $this->cancelDay = $cancelDay;

        return $this;
    }

    public function getCancelYear(): ?int
    {
        return $this->cancelYear;
    }

    public function setCancelYear(?int $cancelYear): static
    {
        $this->cancelYear = $cancelYear;

        return $this;
    }

    public function getExpirationDay(): ?int
    {
        return $this->expirationDay;
    }

    public function setExpirationDay(?int $expirationDay): static
    {
        $this->expirationDay = $expirationDay;

        return $this;
    }

    public function getExpirationYear(): ?int
    {
        return $this->expirationYear;
    }

    public function setExpirationYear(?int $expirationYear): static
    {
        $this->expirationYear = $expirationYear;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getIncomeCategory(): ?IncomeCategory
    {
        return $this->incomeCategory;
    }

    public function setIncomeCategory(?IncomeCategory $incomeCategory): static
    {
        $this->incomeCategory = $incomeCategory;

        return $this;
    }

    public function getAsset(): ?Asset
    {
        return $this->asset;
    }

    public function setAsset(?Asset $asset): static
    {
        $this->asset = $asset;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function getLocalLaw(): ?LocalLaw
    {
        return $this->localLaw;
    }

    public function setLocalLaw(?LocalLaw $localLaw): static
    {
        $this->localLaw = $localLaw;

        return $this;
    }

    public function getSigningLocation(): ?string
    {
        return $this->signingLocation;
    }

    public function setSigningLocation(?string $signingLocation): static
    {
        $this->signingLocation = $signingLocation;

        return $this;
    }

    public function isCancelled(): bool
    {
        return $this->cancelDay !== null && $this->cancelYear !== null;
    }
}
