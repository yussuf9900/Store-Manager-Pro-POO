<?php

namespace App\Model\Entity;

class StatutAppro
{
    public const EN_ATTENTE = 'EN_ATTENTE';
    public const RECU = 'RECU';
    public const ANNULE = 'ANNULE';

    private ?int $id;
    private string $code;
    private string $libelle;

    public function __construct(
        ?int $id = null,
        string $code = '',
        string $libelle = ''
    ) {
        $this->id = $id;
        $this->code = strtoupper(trim($code));
        $this->libelle = trim($libelle);
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

    public function isRecu(): bool
    {
        return $this->code === self::RECU;
    }

    public function isEnAttente(): bool
    {
        return $this->code === self::EN_ATTENTE;
    }

    public function isAnnule(): bool
    {
        return $this->code === self::ANNULE;
    }
}
