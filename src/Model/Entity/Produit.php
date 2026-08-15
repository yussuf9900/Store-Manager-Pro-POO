<?php

namespace App\Model\Entity;

use DateTime;
use InvalidArgumentException;

class Produit
{
    private ?int $id;
    private string $code;
    private string $libelle;
    private ?string $description;
    private float $prixAchat;
    private float $prixVente;
    private int $qteStock;
    private int $seuilAlerte;
    private ?int $categorieId;
    private ?Categorie $categorie;
    private ?DateTime $dateCreation;

    public function __construct(
        ?int $id = null,
        string $code = '',
        string $libelle = '',
        ?string $description = null,
        float $prixAchat = 0.0,
        float $prixVente = 0.0,
        int $qteStock = 0,
        int $seuilAlerte = 5,
        ?int $categorieId = null,
        ?Categorie $categorie = null,
        ?DateTime $dateCreation = null
    ) {
        $this->id = $id;
        $this->code = strtoupper(trim($code));
        $this->libelle = trim($libelle);
        $this->description = $description;
        $this->prixAchat = max(0.0, $prixAchat);
        $this->prixVente = max(0.0, $prixVente);
        $this->qteStock = max(0, $qteStock);
        $this->seuilAlerte = max(0, $seuilAlerte);
        $this->categorieId = $categorieId;
        $this->categorie = $categorie;
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

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = strtoupper(trim($code));
        return $this;
    }

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = trim($libelle);
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getPrixAchat(): float
    {
        return $this->prixAchat;
    }

    public function setPrixAchat(float $prixAchat): self
    {
        if ($prixAchat < 0) {
            throw new InvalidArgumentException("Le prix d'achat ne peut pas être négatif.");
        }
        $this->prixAchat = $prixAchat;
        return $this;
    }

    public function getPrixVente(): float
    {
        return $this->prixVente;
    }

    public function setPrixVente(float $prixVente): self
    {
        if ($prixVente < 0) {
            throw new InvalidArgumentException("Le prix de vente ne peut pas être négatif.");
        }
        $this->prixVente = $prixVente;
        return $this;
    }

    public function getQteStock(): int
    {
        return $this->qteStock;
    }

    public function setQteStock(int $qteStock): self
    {
        if ($qteStock < 0) {
            throw new InvalidArgumentException("La quantité en stock ne peut pas être négative.");
        }
        $this->qteStock = $qteStock;
        return $this;
    }

    public function getSeuilAlerte(): int
    {
        return $this->seuilAlerte;
    }

    public function setSeuilAlerte(int $seuilAlerte): self
    {
        if ($seuilAlerte < 0) {
            throw new InvalidArgumentException("Le seuil d'alerte ne peut pas être négatif.");
        }
        $this->seuilAlerte = $seuilAlerte;
        return $this;
    }

    public function getCategorieId(): ?int
    {
        return $this->categorieId;
    }

    public function setCategorieId(?int $categorieId): self
    {
        $this->categorieId = $categorieId;
        return $this;
    }

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $categorie): self
    {
        $this->categorie = $categorie;
        if ($categorie !== null && $categorie->getId() !== null) {
            $this->categorieId = $categorie->getId();
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

    public function estEnAlerte(): bool
    {
        return $this->qteStock <= $this->seuilAlerte;
    }

    public function calculerMarge(): float
    {
        return $this->prixVente - $this->prixAchat;
    }

    public function calculerTauxMarge(): float
    {
        if ($this->prixAchat <= 0) {
            return 0.0;
        }

        return (($this->prixVente - $this->prixAchat) / $this->prixAchat) * 100;
    }

    public function hasStockSuffisant(int $quantite): bool
    {
        return $quantite > 0 && $this->qteStock >= $quantite;
    }

    public function ajouterStock(int $quantite): void
    {
        if ($quantite <= 0) {
            throw new InvalidArgumentException("La quantité à ajouter au stock doit être strictement positive.");
        }

        $this->qteStock += $quantite;
    }

    public function retirerStock(int $quantite): bool
    {
        if ($quantite <= 0) {
            throw new InvalidArgumentException("La quantité à retirer du stock doit être strictement positive.");
        }

        if ($this->qteStock < $quantite) {
            throw new InvalidArgumentException(
                sprintf(
                    "Stock insuffisant pour l'article '%s' (Réf: %s) : demandé %d, disponible en stock %d.",
                    $this->libelle,
                    $this->code,
                    $quantite,
                    $this->qteStock
                )
            );
        }

        $this->qteStock -= $quantite;
        return true;
    }
}
