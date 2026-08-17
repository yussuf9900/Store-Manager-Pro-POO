<?php

namespace App\Model\Entity;

use DateTime;

class Fournisseur extends AbstractEntity
{
    private string $nom;
    private ?string $contactNom;
    private string $telephone;
    private ?string $email;
    private ?string $adresse;
    private ?DateTime $dateCreation;

    public function __construct(
        ?int $id = null,
        string $nom = '',
        ?string $contactNom = null,
        string $telephone = '',
        ?string $email = null,
        ?string $adresse = null,
        ?DateTime $dateCreation = null
    ) {
        parent::__construct($id);
        $this->nom = trim($nom);
        $this->contactNom = $contactNom !== null ? trim($contactNom) : null;
        $this->telephone = trim($telephone);
        $this->email = $email !== null ? strtolower(trim($email)) : null;
        $this->adresse = $adresse !== null ? trim($adresse) : null;
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

    public function getContactNom(): ?string
    {
        return $this->contactNom;
    }

    public function setContactNom(?string $contactNom): self
    {
        $this->contactNom = $contactNom !== null ? trim($contactNom) : null;
        return $this;
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
