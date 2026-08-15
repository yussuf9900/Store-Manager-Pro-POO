<?php

namespace App\Model\Repository;

use App\Model\Entity\Categorie;
use PDO;

class CategorieRepository extends AbstractRepository
{
    public function findById(int $id): ?Categorie
    {
        $stmt = $this->pdo->prepare("SELECT * FROM categories WHERE id = :id LIMIT 1");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByCode(string $code): ?Categorie
    {
        $stmt = $this->pdo->prepare("SELECT * FROM categories WHERE UPPER(code) = :code LIMIT 1");
        $stmt->bindValue(':code', strtoupper(trim($code)), PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM categories ORDER BY libelle ASC");
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    public function save(Categorie $categorie): bool
    {
        if ($categorie->getId() === null) {
            $stmt = $this->pdo->prepare(
                "INSERT INTO categories (code, libelle, description) VALUES (:code, :libelle, :description)"
            );
            $stmt->bindValue(':code', $categorie->getCode(), PDO::PARAM_STR);
            $stmt->bindValue(':libelle', $categorie->getLibelle(), PDO::PARAM_STR);
            $stmt->bindValue(':description', $categorie->getDescription(), PDO::PARAM_STR);

            $result = $stmt->execute();
            if ($result) {
                $categorie->setId((int)$this->pdo->lastInsertId());
            }
            return $result;
        }

        $stmt = $this->pdo->prepare(
            "UPDATE categories SET code = :code, libelle = :libelle, description = :description WHERE id = :id"
        );
        $stmt->bindValue(':code', $categorie->getCode(), PDO::PARAM_STR);
        $stmt->bindValue(':libelle', $categorie->getLibelle(), PDO::PARAM_STR);
        $stmt->bindValue(':description', $categorie->getDescription(), PDO::PARAM_STR);
        $stmt->bindValue(':id', $categorie->getId(), PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM categories WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function count(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM categories");
        return (int)$stmt->fetchColumn();
    }

    protected function hydrate(array $row): Categorie
    {
        return new Categorie(
            id: isset($row['id']) ? (int)$row['id'] : null,
            code: $row['code'] ?? '',
            libelle: $row['libelle'] ?? '',
            description: $row['description'] ?? null
        );
    }
}
