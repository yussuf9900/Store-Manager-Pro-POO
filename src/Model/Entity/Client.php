<?php

namespace App\Model\Entity;

use DateTime;
use InvalidArgumentException;

class Client extends AbstractEntity
{
    private string $nom;
    private string $prenom;
    private string $telephone;
    private ?string $email;
    private ?string $adresse;
    private float $limiteCredit;
    private float $totalDettesActuelles;
    private ?DateTime $dateCreation;

    public function __construct(
        ?int $id = null,
        string $nom = '',
        string $prenom = '',
        string $telephone = '',
        ?string $email = null,
        ?string $adresse = null,
        float $limiteCredit = 0.0,
        float $totalDettesActuelles = 0.0,
        ?DateTime $dateCreation = null
    ) {
        parent::__construct($id);
        $this->nom = trim($nom);
        $this->prenom = trim($prenom);
        $this->telephone = trim($telephone);
        $this->email = $email !== null ? strtolower(trim($email)) : null;
        $this->adresse = $adresse !== null ? trim($adresse) : null;
        $this->limiteCredit = max(0.0, $limiteCredit);
        $this->totalDettesActuelles = max(0.0, $totalDettesActuelles);
        $this->dateCreation = $dateCreation ?? new DateTime();
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = trim($nom);
        return $this;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = trim($prenom);
        return $this;
    }

    public function getNomComplet(): string
    {
        return trim($this->prenom . ' ' . $this->nom);
    }

    public function getTelephone(): string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): self
    {
        $this->telephone = trim($telephone);
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email !== null ? strtolower(trim($email)) : null;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): self
    {
        $this->adresse = $adresse !== null ? trim($adresse) : null;
        return $this;
    }

    public function getLimiteCredit(): float
    {
        return $this->limiteCredit;
    }

    public function setLimiteCredit(float $limiteCredit): self
    {
        if ($limiteCredit < 0) {
            throw new InvalidArgumentException("La limite de crédit ne peut pas être négative.");
        }
        $this->limiteCredit = $limiteCredit;
        return $this;
    }

    public function getTotalDettesActuelles(): float
    {
        return $this->totalDettesActuelles;
    }

    public function setTotalDettesActuelles(float $totalDettesActuelles): self
    {
        if ($totalDettesActuelles < 0) {
            throw new InvalidArgumentException("Le total des dettes actuelles ne peut pas être négatif.");
        }
        $this->totalDettesActuelles = $totalDettesActuelles;
        return $this;
    }

    public function getCreditDisponible(): float
    {
        return max(0.0, $this->limiteCredit - $this->totalDettesActuelles);
    }

    public function peutPrendreCredit(float $montant): bool
    {
        if ($montant <= 0) {
            return false;
        }

        return ($this->totalDettesActuelles + $montant) <= $this->limiteCredit;
    }

    public function ajouterDette(float $montant): void
    {
        if ($montant <= 0) {
            throw new InvalidArgumentException("Le montant de la dette à ajouter doit être supérieur à zéro.");
        }

        if (!$this->peutPrendreCredit($montant)) {
            throw new InvalidArgumentException(
                sprintf(
                    "Plafond de crédit dépassé pour le client %s : Limite = %.2f FCFA, Encours actuel = %.2f FCFA, Nouveau montant = %.2f FCFA (Disponible = %.2f FCFA)",
                    $this->getNomComplet(),
                    $this->limiteCredit,
                    $this->totalDettesActuelles,
                    $montant,
                    $this->getCreditDisponible()
                )
            );
        }

        $this->totalDettesActuelles += $montant;
    }

    public function diminuerDette(float $montant): void
    {
        if ($montant <= 0) {
            throw new InvalidArgumentException("Le montant du remboursement doit être supérieur à zéro.");
        }

        $this->totalDettesActuelles = max(0.0, $this->totalDettesActuelles - $montant);
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
