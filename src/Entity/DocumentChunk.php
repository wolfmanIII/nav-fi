<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'document_chunk')]
#[ORM\Index(columns: ['path'], name: 'idx_document_chunk_path')]
class DocumentChunk
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // es: "manuali/capitolo1.pdf"
    #[ORM\Column(length: 255)]
    private string $path;

    // pdf / md / odt / docx
    #[ORM\Column(length: 10)]
    private string $extension;

    #[ORM\Column(type: 'integer')]
    private int $chunkIndex = 0;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $indexedAt;

    // hash del file (es. sha256)
    #[ORM\Column(length: 64)]
    private string $fileHash;

    // embedding vettoriale (pgvector)
    #[ORM\Column(type: 'vector', length: 1536, nullable: true)]
    private ?array $embedding = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function setExtension(string $extension): self
    {
        $this->extension = $extension;
        return $this;
    }

    public function getChunkIndex(): int
    {
        return $this->chunkIndex;
    }

    public function setChunkIndex(int $chunkIndex): self
    {
        $this->chunkIndex = $chunkIndex;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getIndexedAt(): \DateTimeImmutable
    {
        return $this->indexedAt;
    }

    public function setIndexedAt(\DateTimeImmutable $indexedAt): self
    {
        $this->indexedAt = $indexedAt;
        return $this;
    }

    public function getEmbedding(): ?array
    {
        return $this->embedding;
    }

    public function setEmbedding(?array $embedding): self
    {
        $this->embedding = $embedding;
        return $this;
    }

    public function getFileHash(): string
    {
        return $this->fileHash;
    }

    public function setFileHash(string $fileHash): self
    {
        $this->fileHash = $fileHash;
        return $this;
    }
}
