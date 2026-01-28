<?php

namespace App\Entity;

use App\Repository\IncomeRepository;
use App\Entity\LocalLaw;
use App\Entity\Cost;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: IncomeRepository::class)]
#[ORM\Index(name: 'idx_income_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_income_fin_acc', columns: ['financial_account_id'])]
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

    public function __construct()
    {
        $this->setCode(Uuid::v7()->toRfc4122());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    #[ORM\Column(type: Types::GUID)]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

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

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

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

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $details = [];

    /**
     * Specialized relationship for Trade Liquidation (previously in IncomeTradeDetails)
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Cost $purchaseCost = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $signingLocation = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?IncomeCategory $incomeCategory = null;

    #[ORM\ManyToOne(inversedBy: 'incomes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?FinancialAccount $financialAccount = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    public function getDetails(): ?array
    {
        return $this->details ?? [];
    }

    public function setDetails(?array $details): static
    {
        $this->details = $details;
        return $this;
    }

    public function getDetailsData(): \App\Model\IncomeDetails
    {
        return \App\Model\IncomeDetails::fromArray($this->details ?? []);
    }

    public function getPurchaseCost(): ?Cost
    {
        return $this->purchaseCost;
    }

    public function setPurchaseCost(?Cost $purchaseCost): static
    {
        $this->purchaseCost = $purchaseCost;
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

    public function getFinancialAccount(): ?FinancialAccount
    {
        return $this->financialAccount;
    }

    public function setFinancialAccount(?FinancialAccount $financialAccount): static
    {
        $this->financialAccount = $financialAccount;
        return $this;
    }

    public function getAsset(): ?Asset
    {
        return $this->getFinancialAccount()?->getAsset();
    }

    public function setAsset(?Asset $asset): static
    {
        if ($asset) {
            $this->setFinancialAccount($asset->getFinancialAccount());
        } else {
            $this->setFinancialAccount(null);
        }

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
