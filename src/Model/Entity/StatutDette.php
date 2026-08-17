<?php

namespace App\Model\Entity;

class StatutDette extends AbstractEntity
{
    public const NON_SOLDEE = 'NON_SOLDEE';
    public const SOLDEE = 'SOLDEE';
    public const EN_RETARD = 'EN_RETARD';

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

    public function isSoldee(): bool
    {
        return $this->code === self::SOLDEE || $this->id === 2;
    }

    public function isNonSoldee(): bool
    {
        return $this->code === self::NON_SOLDEE || $this->id === 1;
    }

    public function isEnRetard(): bool
    {
        return $this->code === self::EN_RETARD || $this->id === 3;
    }
}
