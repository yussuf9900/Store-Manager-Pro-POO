<?php

namespace App\Model\Entity;

use InvalidArgumentException;

class LigneApprovisionnement extends AbstractEntity
{
    private ?Approvisionnement $approvisionnement;
    private ?Produit $produit;
    private int $quantite;
    private float $prixAchatUnitaire;
    private float $sousTotal;

    public function __construct(
        ?int $id = null,
        ?Approvisionnement $approvisionnement = null,
        ?Produit $produit = null,
        int $quantite = 1,
        float $prixAchatUnitaire = 0.0,
        ?float $sousTotal = null
    ) {
        parent::__construct($id);
        $this->approvisionnement = $approvisionnement;
        $this->produit = $produit;
        if ($produit !== null && $prixAchatUnitaire <= 0) {
            $prixAchatUnitaire = $produit->getPrixAchat();
        }
        $this->quantite = max(1, $quantite);
        $this->prixAchatUnitaire = max(0.0, $prixAchatUnitaire);
        $this->sousTotal = $sousTotal ?? $this->calculerSousTotal();
    }

    public function getApprovisionnement(): ?Approvisionnement
    {
        return $this->approvisionnement;
    }

    public function setApprovisionnement(?Approvisionnement $approvisionnement): self
    {
        $this->approvisionnement = $approvisionnement;
        return $this;
    }

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): self
    {
        $this->produit = $produit;
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
