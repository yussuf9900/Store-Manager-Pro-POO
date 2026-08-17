<?php

namespace App\Model\Entity;

class StatutAppro extends AbstractEntity
{
    public const EN_ATTENTE = 'EN_ATTENTE';
    public const RECU = 'RECU';
    public const ANNULE = 'ANNULE';

    private string $code;
    private string $libelle;

    public function __construct(
        ?int $id = null,
        string $code = '',
        string $libelle = ''
    ) {
        parent::__construct($id);
        $this->code = strtoupper(trim($code));
        $this->libelle = trim($libelle);
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
        return $this->code === self::RECU || $this->id === 2;
    }

    public function isEnAttente(): bool
    {
        return $this->code === self::EN_ATTENTE || $this->id === 1;
    }

    public function isAnnule(): bool
    {
        return $this->code === self::ANNULE || $this->id === 3;
    }
}
