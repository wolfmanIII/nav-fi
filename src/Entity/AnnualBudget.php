<?php

namespace App\Entity;

use App\Repository\AnnualBudgetRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: AnnualBudgetRepository::class)]
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
    private ?Ship $ship = null;

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

    public function getShip(): ?Ship
    {
        return $this->ship;
    }

    public function setShip(?Ship $ship): static
    {
        $this->ship = $ship;

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
        foreach($this->getShip()->getIncomes() as $income) {
            if (
                is_null($income->getCancelDay())
                && is_null($income->getCancelYear())
            ) {
                $budget = bcadd($budget, $income->getAmount(), 6);
            }
        }

        foreach($this->getShip()->getCosts() as $cost) {
            $budget = bcsub($budget, $cost->getAmount(), 6);
        }

        foreach($this->getShip()->getMortgage()->getMortgageInstallments() as $payment) {
            $budget = bcsub($budget, $payment->getPayment(), 6);
        }

        return round($budget, 2, PHP_ROUND_HALF_DOWN);

    }
}
