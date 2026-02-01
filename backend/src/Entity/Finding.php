<?php

namespace App\Entity;

use App\Repository\FindingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FindingRepository::class)]
#[ORM\Table(name: 'findings')]
#[ORM\HasLifecycleCallbacks]
class Finding
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 20)]
    private ?string $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Location is required')]
    private ?string $location = null;

    #[ORM\Column(type: Types::STRING, length: 10)]
    #[Assert\NotBlank(message: 'Risk range is required')]
    #[Assert\Choice(choices: ['Low', 'Medium', 'High'], message: 'Risk range must be Low, Medium, or High')]
    private ?string $riskRange = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Comment is required')]
    private ?string $comment = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Recommendations are required')]
    private ?string $recommendations = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $resolved = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getRiskRange(): ?string
    {
        return $this->riskRange;
    }

    public function setRiskRange(string $riskRange): static
    {
        $this->riskRange = $riskRange;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(string $comment): static
    {
        $this->comment = $comment;
        return $this;
    }

    public function getRecommendations(): ?string
    {
        return $this->recommendations;
    }

    public function setRecommendations(string $recommendations): static
    {
        $this->recommendations = $recommendations;
        return $this;
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }

    public function setResolved(bool $resolved): static
    {
        $this->resolved = $resolved;
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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'location' => $this->location,
            'risk_range' => $this->riskRange,
            'comment' => $this->comment,
            'recommendations' => $this->recommendations,
            'resolved' => $this->resolved,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
