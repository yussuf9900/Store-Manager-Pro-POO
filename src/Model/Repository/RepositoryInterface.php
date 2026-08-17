<?php

namespace App\Model\Repository;

interface RepositoryInterface
{
    public static function findById(int $id): ?object;

    public static function findAll(): array;

    public static function delete(int $id): bool;

    public static function count(): int;
}
