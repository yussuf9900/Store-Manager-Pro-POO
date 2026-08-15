<?php

namespace App\Model\Entity;

use DateTime;
use InvalidArgumentException;

class Dette
{
    private ?int $id;
    private ?int $venteId;
    private ?Vente $vente;
    private int $clientId;
    private ?Client $client;
    private float $montantTotal;
    private float $montantRestant;
    private DateTime $dateCreation;
    private ?DateTime $dateEcheance;
    private int $statutId;
    private ?StatutDette $statut;
    private array $paiements = [];

    public function __construct(
        ?int $id = null,
        ?int $venteId = null,
        ?Vente $vente = null,
        int $clientId = 0,
        ?Client $client = null,
        float $montantTotal = 0.0,
        ?float $montantRestant = null,
        ?DateTime $dateCreation = null,
        ?DateTime $dateEcheance = null,
        int $statutId = 1,
        ?StatutDette $statut = null,
        array $paiements = []
    ) {
        $this->id = $id;
        $this->venteId = $venteId;
        $this->vente = $vente;
        if ($vente !== null && $vente->getId() !== null) {
            $this->venteId = $vente->getId();
        }
        $this->clientId = $clientId;
        $this->client = $client;
        if ($client !== null && $client->getId() !== null) {
            $this->clientId = $client->getId();
        }
        $this->montantTotal = max(0.0, $montantTotal);
        $this->montantRestant = $montantRestant ?? $this->montantTotal;
        $this->dateCreation = $dateCreation ?? new DateTime();
        $this->dateEcheance = $dateEcheance;
        $this->statutId = $statutId;
        $this->statut = $statut;

        foreach ($paiements as $paiement) {
            $this->enregistrerPaiement($paiement);
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

    public function getVenteId(): ?int
    {
        return $this->venteId;
    }

    public function setVenteId(?int $venteId): self
    {
        $this->venteId = $venteId;
        return $this;
    }

    public function getVente(): ?Vente
    {
        return $this->vente;
    }

    public function setVente(?Vente $vente): self
    {
        $this->vente = $vente;
        if ($vente !== null && $vente->getId() !== null) {
            $this->venteId = $vente->getId();
        }
        return $this;
    }

    public function getClientId(): int
    {
        return $this->clientId;
    }

    public function setClientId(int $clientId): self
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
            $this->statutId = 2;
            if ($this->statut !== null) {
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

    public function getStatutId(): int
    {
        return $this->statutId;
    }

    public function setStatutId(int $statutId): self
    {
        $this->statutId = $statutId;
        return $this;
    }

    public function getStatut(): ?StatutDette
    {
        return $this->statut;
    }

    public function setStatut(?StatutDette $statut): self
    {
        $this->statut = $statut;
        if ($statut !== null && $statut->getId() !== null) {
            $this->statutId = $statut->getId();
        }
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
            $this->statutId = 2;
            if ($this->statut === null) {
                $this->statut = new StatutDette(2, StatutDette::SOLDEE, 'Soldée / Intégralement payée');
            } else {
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
