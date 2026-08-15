<?php

namespace App\Model\Entity;

use InvalidArgumentException;

class LigneApprovisionnement
{
    private ?int $id;
    private ?int $approvisionnementId;
    private int $produitId;
    private ?Produit $produit;
    private int $quantite;
    private float $prixAchatUnitaire;
    private float $sousTotal;

    public function __construct(
        ?int $id = null,
        ?int $approvisionnementId = null,
        int $produitId = 0,
        ?Produit $produit = null,
        int $quantite = 1,
        float $prixAchatUnitaire = 0.0,
        ?float $sousTotal = null
    ) {
        $this->id = $id;
        $this->approvisionnementId = $approvisionnementId;
        $this->produitId = $produitId;
        $this->produit = $produit;
        if ($produit !== null && $produit->getId() !== null) {
            $this->produitId = $produit->getId();
            if ($prixAchatUnitaire <= 0) {
                $prixAchatUnitaire = $produit->getPrixAchat();
            }
        }
        $this->quantite = max(1, $quantite);
        $this->prixAchatUnitaire = max(0.0, $prixAchatUnitaire);
        $this->sousTotal = $sousTotal ?? $this->calculerSousTotal();
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

    public function getApprovisionnementId(): ?int
    {
        return $this->approvisionnementId;
    }

    public function setApprovisionnementId(?int $approvisionnementId): self
    {
        $this->approvisionnementId = $approvisionnementId;
        return $this;
    }

    public function getProduitId(): int
    {
        return $this->produitId;
    }

    public function setProduitId(int $produitId): self
    {
        $this->produitId = $produitId;
        return $this;
    }

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): self
    {
        $this->produit = $produit;
        if ($produit !== null && $produit->getId() !== null) {
            $this->produitId = $produit->getId();
        }
        return $this;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): self
    {
        if ($quantite <= 0) {
            throw new InvalidArgumentException("La quantité approvisionnée doit être strictement positive.");
        }
        $this->quantite = $quantite;
        $this->sousTotal = $this->calculerSousTotal();
        return $this;
    }

    public function getPrixAchatUnitaire(): float
    {
        return $this->prixAchatUnitaire;
    }

    public function setPrixAchatUnitaire(float $prixAchatUnitaire): self
    {
        if ($prixAchatUnitaire < 0) {
            throw new InvalidArgumentException("Le prix d'achat unitaire ne peut pas être négatif.");
        }
        $this->prixAchatUnitaire = $prixAchatUnitaire;
        $this->sousTotal = $this->calculerSousTotal();
        return $this;
    }

    public function getSousTotal(): float
    {
        return $this->sousTotal;
    }

    public function setSousTotal(float $sousTotal): self
    {
        $this->sousTotal = max(0.0, $sousTotal);
        return $this;
    }

    public function calculerSousTotal(): float
    {
        return $this->prixAchatUnitaire * $this->quantite;
    }
}
