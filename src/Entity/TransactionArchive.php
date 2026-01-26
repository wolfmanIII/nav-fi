<?php

namespace App\Entity;

use App\Repository\TransactionArchiveRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionArchiveRepository::class)]
#[ORM\Table(name: 'transaction_archive')]
#[ORM\Index(name: 'idx_archive_asset', columns: ['asset_id'])]
#[ORM\Index(name: 'idx_archive_year', columns: ['asset_id', 'session_year'])]
class TransactionArchive
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $assetId = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column]
    private ?int $sessionDay = null;

    #[ORM\Column]
    private ?int $sessionYear = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $relatedEntityType = null;

    #[ORM\Column(nullable: true)]
    private ?int $relatedEntityId = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $archivedAt = null;

    #[ORM\Column]
    private ?int $originalTransactionId = null;

    public function __construct()
    {
        $this->archivedAt = new \DateTimeImmutable();
    }

    public static function fromTransaction(Transaction $transaction): self
    {
        $archive = new self();
        // Transaction -> FinancialAccount -> Asset
        $archive->setAssetId($transaction->getFinancialAccount()->getAsset()->getId());
        $archive->setAmount($transaction->getAmount());
        $archive->setDescription($transaction->getDescription());
        $archive->setSessionDay($transaction->getSessionDay());
        $archive->setSessionYear($transaction->getSessionYear());
        $archive->setRelatedEntityType($transaction->getRelatedEntityType());
        $archive->setRelatedEntityId($transaction->getRelatedEntityId());
        $archive->setCreatedAt($transaction->getCreatedAt());
        $archive->setOriginalTransactionId($transaction->getId());

        return $archive;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAssetId(): ?int
    {
        return $this->assetId;
    }

    public function setAssetId(int $assetId): static
    {
        $this->assetId = $assetId;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getSessionDay(): ?int
    {
        return $this->sessionDay;
    }

    public function setSessionDay(int $sessionDay): static
    {
        $this->sessionDay = $sessionDay;
        return $this;
    }

    public function getSessionYear(): ?int
    {
        return $this->sessionYear;
    }

    public function setSessionYear(int $sessionYear): static
    {
        $this->sessionYear = $sessionYear;
        return $this;
    }

    public function getRelatedEntityType(): ?string
    {
        return $this->relatedEntityType;
    }

    public function setRelatedEntityType(?string $relatedEntityType): static
    {
        $this->relatedEntityType = $relatedEntityType;
        return $this;
    }

    public function getRelatedEntityId(): ?int
    {
        return $this->relatedEntityId;
    }

    public function setRelatedEntityId(?int $relatedEntityId): static
    {
        $this->relatedEntityId = $relatedEntityId;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getArchivedAt(): ?\DateTimeImmutable
    {
        return $this->archivedAt;
    }

    public function setArchivedAt(\DateTimeImmutable $archivedAt): static
    {
        $this->archivedAt = $archivedAt;
        return $this;
    }

    public function getOriginalTransactionId(): ?int
    {
        return $this->originalTransactionId;
    }

    public function setOriginalTransactionId(int $originalTransactionId): static
    {
        $this->originalTransactionId = $originalTransactionId;
        return $this;
    }
}
