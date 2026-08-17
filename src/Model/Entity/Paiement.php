<?php

namespace App\Model\Entity;

use DateTime;
use InvalidArgumentException;

class Paiement extends AbstractEntity
{
    private ?Dette $dette;
    private float $montant;
    private DateTime $datePaiement;
    private ?ModePaiement $modePaiement;
    private ?string $referencePaiement;
    private ?User $agent;
    private ?DateTime $dateCreation;

    public function __construct(
        ?int $id = null,
        ?Dette $dette = null,
        float $montant = 0.0,
        ?DateTime $datePaiement = null,
        ?ModePaiement $modePaiement = null,
        ?string $referencePaiement = null,
        ?User $agent = null,
        ?DateTime $dateCreation = null
    ) {
        parent::__construct($id);
        $this->dette = $dette;
        $this->montant = max(0.0, $montant);
        $this->datePaiement = $datePaiement ?? new DateTime();
        $this->modePaiement = $modePaiement;
        $this->referencePaiement = $referencePaiement !== null ? trim($referencePaiement) : null;
        $this->agent = $agent;
        $this->dateCreation = $dateCreation ?? new DateTime();
    }

    public function getDette(): ?Dette
    {
        return $this->dette;
    }

    public function setDette(?Dette $dette): self
    {
        $this->dette = $dette;
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

    public function getModePaiement(): ?ModePaiement
    {
        return $this->modePaiement;
    }

    public function setModePaiement(?ModePaiement $modePaiement): self
    {
        $this->modePaiement = $modePaiement;
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

    public function getAgent(): ?User
    {
        return $this->agent;
    }

    public function setAgent(?User $agent): self
    {
        $this->agent = $agent;
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
