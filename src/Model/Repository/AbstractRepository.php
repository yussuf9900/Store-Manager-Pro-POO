<?php

namespace App\Model\Repository;

use App\Core\Database;
use PDO;

abstract class AbstractRepository implements RepositoryInterface
{
    protected static function getPDO(): PDO
    {
        return Database::getPDO();
    }

    abstract protected static function hydrate(array $row): object;
}
