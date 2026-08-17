<?php

namespace App\Model\Entity;

use DateTime;

class User extends AbstractEntity
{
    private string $nom;
    private string $prenom;
    private string $email;
    private string $motDePasse;
    private ?Role $role;
    private bool $actif;
    private ?DateTime $dateCreation;

    public function __construct(
        ?int $id = null,
        string $nom = '',
        string $prenom = '',
        string $email = '',
        string $motDePasse = '',
        ?Role $role = null,
        bool $actif = true,
        ?DateTime $dateCreation = null
    ) {
        parent::__construct($id);
        $this->nom = trim($nom);
        $this->prenom = trim($prenom);
        $this->email = strtolower(trim($email));
        $this->motDePasse = $motDePasse;
        $this->role = $role;
        $this->actif = $actif;
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

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = strtolower(trim($email));
        return $this;
    }

    public function getMotDePasse(): string
    {
        return $this->motDePasse;
    }

    public function setMotDePasse(string $plainPassword): self
    {
        $this->motDePasse = password_hash($plainPassword, PASSWORD_BCRYPT);
        return $this;
    }

    public function setMotDePasseHash(string $hash): self
    {
        $this->motDePasse = $hash;
        return $this;
    }

    public function verifierMotDePasse(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->motDePasse);
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(?Role $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function hasRole(string $codeRole): bool
    {
        if ($this->role !== null) {
            return strtoupper($this->role->getCode()) === strtoupper(trim($codeRole));
        }

        return false;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): self
    {
        $this->actif = $actif;
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
}
