<?php

namespace App\Entity;

use App\Repository\SalaryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SalaryRepository::class)]
class Salary
{
    public const STATUS_ACTIVE = 'Active';
    public const STATUS_SUSPENDED = 'Suspended';
    public const STATUS_COMPLETED = 'Completed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'salaries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Crew $crew = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column]
    private ?int $firstPaymentDay = null;

    #[ORM\Column]
    private ?int $firstPaymentYear = null;

    #[ORM\Column(length: 20, options: ['default' => self::STATUS_ACTIVE])]
    private ?string $status = self::STATUS_ACTIVE;

    /**
     * @var Collection<int, SalaryPayment>
     */
    #[ORM\OneToMany(targetEntity: SalaryPayment::class, mappedBy: 'salary', orphanRemoval: true)]
    private Collection $payments;


    public function __construct()
    {
        $this->payments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCrew(): ?Crew
    {
        return $this->crew;
    }

    public function setCrew(?Crew $crew): static
    {
        $this->crew = $crew;

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

    public function getFirstPaymentDay(): ?int
    {
        return $this->firstPaymentDay;
    }

    public function setFirstPaymentDay(int $firstPaymentDay): static
    {
        $this->firstPaymentDay = $firstPaymentDay;

        return $this;
    }

    public function getFirstPaymentYear(): ?int
    {
        return $this->firstPaymentYear;
    }

    public function setFirstPaymentYear(int $firstPaymentYear): static
    {
        $this->firstPaymentYear = $firstPaymentYear;

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

    /**
     * @return Collection<int, SalaryPayment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(SalaryPayment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setSalary($this);
        }

        return $this;
    }

    public function removePayment(SalaryPayment $payment): static
    {
        if ($this->payments->removeElement($payment)) {
            // set the owning side to null (unless already changed)
            if ($payment->getSalary() === $this) {
                $payment->setSalary(null);
            }
        }

        return $this;
    }
}
