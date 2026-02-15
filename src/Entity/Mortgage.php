<?php

namespace App\Entity;

use App\Repository\MortgageRepository;
use App\Entity\LocalLaw;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: MortgageRepository::class)]
#[ORM\Index(name: 'idx_mortgage_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_mortgage_asset', columns: ['asset_id'])]
class Mortgage
{
    public const ASSET_SHARE_VALUE = 1000000; // Mantenuto nome costante per compatibilità regole di business

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::GUID)]
    private ?string $code = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\OneToOne(inversedBy: 'mortgage', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Asset $asset = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?FinancialAccount $financialAccount = null;

    #[ORM\Column(nullable: true)]
    private ?int $startDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $startYear = null;

    #[ORM\Column(nullable: true)]
    private ?int $assetShares = null; // Mantenuto nome campo

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

    #[ORM\Column(nullable: true)]
    private ?int $signingDay = null;

    #[ORM\Column(nullable: true)]
    private ?int $signingYear = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $signingLocation = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Company $company = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?LocalLaw $localLaw = null;

    /**
     * @var Collection<int, MortgageInstallment>
     */
    #[ORM\OneToMany(targetEntity: MortgageInstallment::class, mappedBy: 'mortgage', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $mortgageInstallments;

    public function __construct()
    {
        $this->setCode(Uuid::v7()->toRfc4122());

        $this->mortgageInstallments = new ArrayCollection();
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

    public function getAsset(): ?Asset
    {
        return $this->asset;
    }

    public function setAsset(?Asset $asset): static
    {
        $this->asset = $asset;

        // assicura che il lato inverso sia sincronizzato
        if ($asset !== null && $asset->getMortgage() !== $this) {
            $asset->setMortgage($this);
        }

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

    public function getAssetShares(): ?int
    {
        return $this->assetShares;
    }

    public function setAssetShares(?int $assetShares): static
    {
        $this->assetShares = $assetShares;

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

    public function getSigningLocation(): ?string
    {
        return $this->signingLocation;
    }

    public function setSigningLocation(?string $signingLocation): static
    {
        $this->signingLocation = $signingLocation;

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

    public function calculateAssetCost(): string
    {
        $assetPrice = $this->normalizeAmount($this->getAsset()?->getPrice());
        $assetSharesValue = bcmul((string)($this->getAssetShares() ?? 0), (string)self::ASSET_SHARE_VALUE, 4);

        $assetCost = bcsub($assetPrice, $assetSharesValue, 6);

        if ($this->getAdvancePayment()) {
            $assetCost = bcsub($assetCost, $this->normalizeAmount($this->getAdvancePayment()), 6);
        }

        if ($this->getDiscount()) {
            $discount = bcmul($assetPrice, bcdiv($this->normalizeAmount($this->getDiscount()), '100', 4), 6);
            $assetCost = bcsub($assetCost, $discount, 6);
        }

        return bcadd($assetCost, '0', 6);
    }

    public function calculateMonthlyPayment(): string
    {
        $assetPrice = $this->normalizeAmount($this->getAsset()?->getPrice());
        $rate = $this->getInterestRate();

        // 1. Calcola Base (1% del Prezzo Asset)
        $base = bcdiv($assetPrice, '100', 6);

        // 2. Aggiusta Base con moltiplicatore/divisore
        // Formula: Base * Moltiplicatore / Divisore
        if ($rate) {
            $multiplier = $rate->getPriceMultiplier() ?? '1.0';
            $divider = $rate->getPriceDivider() ?? 1;

            if ($divider == 0) $divider = 1;

            // Calcolo interessi basato sull'importo netto finanziato (prezzo asset meno acconti e sconti).
            $assetCost = $this->calculateAssetCost();

            // Applichiamo i termini del tasso (moltiplicatore/divisore) al capitale finanziato.
            // Nota: Le procedure imperiali TAS confermano che il capitale residuo è la base per il piano di ammortamento.

            // Usiamo la logica dell'entità tasso:
            $totalMortgage = bcmul($assetCost, $multiplier, 6);
            $totalMortgage = bcdiv($totalMortgage, (string)$divider, 6);

            $duration = $rate->getDuration() ?? 240;
            if ($duration <= 0) $duration = 1;

            return bcdiv($totalMortgage, (string)$duration, 6);
        }

        return '0.00';
    }

    public function calculateInsuranceCost(): string
    {
        $assetPrice = $this->normalizeAmount($this->getAsset()?->getPrice());
        $annualCost = $this->normalizeAmount($this->getInsurance()?->getAnnualCost());

        $base = bcdiv($assetPrice, '100', 6);
        $annualPayment = bcmul($base, $annualCost, 6);

        return bcdiv($annualPayment, '13', 6);
    }

    public function calculate(): array
    {
        $assetCost = $this->calculateAssetCost();
        $multiplier = $this->normalizeAmount($this->getInterestRate()?->getPriceMultiplier());
        $duration = $this->getInterestRate()?->getDuration() ?? 1;

        $monthlyPayment = bcdiv(
            bcdiv(
                bcmul($assetCost, $multiplier, 6),
                (string)$duration,
                6
            ),
            '13',
            6
        );

        $annualPayment = bcmul($monthlyPayment, '13', 6);

        $insuranceMonthlyPayment = '0.00';
        $insuranceAnnualPayment = '0.00';
        if ($this->getInsurance()) {
            $insuranceMonthlyPayment = $this->calculateInsuranceCost();
            $insuranceAnnualPayment = bcmul($insuranceMonthlyPayment, '13', 6);
        }

        $totalMonthlyPayment = bcadd($monthlyPayment, $insuranceMonthlyPayment, 6);
        $totalAnnualPayment = bcadd($annualPayment, $insuranceAnnualPayment, 6);

        $totalMortgage = bcmul($assetCost, $multiplier, 6);

        $totalMortgagePaid = '0.00';
        foreach ($this->getMortgageInstallments() as $installment) {
            $totalMortgagePaid = bcadd(
                $totalMortgagePaid,
                $this->normalizeAmount($installment->getPayment()),
                6
            );
        }

        return [
            'asset_cost' => $this->roundAmount($assetCost),
            'mortgage_monthly' => $this->roundAmount($monthlyPayment),
            'mortgage_annual' => $this->roundAmount($annualPayment),
            'insurance_monthly' => $this->roundAmount($insuranceMonthlyPayment),
            'insurance_annual' => $this->roundAmount($insuranceAnnualPayment),
            'total_monthly_payment' => $this->roundAmount($totalMonthlyPayment),
            'total_annual_payment' => $this->roundAmount($totalAnnualPayment),
            'total_mortgage' => $this->roundAmount($totalMortgage),
            'installments_paid' => $this->getMortgageInstallments()->count(),
            'total_mortgage_paid' => $this->roundAmount($totalMortgagePaid, 2, PHP_ROUND_HALF_UP)
        ];
    }

    public function isSigned(): bool
    {
        return $this->signingDay !== null && $this->signingYear !== null;
    }

    /**
     * @return Collection<int, MortgageInstallment>
     */
    public function getMortgageInstallments(): Collection
    {
        return $this->mortgageInstallments;
    }

    public function addMortgageInstallment(MortgageInstallment $mortgageInstallment): static
    {
        if (!$this->mortgageInstallments->contains($mortgageInstallment)) {
            $this->mortgageInstallments->add($mortgageInstallment);
            $mortgageInstallment->setMortgage($this);
        }

        return $this;
    }

    public function removeMortgageInstallment(MortgageInstallment $mortgageInstallment): static
    {
        if ($this->mortgageInstallments->removeElement($mortgageInstallment)) {
            // imposta il lato proprietario a null (a meno che non sia già cambiato)
            if ($mortgageInstallment->getMortgage() === $this) {
                $mortgageInstallment->setMortgage(null);
            }
        }

        return $this;
    }

    private function normalizeAmount(float|int|string|null $value, int $scale = 2): string
    {
        if ($value === null) {
            return bcadd('0', '0', $scale);
        }

        return bcadd((string)$value, '0', $scale);
    }

    private function roundAmount(string $value, int $precision = 2, int $mode = PHP_ROUND_HALF_DOWN): string
    {
        return number_format((float)$value, $precision, '.', '');
    }
    public function getFinancialAccount(): ?FinancialAccount
    {
        return $this->financialAccount;
    }

    public function setFinancialAccount(?FinancialAccount $financialAccount): static
    {
        $this->financialAccount = $financialAccount;
        return $this;
    }
}
