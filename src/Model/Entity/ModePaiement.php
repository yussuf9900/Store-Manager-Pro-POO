<?php

namespace App\Model\Entity;

class ModePaiement extends AbstractEntity
{
    public const ESPECES = 'ESPECES';
    public const WAVE = 'WAVE';
    public const ORANGE_MONEY = 'ORANGE_MONEY';
    public const CARTE_BANCAIRE = 'CARTE_BANCAIRE';
    public const DETTE = 'DETTE';

    private string $code;
    private string $libelle;
    private bool $estActif;

    public function __construct(
        ?int $id = null,
        string $code = '',
        string $libelle = '',
        bool $estActif = true
    ) {
        parent::__construct($id);
        $this->code = strtoupper(trim($code));
        $this->libelle = trim($libelle);
        $this->estActif = $estActif;
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

    public function isEstActif(): bool
    {
        return $this->estActif;
    }

    public function setEstActif(bool $estActif): self
    {
        $this->estActif = $estActif;
        return $this;
    }
}
