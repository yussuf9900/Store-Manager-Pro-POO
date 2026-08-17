<?php

namespace App\Model\Entity;

use DateTime;
use InvalidArgumentException;

class Approvisionnement extends AbstractEntity
{
    private string $numeroBL;
    private DateTime $dateAppro;
    private float $montantTotal;
    private ?StatutAppro $statut;
    private ?Fournisseur $fournisseur;
    private ?User $agentStock;
    private array $lignes = [];
    private ?DateTime $dateCreation;

    public function __construct(
        ?int $id = null,
        string $numeroBL = '',
        ?DateTime $dateAppro = null,
        float $montantTotal = 0.0,
        ?StatutAppro $statut = null,
        ?Fournisseur $fournisseur = null,
        ?User $agentStock = null,
        array $lignes = [],
        ?DateTime $dateCreation = null
    ) {
        parent::__construct($id);
        $this->numeroBL = trim($numeroBL);
        $this->dateAppro = $dateAppro ?? new DateTime();
        $this->montantTotal = max(0.0, $montantTotal);
        $this->statut = $statut;
        $this->fournisseur = $fournisseur;
        $this->agentStock = $agentStock;
        $this->dateCreation = $dateCreation ?? new DateTime();

        foreach ($lignes as $ligne) {
            $this->ajouterLigne($ligne);
        }
    }

    public function getNumeroBL(): string
    {
        return $this->numeroBL;
    }

    public function isRecu(): bool
    {
        return $this->statut !== null && ($this->statut->isRecu() || $this->statut->getId() === 2);
    }

    public function isEnAttente(): bool
    {
        return $this->statut !== null && ($this->statut->isEnAttente() || $this->statut->getId() === 1);
    }

    public function isAnnule(): bool
    {
        return $this->statut !== null && ($this->statut->isAnnule() || $this->statut->getId() === 3);
    }

    public function setNumeroBL(string $numeroBL): self
    {
        $this->numeroBL = trim($numeroBL);
        return $this;
    }

    public function getDateAppro(): DateTime
    {
        return $this->dateAppro;
    }

    public function setDateAppro(DateTime $dateAppro): self
    {
        $this->dateAppro = $dateAppro;
        return $this;
    }

    public function getMontantTotal(): float
    {
        return $this->montantTotal;
    }

    public function setMontantTotal(float $montantTotal): self
    {
        if ($montantTotal < 0) {
            throw new InvalidArgumentException("Le montant total ne peut pas être négatif.");
        }
        $this->montantTotal = $montantTotal;
        return $this;
    }

    public function getStatut(): ?StatutAppro
    {
        return $this->statut;
    }

    public function setStatut(?StatutAppro $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getFournisseur(): ?Fournisseur
    {
        return $this->fournisseur;
    }

    public function setFournisseur(?Fournisseur $fournisseur): self
    {
        $this->fournisseur = $fournisseur;
        return $this;
    }

    public function getAgentStock(): ?User
    {
        return $this->agentStock;
    }

    public function setAgentStock(?User $agentStock): self
    {
        $this->agentStock = $agentStock;
        return $this;
    }

    public function getLignes(): array
    {
        return $this->lignes;
    }

    public function setLignes(array $lignes): self
    {
        $this->lignes = [];
        foreach ($lignes as $ligne) {
            $this->ajouterLigne($ligne);
        }
        return $this;
    }

    public function ajouterLigne(LigneApprovisionnement $ligne): void
    {
        $this->lignes[] = $ligne;
        $this->montantTotal = $this->calculerTotal();
    }

    public function calculerTotal(): float
    {
        $total = 0.0;
        foreach ($this->lignes as $ligne) {
            $total += $ligne->calculerSousTotal();
        }
        return $total;
    }

    public function getNombreArticles(): int
    {
        $count = 0;
        foreach ($this->lignes as $ligne) {
            $count += $ligne->getQuantite();
        }
        return $count;
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
