<?php

namespace App\Entity;

use App\Repository\AnnualBudgetRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: AnnualBudgetRepository::class)]
#[ORM\Index(name: 'idx_budget_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_budget_asset', columns: ['asset_id'])]
class AnnualBudget
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 36)]
    private ?string $code = null;

    #[ORM\Column]
    private ?int $startDay = null;

    #[ORM\Column]
    private ?int $startYear = null;

    #[ORM\Column]
    private ?int $endDay = null;

    #[ORM\Column]
    private ?int $endYear = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Asset $asset = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    public function __construct()
    {
        $this->setCode(Uuid::v7());
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

    public function getStartDay(): ?int
    {
        return $this->startDay;
    }

    public function setStartDay(int $startDay): static
    {
        $this->startDay = $startDay;

        return $this;
    }

    public function getStartYear(): ?int
    {
        return $this->startYear;
    }

    public function setStartYear(int $startYear): static
    {
        $this->startYear = $startYear;

        return $this;
    }

    public function getEndDay(): ?int
    {
        return $this->endDay;
    }

    public function setEndDay(int $endDay): static
    {
        $this->endDay = $endDay;

        return $this;
    }

    public function getEndYear(): ?int
    {
        return $this->endYear;
    }

    public function setEndYear(int $endYear): static
    {
        $this->endYear = $endYear;

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

    public function calculateBudget()
    {
        $budget = 0.00;
        foreach ($this->getAsset()->getIncomes() as $income) {
            if (
                is_null($income->getCancelDay())
                && !is_null($income->getSigningYear())
                && is_null($income->getCancelYear())
            ) {
                $budget = bcadd($budget, $income->getAmount(), 6);
            }
        }

        foreach ($this->getAsset()->getCosts() as $cost) {
            $budget = bcsub($budget, $cost->getAmount(), 6);
        }

        foreach ($this->getAsset()->getMortgage()->getMortgageInstallments() as $payment) {
            $budget = bcsub($budget, $payment->getPayment(), 6);
        }

        return round($budget, 2, PHP_ROUND_HALF_DOWN);
    }

    public function getTotalIncomeAmount()
    {
        $incomeAmount = 0.00;
        foreach ($this->getAsset()->getIncomes() as $income) {
            if (
                is_null($income->getCancelDay())
                && is_null($income->getCancelYear())
                && !is_null($income->getSigningDay())
                && !is_null($income->getSigningYear())
            ) {
                $incomeAmount = bcadd($incomeAmount, $income->getAmount(), 6);
            }
        }
        return round($incomeAmount, 2, PHP_ROUND_HALF_DOWN);
    }

    public function getTotalCostsAmount()
    {
        $costAmount = 0.00;
        foreach ($this->getAsset()->getCosts() as $cost) {
            $costAmount = bcadd($costAmount, $cost->getAmount(), 6);
        }
        return round($costAmount, 2, PHP_ROUND_HALF_DOWN);
    }

    public function getActualBudget()
    {
        $totalIncomeAmount = $this->getTotalIncomeAmount();
        $mortgageAnnualPayment = $this->getAsset()->getMortgage()->calculate()['total_annual_payment'];
        $totaleCostsAmount = $this->getTotalCostsAmount();

        $totalCost = bcadd($mortgageAnnualPayment, $totaleCostsAmount, 6);

        $actualBudget = bcsub(
            $totalIncomeAmount,
            $totalCost,
            6
        );

        return round($actualBudget, 2, PHP_ROUND_HALF_DOWN);
    }

    #[Assert\Callback]
    public function validateDateRange(ExecutionContextInterface $context): void
    {
        if ($this->startYear === null || $this->endYear === null) {
            return;
        }

        if ($this->startYear > $this->endYear) {
            $context->buildViolation('End date must be after or equal to start date.')
                ->atPath('endDate')
                ->addViolation();
            return;
        }

        if ($this->startYear === $this->endYear && $this->startDay !== null && $this->endDay !== null) {
            if ($this->startDay > $this->endDay) {
                $context->buildViolation('End date must be after or equal to start date.')
                    ->atPath('endDate')
                    ->addViolation();
            }
        }
    }
}
