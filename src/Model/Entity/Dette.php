<?php

namespace App\Model\Entity;

use DateTime;
use InvalidArgumentException;

class Dette extends AbstractEntity
{
    private ?Vente $vente;
    private ?Client $client;
    private float $montantTotal;
    private float $montantRestant;
    private DateTime $dateCreation;
    private ?DateTime $dateEcheance;
    private ?StatutDette $statut;
    private array $paiements = [];

    public function __construct(
        ?int $id = null,
        ?Vente $vente = null,
        ?Client $client = null,
        float $montantTotal = 0.0,
        ?float $montantRestant = null,
        ?DateTime $dateCreation = null,
        ?DateTime $dateEcheance = null,
        ?StatutDette $statut = null,
        array $paiements = []
    ) {
        parent::__construct($id);
        $this->vente = $vente;
        $this->client = $client;
        $this->montantTotal = max(0.0, $montantTotal);
        $this->montantRestant = $montantRestant ?? $this->montantTotal;
        $this->dateCreation = $dateCreation ?? new DateTime();
        $this->dateEcheance = $dateEcheance;
        $this->statut = $statut ?? new StatutDette(1, StatutDette::NON_SOLDEE, 'Non soldée');

        foreach ($paiements as $paiement) {
            $this->enregistrerPaiement($paiement);
        }
    }

    public function getVente(): ?Vente
    {
        return $this->vente;
    }

    public function setVente(?Vente $vente): self
    {
        $this->vente = $vente;
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

    public function getMontantTotal(): float
    {
        return $this->montantTotal;
    }

    public function setMontantTotal(float $montantTotal): self
    {
        if ($montantTotal < 0) {
            throw new InvalidArgumentException("Le montant total de la dette ne peut pas être négatif.");
        }
        $this->montantTotal = $montantTotal;
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
        if ($montantRestant > $this->montantTotal) {
            throw new InvalidArgumentException("Le montant restant ne peut pas excéder le montant total de la dette.");
        }
        $this->montantRestant = $montantRestant;

        if ($this->montantRestant <= 0) {
            if ($this->statut === null) {
                $this->statut = new StatutDette(2, StatutDette::SOLDEE, 'Soldée / Intégralement payée');
            } else {
                $this->statut->setId(2);
                $this->statut->setCode(StatutDette::SOLDEE);
                $this->statut->setLibelle('Soldée / Intégralement payée');
            }
        }

        return $this;
    }

    public function getDateCreation(): DateTime
    {
        return $this->dateCreation;
    }

    public function setDateCreation(DateTime $dateCreation): self
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getDateEcheance(): ?DateTime
    {
        return $this->dateEcheance;
    }

    public function setDateEcheance(?DateTime $dateEcheance): self
    {
        $this->dateEcheance = $dateEcheance;
        return $this;
    }

    public function getStatut(): ?StatutDette
    {
        return $this->statut;
    }

    public function setStatut(?StatutDette $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getPaiements(): array
    {
        return $this->paiements;
    }

    public function enregistrerPaiement(Paiement $paiement): void
    {
        if ($paiement->getMontant() <= 0) {
            throw new InvalidArgumentException("Le montant du versement doit être supérieur à zéro.");
        }

        $this->paiements[] = $paiement;
        $this->montantRestant = max(0.0, $this->montantRestant - $paiement->getMontant());

        if ($this->montantRestant <= 0) {
            if ($this->statut === null) {
                $this->statut = new StatutDette(2, StatutDette::SOLDEE, 'Soldée / Intégralement payée');
            } else {
                $this->statut->setId(2);
                $this->statut->setCode(StatutDette::SOLDEE);
                $this->statut->setLibelle('Soldée / Intégralement payée');
            }
        }
    }

    public function calculerTotalPaye(): float
    {
        $total = 0.0;
        foreach ($this->paiements as $paiement) {
            $total += $paiement->getMontant();
        }
        return $total;
    }

    public function getMontantInitial(): float
    {
        return $this->montantTotal;
    }

    public function getMontantPaye(): float
    {
        return max(0.0, $this->montantTotal - $this->montantRestant);
    }

    public function getPourcentageRembourse(): float
    {
        if ($this->montantTotal <= 0) {
            return 100.0;
        }
        return round(($this->getMontantPaye() / $this->montantTotal) * 100, 1);
    }

    public function estSoldee(): bool
    {
        return $this->montantRestant <= 0.0 || ($this->statut !== null && $this->statut->isSoldee());
    }

    public function estEnRetard(): bool
    {
        if ($this->estSoldee()) {
            return false;
        }

        if ($this->dateEcheance === null) {
            return false;
        }

        $now = new DateTime();
        return $now > $this->dateEcheance;
    }
}
