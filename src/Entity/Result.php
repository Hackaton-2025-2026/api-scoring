<?php

namespace App\Entity;

use App\Repository\ResultRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ResultRepository::class)]
class Result
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['result:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'results')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['result:read'])]
    private ?Runner $runner = null;

    #[ORM\ManyToOne(inversedBy: 'results')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['result:read'])]
    private ?Race $race = null;

    #[ORM\Column(length: 255)]
    #[Groups(['result:read'])]
    private ?string $time = null;

    #[ORM\Column]
    #[Groups(['result:read'])]
    private ?int $runnerRank = null;

    #[ORM\Column]
    #[Groups(['result:read'])]
    private ?bool $hasFinished = null;

    #[ORM\Column(type: Types::FLOAT, options: ['default' => 0.0])]
    #[Groups(['result:read'])]
    private float $liveKilometer = 0.0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['result:read'])]
    private ?\DateTimeInterface $createAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['result:read'])]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createAt = new \DateTime();
    }

    public function getLiveKilometer(): float
    {
        return $this->liveKilometer;
    }

    public function setLiveKilometer(float $liveKilometer): static
    {
        $this->liveKilometer = $liveKilometer;

        return $this;
    }

    #[Groups(['result:read'])]
    public function getRunnerBibNumber(): ?int
    {
        return $this->getRunner() ? $this->getRunner()->getBibNumber() : null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRunner(): ?Runner
    {
        return $this->runner;
    }

    public function setRunner(?Runner $runner): static
    {
        $this->runner = $runner;

        return $this;
    }

    public function getRace(): ?Race
    {
        return $this->race;
    }

    public function setRace(?Race $race): static
    {
        $this->race = $race;

        return $this;
    }

    public function getTime(): ?string
    {
        return $this->time;
    }

    public function setTime(string $time): static
    {
        $this->time = $time;

        return $this;
    }

    public function getRunnerRank(): ?int
    {
        return $this->runnerRank;
    }

    public function setRunnerRank(int $runnerRank): static
    {
        $this->runnerRank = $runnerRank;

        return $this;
    }

    public function isHasFinished(): ?bool
    {
        return $this->hasFinished;
    }

    public function setHasFinished(bool $hasFinished): static
    {
        $this->hasFinished = $hasFinished;

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
}
