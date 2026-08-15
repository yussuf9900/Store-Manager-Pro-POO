<?php

namespace App\Model\Repository;

interface RepositoryInterface
{
    public function findById(int $id): ?object;

    public function findAll(): array;

    public function delete(int $id): bool;

    public function count(): int;
}
