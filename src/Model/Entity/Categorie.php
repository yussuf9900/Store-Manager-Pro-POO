<?php

namespace App\Model\Entity;

class Categorie
{
    private ?int $id;
    private string $code;
    private string $libelle;
    private ?string $description;

    public function __construct(
        ?int $id = null,
        string $code = '',
        string $libelle = '',
        ?string $description = null
    ) {
        $this->id = $id;
        $this->code = strtoupper(trim($code));
        $this->libelle = trim($libelle);
        $this->description = $description;
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
}
