<?php

namespace App\Entity;

use App\Repository\RaceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: RaceRepository::class)]
class Race
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['race:read', 'result:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['race:read', 'result:read'])]
    private ?string $id_public_race = null;

    #[ORM\Column(length: 255)]
    #[Groups(['race:read', 'result:read'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['race:read'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['race:read'])]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['race:read'])]
    private ?\DateTimeInterface $createAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['race:read'])]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Groups(['race:read'])]
    private ?float $kilometer = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Groups(['race:read'])]
    private ?float $distance = null;

    #[ORM\OneToMany(mappedBy: 'race', targetEntity: Result::class)]
    #[Ignore]
    private Collection $results;

    public function __construct()
    {
        $this->results = new ArrayCollection();
        $this->createAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdPublicRace(): ?string
    {
        return $this->id_public_race;
    }

    public function setIdPublicRace(string $id_public_race): static
    {
        $this->id_public_race = $id_public_race;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getCreateAt(): ?\DateTimeInterface
    {
        return $this->createAt;
    }

    public function setCreateAt(\DateTimeInterface $createAt): static
    {
        $this->createAt = $createAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getKilometer(): ?float
    {
        return $this->kilometer;
    }

    public function setKilometer(?float $kilometer): static
    {
        $this->kilometer = $kilometer;

        return $this;
    }

    public function getDistance(): ?float
    {
        return $this->distance;
    }

    public function setDistance(?float $distance): static
    {
        $this->distance = $distance;

        return $this;
    }

    /**
     * @return Collection<int, Result>
     */
    public function getResults(): Collection
    {
        return $this->results;
    }

    public function addResult(Result $result): static
    {
        if (!$this->results->contains($result)) {
            $this->results->add($result);
            $result->setRace($this);
        }

        return $this;
    }

    public function removeResult(Result $result): static
    {
        if ($this->results->removeElement($result)) {
            // set the owning side to null (unless already changed)
            if ($result->getRace() === $this) {
                $result->setRace(null);
            }
        }

        return $this;
    }
}
