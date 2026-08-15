<?php

namespace App\Model\Entity;

use DateTime;
use InvalidArgumentException;

class Vente
{
    private ?int $id;
    private string $numeroFacture;
    private DateTime $dateVente;
    private float $montantTotal;
    private float $montantPaye;
    private float $montantRestant;
    private int $modePaiementId;
    private ?ModePaiement $modePaiement;
    private string $statut;
    private ?int $clientId;
    private ?Client $client;
    private int $userId;
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
        int $modePaiementId = 1,
        ?ModePaiement $modePaiement = null,
        string $statut = 'VALIDEE',
        ?int $clientId = null,
        ?Client $client = null,
        int $userId = 1,
        ?User $vendeur = null,
        array $lignes = [],
        ?DateTime $dateCreation = null
    ) {
        $this->id = $id;
        $this->numeroFacture = trim($numeroFacture);
        $this->dateVente = $dateVente ?? new DateTime();
        $this->montantTotal = max(0.0, $montantTotal);
        $this->montantPaye = max(0.0, $montantPaye);
        $this->montantRestant = max(0.0, $montantRestant);
        $this->modePaiementId = $modePaiementId;
        $this->modePaiement = $modePaiement;
        $this->statut = strtoupper(trim($statut));
        $this->clientId = $clientId;
        $this->client = $client;
        $this->userId = $userId;
        $this->vendeur = $vendeur;
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

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = strtoupper(trim($statut));
        return $this;
    }

    public function getClientId(): ?int
    {
        return $this->clientId;
    }

    public function setClientId(?int $clientId): self
    {
        $this->clientId = $clientId;
        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;
        if ($client !== null && $client->getId() !== null) {
            $this->clientId = $client->getId();
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

    public function getVendeur(): ?User
    {
        return $this->vendeur;
    }

    public function setVendeur(?User $vendeur): self
    {
        $this->vendeur = $vendeur;
        if ($vendeur !== null && $vendeur->getId() !== null) {
            $this->userId = $vendeur->getId();
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

        if ($this->modePaiement !== null && $this->modePaiement->getCode() === ModePaiement::DETTE) {
            return true;
        }

        return $this->modePaiementId === 5;
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
