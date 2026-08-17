<?php

namespace App\Model\Repository;

use App\Model\Entity\Fournisseur;
use DateTime;
use PDO;

class FournisseurRepository extends AbstractRepository
{
    public static function findById(int $id): ?Fournisseur
    {
        $stmt = self::getPDO()->prepare("SELECT * FROM fournisseurs WHERE id = :id LIMIT 1");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();

        return $row ? self::hydrate($row) : null;
    }

    public static function findByTelephone(string $telephone): ?Fournisseur
    {
        $stmt = self::getPDO()->prepare("SELECT * FROM fournisseurs WHERE telephone = :tel LIMIT 1");
        $stmt->bindValue(':tel', trim($telephone), PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();

        return $row ? self::hydrate($row) : null;
    }

    public static function findAll(): array
    {
        $stmt = self::getPDO()->query("SELECT * FROM fournisseurs ORDER BY nom ASC");
        $rows = $stmt->fetchAll();

        return array_map([self::class, 'hydrate'], $rows);
    }

    public static function search(string $term): array
    {
        $cleanTerm = '%' . trim(strtolower($term)) . '%';
        $stmt = self::getPDO()->prepare(
            "SELECT * FROM fournisseurs 
             WHERE LOWER(nom) LIKE :term 
                OR LOWER(COALESCE(contact_nom, '')) LIKE :term 
                OR LOWER(telephone) LIKE :term 
                OR LOWER(COALESCE(email, '')) LIKE :term 
             ORDER BY nom ASC"
        );
        $stmt->bindValue(':term', $cleanTerm, PDO::PARAM_STR);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return array_map([self::class, 'hydrate'], $rows);
    }

    public static function save(Fournisseur $fournisseur): bool
    {
        $pdo = self::getPDO();
        if ($fournisseur->getId() === null) {
            $stmt = $pdo->prepare(
                "INSERT INTO fournisseurs (nom, contact_nom, telephone, email, adresse) 
                 VALUES (:nom, :contact_nom, :telephone, :email, :adresse)"
            );
            $stmt->bindValue(':nom', $fournisseur->getNom(), PDO::PARAM_STR);
            $stmt->bindValue(':contact_nom', $fournisseur->getContactNom(), PDO::PARAM_STR);
            $stmt->bindValue(':telephone', $fournisseur->getTelephone(), PDO::PARAM_STR);
            $stmt->bindValue(':email', $fournisseur->getEmail(), PDO::PARAM_STR);
            $stmt->bindValue(':adresse', $fournisseur->getAdresse(), PDO::PARAM_STR);

            $result = $stmt->execute();
            if ($result) {
                $fournisseur->setId((int)$pdo->lastInsertId());
            }
            return $result;
        }

        $stmt = $pdo->prepare(
            "UPDATE fournisseurs SET 
                nom = :nom, 
                contact_nom = :contact_nom, 
                telephone = :telephone, 
                email = :email, 
                adresse = :adresse 
             WHERE id = :id"
        );
        $stmt->bindValue(':nom', $fournisseur->getNom(), PDO::PARAM_STR);
        $stmt->bindValue(':contact_nom', $fournisseur->getContactNom(), PDO::PARAM_STR);
        $stmt->bindValue(':telephone', $fournisseur->getTelephone(), PDO::PARAM_STR);
        $stmt->bindValue(':email', $fournisseur->getEmail(), PDO::PARAM_STR);
        $stmt->bindValue(':adresse', $fournisseur->getAdresse(), PDO::PARAM_STR);
        $stmt->bindValue(':id', $fournisseur->getId(), PDO::PARAM_INT);

        return $stmt->execute();
    }

    public static function delete(int $id): bool
    {
        $stmt = self::getPDO()->prepare("DELETE FROM fournisseurs WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public static function count(): int
    {
        $stmt = self::getPDO()->query("SELECT COUNT(*) FROM fournisseurs");
        return (int)$stmt->fetchColumn();
    }

    protected static function hydrate(array $row): Fournisseur
    {
        $dateCreation = null;
        if (!empty($row['created_at'])) {
            $dateCreation = new DateTime($row['created_at']);
        }

        return new Fournisseur(
            id: isset($row['id']) ? (int)$row['id'] : null,
            nom: $row['nom'] ?? '',
            contactNom: $row['contact_nom'] ?? null,
            telephone: $row['telephone'] ?? '',
            email: $row['email'] ?? null,
            adresse: $row['adresse'] ?? null,
            dateCreation: $dateCreation
        );
    }
}
