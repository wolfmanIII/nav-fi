<?php

namespace App\Entity;

use App\Repository\FinancialAccountRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: FinancialAccountRepository::class)]
#[ORM\Index(name: 'idx_fin_acc_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_fin_acc_bank', columns: ['bank_id'])]
class FinancialAccount
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::GUID)]
    private ?string $code = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, options: ['default' => '0.00'])]
    private ?string $credits = '0.00';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bankName = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Company $bank = null;

    #[ORM\OneToOne(inversedBy: 'financialAccount', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Asset $asset = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\OneToMany(mappedBy: 'financialAccount', targetEntity: Income::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $incomes;

    #[ORM\OneToMany(mappedBy: 'financialAccount', targetEntity: Cost::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $costs;

    #[ORM\OneToMany(mappedBy: 'financialAccount', targetEntity: Transaction::class, cascade: ['persist', 'remove'])]
    private Collection $transactions;

    #[ORM\OneToMany(mappedBy: 'financialAccount', targetEntity: AnnualBudget::class, cascade: ['persist', 'remove'])]
    private Collection $annualBudgets;

    public function __construct()
    {
        $this->code = Uuid::v7()->toRfc4122();
        $this->incomes = new ArrayCollection();
        $this->costs = new ArrayCollection();
        $this->transactions = new ArrayCollection();
        $this->annualBudgets = new ArrayCollection();
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

    public function getCredits(): ?string
    {
        return $this->credits;
    }

    public function setCredits(string $credits): static
    {
        $this->credits = $credits;
        return $this;
    }

    public function getBankName(): ?string
    {
        return $this->bankName;
    }

    public function setBankName(?string $bankName): static
    {
        $this->bankName = $bankName;
        return $this;
    }

    public function getBank(): ?Company
    {
        return $this->bank;
    }

    public function setBank(?Company $bank): static
    {
        $this->bank = $bank;
        return $this;
    }

    public function getAsset(): ?Asset
    {
        return $this->asset;
    }

    public function setAsset(?Asset $asset): static
    {
        $this->asset = $asset;

        // set the inverse side of the relation if necessary
        if ($asset !== null && $asset->getFinancialAccount() !== $this) {
            $asset->setFinancialAccount($this);
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

    public function getCampaign(): ?Campaign
    {
        return $this->asset?->getCampaign();
    }

    /**
     * @return Collection<int, Income>
     */
    public function getIncomes(): Collection
    {
        return $this->incomes;
    }

    public function addIncome(Income $income): static
    {
        if (!$this->incomes->contains($income)) {
            $this->incomes->add($income);
            $income->setFinancialAccount($this);
        }
        return $this;
    }

    public function removeIncome(Income $income): static
    {
        if ($this->incomes->removeElement($income)) {
            if ($income->getFinancialAccount() === $this) {
                $income->setFinancialAccount(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Cost>
     */
    public function getCosts(): Collection
    {
        return $this->costs;
    }

    public function addCost(Cost $cost): static
    {
        if (!$this->costs->contains($cost)) {
            $this->costs->add($cost);
            $cost->setFinancialAccount($this);
        }
        return $this;
    }

    public function removeCost(Cost $cost): static
    {
        if ($this->costs->removeElement($cost)) {
            if ($cost->getFinancialAccount() === $this) {
                $cost->setFinancialAccount(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): static
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setFinancialAccount($this);
        }
        return $this;
    }

    public function removeTransaction(Transaction $transaction): static
    {
        if ($this->transactions->removeElement($transaction)) {
            if ($transaction->getFinancialAccount() === $this) {
                $transaction->setFinancialAccount(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, AnnualBudget>
     */
    public function getAnnualBudgets(): Collection
    {
        return $this->annualBudgets;
    }

    public function addAnnualBudget(AnnualBudget $annualBudget): static
    {
        if (!$this->annualBudgets->contains($annualBudget)) {
            $this->annualBudgets->add($annualBudget);
            $annualBudget->setFinancialAccount($this);
        }
        return $this;
    }

    public function removeAnnualBudget(AnnualBudget $annualBudget): static
    {
        if ($this->annualBudgets->removeElement($annualBudget)) {
            if ($annualBudget->getFinancialAccount() === $this) {
                $annualBudget->setFinancialAccount(null);
            }
        }
        return $this;
    }

    public function getDisplayName(): string
    {
        $bank = $this->bank ? $this->bank->getName() : ($this->bankName ?: 'Unknown Institution');
        $asset = $this->asset ? $this->asset->getName() : 'Unlinked Asset';

        // STANDARD: Bank - Asset (CODE: XXXXXXXX)
        // Matches user requirement for consistent CODE usage vs ID.
        return sprintf('%s - %s (CODE: %s)', $bank, $asset, substr((string) $this->code, 0, 8));
    }

    public function __toString(): string
    {
        return $this->getDisplayName();
    }
}
