<?php

namespace App\Model\Repository;

use App\Model\Entity\Produit;
use App\Model\Entity\Categorie;
use DateTime;
use PDO;

class ProduitRepository extends AbstractRepository
{
    private static string $baseSelect = "SELECT p.*, 
            c.id AS cat_id, c.code AS cat_code, c.libelle AS cat_libelle, c.description AS cat_description 
        FROM produits p 
        LEFT JOIN categories c ON p.categorie_id = c.id";

    public static function findById(int $id): ?Produit
    {
        $stmt = self::getPDO()->prepare(self::$baseSelect . " WHERE p.id = :id LIMIT 1");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();

        return $row ? self::hydrate($row) : null;
    }

    public static function findByCode(string $code): ?Produit
    {
        $stmt = self::getPDO()->prepare(self::$baseSelect . " WHERE UPPER(p.code) = :code LIMIT 1");
        $stmt->bindValue(':code', strtoupper(trim($code)), PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();

        return $row ? self::hydrate($row) : null;
    }

    public static function findAll(): array
    {
        $stmt = self::getPDO()->query(self::$baseSelect . " ORDER BY p.libelle ASC");
        $rows = $stmt->fetchAll();

        return array_map([self::class, 'hydrate'], $rows);
    }

    public static function findByCategorie(int $categorieId): array
    {
        $stmt = self::getPDO()->prepare(self::$baseSelect . " WHERE p.categorie_id = :categorie_id ORDER BY p.libelle ASC");
        $stmt->bindValue(':categorie_id', $categorieId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return array_map([self::class, 'hydrate'], $rows);
    }

    public static function findEnAlerteStock(): array
    {
        $stmt = self::getPDO()->query(self::$baseSelect . " WHERE p.qte_stock <= p.seuil_alerte ORDER BY p.qte_stock ASC, p.libelle ASC");
        $rows = $stmt->fetchAll();

        return array_map([self::class, 'hydrate'], $rows);
    }

    public static function search(string $term): array
    {
        $cleanTerm = '%' . trim(strtolower($term)) . '%';
        $stmt = self::getPDO()->prepare(
            self::$baseSelect . " 
             WHERE LOWER(p.code) LIKE :term 
                OR LOWER(p.libelle) LIKE :term 
                OR LOWER(COALESCE(p.description, '')) LIKE :term 
             ORDER BY p.libelle ASC"
        );
        $stmt->bindValue(':term', $cleanTerm, PDO::PARAM_STR);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return array_map([self::class, 'hydrate'], $rows);
    }

    public static function save(Produit $produit): bool
    {
        $pdo = self::getPDO();
        $catId = $produit->getCategorie()?->getId();

        if ($produit->getId() === null) {
            $stmt = $pdo->prepare(
                "INSERT INTO produits (code, libelle, description, prix_achat, prix_vente, qte_stock, seuil_alerte, categorie_id) 
                 VALUES (:code, :libelle, :description, :prix_achat, :prix_vente, :qte_stock, :seuil_alerte, :categorie_id)"
            );
            $stmt->bindValue(':code', $produit->getCode(), PDO::PARAM_STR);
            $stmt->bindValue(':libelle', $produit->getLibelle(), PDO::PARAM_STR);
            $stmt->bindValue(':description', $produit->getDescription(), PDO::PARAM_STR);
            $stmt->bindValue(':prix_achat', $produit->getPrixAchat());
            $stmt->bindValue(':prix_vente', $produit->getPrixVente());
            $stmt->bindValue(':qte_stock', $produit->getQteStock(), PDO::PARAM_INT);
            $stmt->bindValue(':seuil_alerte', $produit->getSeuilAlerte(), PDO::PARAM_INT);
            $stmt->bindValue(':categorie_id', $catId, $catId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);

            $result = $stmt->execute();
            if ($result) {
                $produit->setId((int)$pdo->lastInsertId());
            }
            return $result;
        }

        $stmt = $pdo->prepare(
            "UPDATE produits SET 
                code = :code, 
                libelle = :libelle, 
                description = :description, 
                prix_achat = :prix_achat, 
                prix_vente = :prix_vente, 
                qte_stock = :qte_stock, 
                seuil_alerte = :seuil_alerte, 
                categorie_id = :categorie_id 
             WHERE id = :id"
        );
        $stmt->bindValue(':code', $produit->getCode(), PDO::PARAM_STR);
        $stmt->bindValue(':libelle', $produit->getLibelle(), PDO::PARAM_STR);
        $stmt->bindValue(':description', $produit->getDescription(), PDO::PARAM_STR);
        $stmt->bindValue(':prix_achat', $produit->getPrixAchat());
        $stmt->bindValue(':prix_vente', $produit->getPrixVente());
        $stmt->bindValue(':qte_stock', $produit->getQteStock(), PDO::PARAM_INT);
        $stmt->bindValue(':seuil_alerte', $produit->getSeuilAlerte(), PDO::PARAM_INT);
        $stmt->bindValue(':categorie_id', $catId, $catId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':id', $produit->getId(), PDO::PARAM_INT);

        return $stmt->execute();
    }

    public static function updateStock(int $id, int $nouvelleQte): bool
    {
        $stmt = self::getPDO()->prepare("UPDATE produits SET qte_stock = :qte WHERE id = :id AND :qte >= 0");
        $stmt->bindValue(':qte', max(0, $nouvelleQte), PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public static function decrementStock(int $id, int $quantite): bool
    {
        $stmt = self::getPDO()->prepare("UPDATE produits SET qte_stock = qte_stock - :qte WHERE id = :id AND qte_stock >= :qte");
        $stmt->bindValue(':qte', $quantite, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public static function incrementStock(int $id, int $quantite): bool
    {
        $stmt = self::getPDO()->prepare("UPDATE produits SET qte_stock = qte_stock + :qte WHERE id = :id");
        $stmt->bindValue(':qte', $quantite, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public static function delete(int $id): bool
    {
        $stmt = self::getPDO()->prepare("DELETE FROM produits WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public static function count(): int
    {
        $stmt = self::getPDO()->query("SELECT COUNT(*) FROM produits");
        return (int)$stmt->fetchColumn();
    }

    public static function countAlertes(): int
    {
        $stmt = self::getPDO()->query("SELECT COUNT(*) FROM produits WHERE qte_stock <= seuil_alerte");
        return (int)$stmt->fetchColumn();
    }

    public static function getValeurTotaleStock(): float
    {
        $stmt = self::getPDO()->query("SELECT COALESCE(SUM(prix_vente * qte_stock), 0) FROM produits");
        return (float)$stmt->fetchColumn();
    }

    protected static function hydrate(array $row): Produit
    {
        $categorie = null;
        if (!empty($row['cat_id'])) {
            $categorie = new Categorie(
                id: (int)$row['cat_id'],
                code: $row['cat_code'] ?? '',
                libelle: $row['cat_libelle'] ?? '',
                description: $row['cat_description'] ?? null
            );
        } elseif (!empty($row['categorie_id'])) {
            $categorie = new Categorie(
                id: (int)$row['categorie_id']
            );
        }

        $dateCreation = null;
        if (!empty($row['created_at'])) {
            $dateCreation = new DateTime($row['created_at']);
        }

        return new Produit(
            id: isset($row['id']) ? (int)$row['id'] : null,
            code: $row['code'] ?? '',
            libelle: $row['libelle'] ?? '',
            description: $row['description'] ?? null,
            prixAchat: isset($row['prix_achat']) ? (float)$row['prix_achat'] : 0.0,
            prixVente: isset($row['prix_vente']) ? (float)$row['prix_vente'] : 0.0,
            qteStock: isset($row['qte_stock']) ? (int)$row['qte_stock'] : 0,
            seuilAlerte: isset($row['seuil_alerte']) ? (int)$row['seuil_alerte'] : 5,
            categorie: $categorie,
            dateCreation: $dateCreation
        );
    }
}
