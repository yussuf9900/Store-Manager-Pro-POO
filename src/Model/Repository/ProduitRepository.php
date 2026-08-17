<?php

namespace App\Model\Repository;

use App\Model\Entity\Produit;
use App\Model\Entity\Categorie;
use DateTime;
use PDO;

class ProduitRepository extends AbstractRepository
{
    private string $baseSelect = "SELECT p.*, 
            c.id AS cat_id, c.code AS cat_code, c.libelle AS cat_libelle, c.description AS cat_description 
        FROM produits p 
        LEFT JOIN categories c ON p.categorie_id = c.id";

    public function findById(int $id): ?Produit
    {
        $stmt = $this->pdo->prepare("{$this->baseSelect} WHERE p.id = :id LIMIT 1");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByCode(string $code): ?Produit
    {
        $stmt = $this->pdo->prepare("{$this->baseSelect} WHERE UPPER(p.code) = :code LIMIT 1");
        $stmt->bindValue(':code', strtoupper(trim($code)), PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("{$this->baseSelect} ORDER BY p.libelle ASC");
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    public function findByCategorie(int $categorieId): array
    {
        $stmt = $this->pdo->prepare("{$this->baseSelect} WHERE p.categorie_id = :categorie_id ORDER BY p.libelle ASC");
        $stmt->bindValue(':categorie_id', $categorieId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    public function findEnAlerteStock(): array
    {
        $stmt = $this->pdo->query("{$this->baseSelect} WHERE p.qte_stock <= p.seuil_alerte ORDER BY p.qte_stock ASC, p.libelle ASC");
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    public function search(string $term): array
    {
        $cleanTerm = '%' . trim(strtolower($term)) . '%';
        $stmt = $this->pdo->prepare(
            "{$this->baseSelect} 
             WHERE LOWER(p.code) LIKE :term 
                OR LOWER(p.libelle) LIKE :term 
                OR LOWER(COALESCE(p.description, '')) LIKE :term 
             ORDER BY p.libelle ASC"
        );
        $stmt->bindValue(':term', $cleanTerm, PDO::PARAM_STR);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    public function save(Produit $produit): bool
    {
        $catId = $produit->getCategorie()?->getId();

        if ($produit->getId() === null) {
            $stmt = $this->pdo->prepare(
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
                $produit->setId((int)$this->pdo->lastInsertId());
            }
            return $result;
        }

        $stmt = $this->pdo->prepare(
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

    public function updateStock(int $id, int $nouvelleQte): bool
    {
        $stmt = $this->pdo->prepare("UPDATE produits SET qte_stock = :qte WHERE id = :id AND :qte >= 0");
        $stmt->bindValue(':qte', max(0, $nouvelleQte), PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function decrementStock(int $id, int $quantite): bool
    {
        $stmt = $this->pdo->prepare("UPDATE produits SET qte_stock = qte_stock - :qte WHERE id = :id AND qte_stock >= :qte");
        $stmt->bindValue(':qte', $quantite, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function incrementStock(int $id, int $quantite): bool
    {
        $stmt = $this->pdo->prepare("UPDATE produits SET qte_stock = qte_stock + :qte WHERE id = :id");
        $stmt->bindValue(':qte', $quantite, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM produits WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function count(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM produits");
        return (int)$stmt->fetchColumn();
    }

    public function countAlertes(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM produits WHERE qte_stock <= seuil_alerte");
        return (int)$stmt->fetchColumn();
    }

    protected function hydrate(array $row): Produit
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
