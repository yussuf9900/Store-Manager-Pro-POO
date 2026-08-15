<?php

namespace App\Model\Entity;

use DateTime;
use InvalidArgumentException;

class Paiement
{
    private ?int $id;
    private int $detteId;
    private ?Dette $dette;
    private float $montant;
    private DateTime $datePaiement;
    private int $modePaiementId;
    private ?ModePaiement $modePaiement;
    private ?string $referencePaiement;
    private int $userId;
    private ?User $agent;
    private ?DateTime $dateCreation;

    public function __construct(
        ?int $id = null,
        int $detteId = 0,
        ?Dette $dette = null,
        float $montant = 0.0,
        ?DateTime $datePaiement = null,
        int $modePaiementId = 1,
        ?ModePaiement $modePaiement = null,
        ?string $referencePaiement = null,
        int $userId = 1,
        ?User $agent = null,
        ?DateTime $dateCreation = null
    ) {
        $this->id = $id;
        $this->detteId = $detteId;
        $this->dette = $dette;
        if ($dette !== null && $dette->getId() !== null) {
            $this->detteId = $dette->getId();
        }
        $this->montant = max(0.0, $montant);
        $this->datePaiement = $datePaiement ?? new DateTime();
        $this->modePaiementId = $modePaiementId;
        $this->modePaiement = $modePaiement;
        $this->referencePaiement = $referencePaiement !== null ? trim($referencePaiement) : null;
        $this->userId = $userId;
        $this->agent = $agent;
        $this->dateCreation = $dateCreation ?? new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getDetteId(): int
    {
        return $this->detteId;
    }

    public function setDetteId(int $detteId): self
    {
        $this->detteId = $detteId;
        return $this;
    }

    public function getDette(): ?Dette
    {
        return $this->dette;
    }

    public function setDette(?Dette $dette): self
    {
        $this->dette = $dette;
        if ($dette !== null && $dette->getId() !== null) {
            $this->detteId = $dette->getId();
        }
        return $this;
    }

    public function getMontant(): float
    {
        return $this->montant;
    }

    public function setMontant(float $montant): self
    {
        if ($montant <= 0) {
            throw new InvalidArgumentException("Le montant du paiement doit être strictement positif.");
        }
        $this->montant = $montant;
        return $this;
    }

    public function getDatePaiement(): DateTime
    {
        return $this->datePaiement;
    }

    public function setDatePaiement(DateTime $datePaiement): self
    {
        $this->datePaiement = $datePaiement;
        return $this;
    }

    public function getModePaiementId(): int
    {
        return $this->modePaiementId;
    }

    public function setModePaiementId(int $modePaiementId): self
    {
        $this->modePaiementId = $modePaiementId;
        return $this;
    }

    public function getModePaiement(): ?ModePaiement
    {
        return $this->modePaiement;
    }

    public function setModePaiement(?ModePaiement $modePaiement): self
    {
        $this->modePaiement = $modePaiement;
        if ($modePaiement !== null && $modePaiement->getId() !== null) {
            $this->modePaiementId = $modePaiement->getId();
        }
        return $this;
    }

    public function getReferencePaiement(): ?string
    {
        return $this->referencePaiement;
    }

    public function setReferencePaiement(?string $referencePaiement): self
    {
        $this->referencePaiement = $referencePaiement !== null ? trim($referencePaiement) : null;
        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getAgent(): ?User
    {
        return $this->agent;
    }

    public function setAgent(?User $agent): self
    {
        $this->agent = $agent;
        if ($agent !== null && $agent->getId() !== null) {
            $this->userId = $agent->getId();
        }
        return $this;
    }

    public function getDateCreation(): ?DateTime
    {
        return $this->dateCreation;
    }

    public function setDateCreation(?DateTime $dateCreation): self
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }
}
