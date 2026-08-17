<?php

namespace App\Model\Repository;

use App\Model\Entity\Approvisionnement;
use App\Model\Entity\Fournisseur;
use App\Model\Entity\LigneApprovisionnement;
use App\Model\Entity\Produit;
use App\Model\Entity\StatutAppro;
use App\Model\Entity\User;
use DateTime;
use PDO;

class ApprovisionnementRepository extends AbstractRepository
{
    private string $baseSelect = "SELECT a.*, 
            f.nom AS fournisseur_nom, f.contact_nom AS fournisseur_contact, f.telephone AS fournisseur_telephone, 
            f.email AS fournisseur_email, f.adresse AS fournisseur_adresse,
            s.code AS statut_code, s.libelle AS statut_libelle,
            u.nom AS user_nom, u.prenom AS user_prenom, u.email AS user_email
        FROM approvisionnements a
        LEFT JOIN fournisseurs f ON a.fournisseur_id = f.id
        LEFT JOIN statuts_appro s ON a.statut_id = s.id
        LEFT JOIN utilisateurs u ON a.user_id = u.id";

    public function findById(int $id): ?Approvisionnement
    {
        $stmt = $this->pdo->prepare("{$this->baseSelect} WHERE a.id = :id LIMIT 1");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $appro = $this->hydrate($row);
        return $this->attachDetails($appro);
    }

    public function findByNumeroBL(string $numeroBL): ?Approvisionnement
    {
        $stmt = $this->pdo->prepare("{$this->baseSelect} WHERE UPPER(a.numero_bl) = :bl LIMIT 1");
        $stmt->bindValue(':bl', strtoupper(trim($numeroBL)), PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $appro = $this->hydrate($row);
        return $this->attachDetails($appro);
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("{$this->baseSelect} ORDER BY a.date_appro DESC, a.id DESC");
        $rows = $stmt->fetchAll();

        return array_map(function ($row) {
            $appro = $this->hydrate($row);
            return $this->attachDetails($appro);
        }, $rows);
    }

    public function findByFournisseur(int $fournisseurId): array
    {
        $stmt = $this->pdo->prepare("{$this->baseSelect} WHERE a.fournisseur_id = :fid ORDER BY a.date_appro DESC, a.id DESC");
        $stmt->bindValue(':fid', $fournisseurId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return array_map(function ($row) {
            $appro = $this->hydrate($row);
            return $this->attachDetails($appro);
        }, $rows);
    }

    public function findByStatut(int $statutId): array
    {
        $stmt = $this->pdo->prepare("{$this->baseSelect} WHERE a.statut_id = :sid ORDER BY a.date_appro DESC, a.id DESC");
        $stmt->bindValue(':sid', $statutId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return array_map(function ($row) {
            $appro = $this->hydrate($row);
            return $this->attachDetails($appro);
        }, $rows);
    }

    public function findEnAttente(): array
    {
        return $this->findByStatut(1);
    }

    public function findRecus(): array
    {
        return $this->findByStatut(2);
    }

    public function save(Approvisionnement $appro): bool
    {
        $statutId = $appro->getStatut()?->getId() ?? 1;
        $fournisseurId = $appro->getFournisseur()?->getId() ?? 1;
        $userId = $appro->getAgentStock()?->getId() ?? 1;

        if ($appro->getId() === null) {
            $stmt = $this->pdo->prepare(
                "INSERT INTO approvisionnements (numero_bl, date_appro, montant_total, statut_id, fournisseur_id, user_id, created_at)
                 VALUES (:numero_bl, :date_appro, :montant_total, :statut_id, :fournisseur_id, :user_id, :created_at)"
            );
            $stmt->bindValue(':numero_bl', $appro->getNumeroBL(), PDO::PARAM_STR);
            $stmt->bindValue(':date_appro', $appro->getDateAppro()->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':montant_total', $appro->getMontantTotal());
            $stmt->bindValue(':statut_id', $statutId, PDO::PARAM_INT);
            $stmt->bindValue(':fournisseur_id', $fournisseurId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':created_at', $appro->getDateCreation() ? $appro->getDateCreation()->format('Y-m-d H:i:s') : date('Y-m-d H:i:s'), PDO::PARAM_STR);

            $result = $stmt->execute();
            if ($result) {
                $appro->setId((int)$this->pdo->lastInsertId());
            }
            return $result;
        }

        $stmt = $this->pdo->prepare(
            "UPDATE approvisionnements SET
                numero_bl = :numero_bl,
                date_appro = :date_appro,
                montant_total = :montant_total,
                statut_id = :statut_id,
                fournisseur_id = :fournisseur_id,
                user_id = :user_id
             WHERE id = :id"
        );
        $stmt->bindValue(':numero_bl', $appro->getNumeroBL(), PDO::PARAM_STR);
        $stmt->bindValue(':date_appro', $appro->getDateAppro()->format('Y-m-d H:i:s'), PDO::PARAM_STR);
        $stmt->bindValue(':montant_total', $appro->getMontantTotal());
        $stmt->bindValue(':statut_id', $statutId, PDO::PARAM_INT);
        $stmt->bindValue(':fournisseur_id', $fournisseurId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':id', $appro->getId(), PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function saveLigne(LigneApprovisionnement $ligne): bool
    {
        $approId = $ligne->getApprovisionnement()?->getId();
        $produitId = $ligne->getProduit()?->getId() ?? 0;

        if ($ligne->getId() === null) {
            $stmt = $this->pdo->prepare(
                "INSERT INTO lignes_approvisionnement (approvisionnement_id, produit_id, quantite, prix_achat_unitaire, sous_total)
                 VALUES (:appro_id, :produit_id, :quantite, :prix_achat, :sous_total)"
            );
            $stmt->bindValue(':appro_id', $approId, $approId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':produit_id', $produitId, PDO::PARAM_INT);
            $stmt->bindValue(':quantite', $ligne->getQuantite(), PDO::PARAM_INT);
            $stmt->bindValue(':prix_achat', $ligne->getPrixAchatUnitaire());
            $stmt->bindValue(':sous_total', $ligne->getSousTotal());

            $result = $stmt->execute();
            if ($result) {
                $ligne->setId((int)$this->pdo->lastInsertId());
            }
            return $result;
        }

        return $this->updateLigne($ligne);
    }

    public function updateLigne(LigneApprovisionnement $ligne): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE lignes_approvisionnement SET
                quantite = :quantite,
                prix_achat_unitaire = :prix_achat,
                sous_total = :sous_total
             WHERE id = :id"
        );
        $stmt->bindValue(':quantite', $ligne->getQuantite(), PDO::PARAM_INT);
        $stmt->bindValue(':prix_achat', $ligne->getPrixAchatUnitaire());
        $stmt->bindValue(':sous_total', $ligne->getSousTotal());
        $stmt->bindValue(':id', $ligne->getId(), PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function updateStatut(int $approId, int $statutId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE approvisionnements SET statut_id = :sid WHERE id = :id");
        $stmt->bindValue(':sid', $statutId, PDO::PARAM_INT);
        $stmt->bindValue(':id', $approId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function updateMontantTotal(int $approId, float $montantTotal): bool
    {
        $stmt = $this->pdo->prepare("UPDATE approvisionnements SET montant_total = :total WHERE id = :id");
        $stmt->bindValue(':total', max(0.0, $montantTotal));
        $stmt->bindValue(':id', $approId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function findLignesByApproId(int $approId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT la.*, 
                    p.code AS produit_code, p.libelle AS produit_libelle, p.description AS produit_description,
                    p.prix_achat AS produit_prix_achat, p.prix_vente AS produit_prix_vente, 
                    p.qte_stock AS produit_qte_stock, p.seuil_alerte AS produit_seuil_alerte,
                    p.categorie_id AS produit_categorie_id
             FROM lignes_approvisionnement la
             LEFT JOIN produits p ON la.produit_id = p.id
             WHERE la.approvisionnement_id = :appro_id
             ORDER BY la.id ASC"
        );
        $stmt->bindValue(':appro_id', $approId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrateLigne'], $rows);
    }

    public function attachDetails(Approvisionnement $appro): Approvisionnement
    {
        if ($appro->getId() !== null) {
            $lignes = $this->findLignesByApproId($appro->getId());
            foreach ($lignes as $ligne) {
                $ligne->setApprovisionnement($appro);
            }
            $appro->setLignes($lignes);
        }
        return $appro;
    }

    public function getTotalCoutEntrees(): float
    {
        $stmt = $this->pdo->query("SELECT COALESCE(SUM(montant_total), 0) FROM approvisionnements WHERE statut_id = 2");
        $total = (float)$stmt->fetchColumn();
        if ($total <= 0) {
            $stmtAll = $this->pdo->query("SELECT COALESCE(SUM(montant_total), 0) FROM approvisionnements");
            $total = (float)$stmtAll->fetchColumn();
        }
        return $total;
    }

    public function count(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM approvisionnements");
        return (int)$stmt->fetchColumn();
    }

    public function delete(int $id): bool
    {
        $stmtLignes = $this->pdo->prepare("DELETE FROM lignes_approvisionnement WHERE approvisionnement_id = :id");
        $stmtLignes->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtLignes->execute();

        $stmt = $this->pdo->prepare("DELETE FROM approvisionnements WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function deleteLigne(int $ligneId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM lignes_approvisionnement WHERE id = :id");
        $stmt->bindValue(':id', $ligneId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    protected function hydrate(array $row): Approvisionnement
    {
        $fournisseur = null;
        if (!empty($row['fournisseur_id'])) {
            $fournisseur = new Fournisseur(
                id: (int)$row['fournisseur_id'],
                nom: $row['fournisseur_nom'] ?? '',
                contactNom: $row['fournisseur_contact'] ?? null,
                telephone: $row['fournisseur_telephone'] ?? '',
                email: $row['fournisseur_email'] ?? null,
                adresse: $row['fournisseur_adresse'] ?? null
            );
        }

        $statut = null;
        if (!empty($row['statut_id'])) {
            $statut = new StatutAppro(
                id: (int)$row['statut_id'],
                code: $row['statut_code'] ?? ($row['statut_id'] == 2 ? 'RECU' : ($row['statut_id'] == 3 ? 'ANNULE' : 'EN_ATTENTE')),
                libelle: $row['statut_libelle'] ?? ''
            );
        }

        $agent = null;
        if (!empty($row['user_id'])) {
            $agent = new User(
                id: (int)$row['user_id'],
                nom: $row['user_nom'] ?? '',
                prenom: $row['user_prenom'] ?? '',
                email: $row['user_email'] ?? ''
            );
        }

        $dateAppro = !empty($row['date_appro']) ? new DateTime($row['date_appro']) : new DateTime();
        $dateCreation = !empty($row['created_at']) ? new DateTime($row['created_at']) : null;

        return new Approvisionnement(
            id: isset($row['id']) ? (int)$row['id'] : null,
            numeroBL: $row['numero_bl'] ?? '',
            dateAppro: $dateAppro,
            montantTotal: isset($row['montant_total']) ? (float)$row['montant_total'] : 0.0,
            statut: $statut,
            fournisseur: $fournisseur,
            agentStock: $agent,
            lignes: [],
            dateCreation: $dateCreation
        );
    }

    public function hydrateLigne(array $row): LigneApprovisionnement
    {
        $produit = null;
        if (!empty($row['produit_id'])) {
            $produit = new Produit(
                id: (int)$row['produit_id'],
                code: $row['produit_code'] ?? '',
                libelle: $row['produit_libelle'] ?? '',
                description: $row['produit_description'] ?? null,
                prixAchat: isset($row['produit_prix_achat']) ? (float)$row['produit_prix_achat'] : 0.0,
                prixVente: isset($row['produit_prix_vente']) ? (float)$row['produit_prix_vente'] : 0.0,
                qteStock: isset($row['produit_qte_stock']) ? (int)$row['produit_qte_stock'] : 0,
                seuilAlerte: isset($row['produit_seuil_alerte']) ? (int)$row['produit_seuil_alerte'] : 5
            );
        }

        $appro = null;
        if (!empty($row['approvisionnement_id'])) {
            $appro = new Approvisionnement(id: (int)$row['approvisionnement_id']);
        }

        return new LigneApprovisionnement(
            id: isset($row['id']) ? (int)$row['id'] : null,
            approvisionnement: $appro,
            produit: $produit,
            quantite: isset($row['quantite']) ? (int)$row['quantite'] : 1,
            prixAchatUnitaire: isset($row['prix_achat_unitaire']) ? (float)$row['prix_achat_unitaire'] : 0.0,
            sousTotal: isset($row['sous_total']) ? (float)$row['sous_total'] : null
        );
    }
}
