<?php

namespace App\Model\Entity;

use DateTime;
use InvalidArgumentException;

class Approvisionnement
{
    private ?int $id;
    private string $numeroBL;
    private DateTime $dateAppro;
    private float $montantTotal;
    private int $statutId;
    private ?StatutAppro $statut;
    private int $fournisseurId;
    private ?Fournisseur $fournisseur;
    private int $userId;
    private ?User $agentStock;
    private array $lignes = [];
    private ?DateTime $dateCreation;

    public function __construct(
        ?int $id = null,
        string $numeroBL = '',
        ?DateTime $dateAppro = null,
        float $montantTotal = 0.0,
        int $statutId = 1,
        ?StatutAppro $statut = null,
        int $fournisseurId = 0,
        ?Fournisseur $fournisseur = null,
        int $userId = 1,
        ?User $agentStock = null,
        array $lignes = [],
        ?DateTime $dateCreation = null
    ) {
        $this->id = $id;
        $this->numeroBL = trim($numeroBL);
        $this->dateAppro = $dateAppro ?? new DateTime();
        $this->montantTotal = max(0.0, $montantTotal);
        $this->statutId = $statutId;
        $this->statut = $statut;
        $this->fournisseurId = $fournisseurId;
        $this->fournisseur = $fournisseur;
        if ($fournisseur !== null && $fournisseur->getId() !== null) {
            $this->fournisseurId = $fournisseur->getId();
        }
        $this->userId = $userId;
        $this->agentStock = $agentStock;
        if ($agentStock !== null && $agentStock->getId() !== null) {
            $this->userId = $agentStock->getId();
        }
        $this->dateCreation = $dateCreation ?? new DateTime();

        foreach ($lignes as $ligne) {
            $this->ajouterLigne($ligne);
        }
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

    public function getNumeroBL(): string
    {
        return $this->numeroBL;
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

    public function getStatutId(): int
    {
        return $this->statutId;
    }

    public function setStatutId(int $statutId): self
    {
        $this->statutId = $statutId;
        return $this;
    }

    public function getStatut(): ?StatutAppro
    {
        return $this->statut;
    }

    public function setStatut(?StatutAppro $statut): self
    {
        $this->statut = $statut;
        if ($statut !== null && $statut->getId() !== null) {
            $this->statutId = $statut->getId();
        }
        return $this;
    }

    public function getFournisseurId(): int
    {
        return $this->fournisseurId;
    }

    public function setFournisseurId(int $fournisseurId): self
    {
        $this->fournisseurId = $fournisseurId;
        return $this;
    }

    public function getFournisseur(): ?Fournisseur
    {
        return $this->fournisseur;
    }

    public function setFournisseur(?Fournisseur $fournisseur): self
    {
        $this->fournisseur = $fournisseur;
        if ($fournisseur !== null && $fournisseur->getId() !== null) {
            $this->fournisseurId = $fournisseur->getId();
        }
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

    public function getAgentStock(): ?User
    {
        return $this->agentStock;
    }

    public function setAgentStock(?User $agentStock): self
    {
        $this->agentStock = $agentStock;
        if ($agentStock !== null && $agentStock->getId() !== null) {
            $this->userId = $agentStock->getId();
        }
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
