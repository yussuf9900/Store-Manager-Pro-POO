<?php

namespace App\Model\Repository;

use App\Core\Database;
use PDO;

abstract class AbstractRepository implements RepositoryInterface
{
    protected PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getPDO();
    }

    abstract protected function hydrate(array $row): object;

    public function getPDO(): PDO
    {
        return $this->pdo;
    }
}
