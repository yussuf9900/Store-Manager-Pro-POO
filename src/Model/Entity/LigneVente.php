<?php

namespace App\Model\Entity;

use InvalidArgumentException;

class LigneVente
{
    private ?int $id;
    private ?int $venteId;
    private int $produitId;
    private ?Produit $produit;
    private int $quantite;
    private float $prixUnitaire;
    private float $remise;
    private float $sousTotal;

    public function __construct(
        ?int $id = null,
        ?int $venteId = null,
        int $produitId = 0,
        ?Produit $produit = null,
        int $quantite = 1,
        float $prixUnitaire = 0.0,
        float $remise = 0.0,
        ?float $sousTotal = null
    ) {
        $this->id = $id;
        $this->venteId = $venteId;
        $this->produitId = $produitId;
        $this->produit = $produit;
        if ($produit !== null && $produit->getId() !== null) {
            $this->produitId = $produit->getId();
            if ($prixUnitaire <= 0) {
                $prixUnitaire = $produit->getPrixVente();
            }
        }
        $this->quantite = max(1, $quantite);
        $this->prixUnitaire = max(0.0, $prixUnitaire);
        $this->remise = max(0.0, $remise);
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

    public function getVenteId(): ?int
    {
        return $this->venteId;
    }

    public function setVenteId(?int $venteId): self
    {
        $this->venteId = $venteId;
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
            throw new InvalidArgumentException("La quantité vendue doit être strictement positive.");
        }
        $this->quantite = $quantite;
        $this->sousTotal = $this->calculerSousTotal();
        return $this;
    }

    public function getPrixUnitaire(): float
    {
        return $this->prixUnitaire;
    }

    public function setPrixUnitaire(float $prixUnitaire): self
    {
        if ($prixUnitaire < 0) {
            throw new InvalidArgumentException("Le prix unitaire ne peut pas être négatif.");
        }
        $this->prixUnitaire = $prixUnitaire;
        $this->sousTotal = $this->calculerSousTotal();
        return $this;
    }

    public function getRemise(): float
    {
        return $this->remise;
    }

    public function setRemise(float $remise): self
    {
        if ($remise < 0) {
            throw new InvalidArgumentException("La remise ne peut pas être négative.");
        }
        $this->remise = $remise;
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
        $brut = $this->prixUnitaire * $this->quantite;
        return max(0.0, $brut - $this->remise);
    }
}
