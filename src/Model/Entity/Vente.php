<?php

namespace App\Model\Entity;

use DateTime;
use InvalidArgumentException;

class Vente extends AbstractEntity
{
    private string $numeroFacture;
    private DateTime $dateVente;
    private float $montantTotal;
    private float $montantPaye;
    private float $montantRestant;
    private ?ModePaiement $modePaiement;
    private string $statut;
    private ?Client $client;
    private ?User $vendeur;
    private array $lignes = [];
    private ?DateTime $dateCreation;

    public function __construct(
        ?int $id = null,
        string $numeroFacture = '',
        ?DateTime $dateVente = null,
        float $montantTotal = 0.0,
        float $montantPaye = 0.0,
        float $montantRestant = 0.0,
        ?ModePaiement $modePaiement = null,
        string $statut = 'VALIDEE',
        ?Client $client = null,
        ?User $vendeur = null,
        array $lignes = [],
        ?DateTime $dateCreation = null
    ) {
        parent::__construct($id);
        $this->numeroFacture = trim($numeroFacture);
        $this->dateVente = $dateVente ?? new DateTime();
        $this->montantTotal = max(0.0, $montantTotal);
        $this->montantPaye = max(0.0, $montantPaye);
        $this->montantRestant = max(0.0, $montantRestant);
        $this->modePaiement = $modePaiement;
        $this->statut = strtoupper(trim($statut));
        $this->client = $client;
        $this->vendeur = $vendeur;
        $this->dateCreation = $dateCreation ?? new DateTime();

        foreach ($lignes as $ligne) {
            $this->ajouterLigne($ligne);
        }
    }

    public function getNumeroFacture(): string
    {
        return $this->numeroFacture;
    }

    public function setNumeroFacture(string $numeroFacture): self
    {
        $this->numeroFacture = trim($numeroFacture);
        return $this;
    }

    public function getDateVente(): DateTime
    {
        return $this->dateVente;
    }

    public function setDateVente(DateTime $dateVente): self
    {
        $this->dateVente = $dateVente;
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

    public function getMontantPaye(): float
    {
        return $this->montantPaye;
    }

    public function setMontantPaye(float $montantPaye): self
    {
        if ($montantPaye < 0) {
            throw new InvalidArgumentException("Le montant payé ne peut pas être négatif.");
        }
        $this->montantPaye = $montantPaye;
        $this->montantRestant = max(0.0, $this->montantTotal - $this->montantPaye);
        return $this;
    }

    public function getMontantRestant(): float
    {
        return $this->montantRestant;
    }

    public function setMontantRestant(float $montantRestant): self
    {
        if ($montantRestant < 0) {
            throw new InvalidArgumentException("Le montant restant ne peut pas être négatif.");
        }
        $this->montantRestant = $montantRestant;
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

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = strtoupper(trim($statut));
        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;
        return $this;
    }

    public function getVendeur(): ?User
    {
        return $this->vendeur;
    }

    public function setVendeur(?User $vendeur): self
    {
        $this->vendeur = $vendeur;
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

    public function ajouterLigne(LigneVente $ligne): void
    {
        $this->lignes[] = $ligne;
        $this->montantTotal = $this->calculerTotal();
        $this->montantRestant = max(0.0, $this->montantTotal - $this->montantPaye);
    }

    public function calculerTotal(): float
    {
        $total = 0.0;
        foreach ($this->lignes as $ligne) {
            $total += $ligne->calculerSousTotal();
        }
        return $total;
    }

    public function recalculerMontants(float $montantPaye = 0.0): void
    {
        $this->montantTotal = $this->calculerTotal();
        $this->montantPaye = max(0.0, $montantPaye);
        $this->montantRestant = max(0.0, $this->montantTotal - $this->montantPaye);
    }

    public function estACredit(): bool
    {
        if ($this->montantRestant > 0) {
            return true;
        }

        if ($this->modePaiement !== null) {
            return $this->modePaiement->getCode() === ModePaiement::DETTE || $this->modePaiement->getId() === 5;
        }

        return false;
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
