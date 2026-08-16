<?php

namespace App\Model\Repository;

use App\Model\Entity\Client;
use App\Model\Entity\Dette;
use App\Model\Entity\ModePaiement;
use App\Model\Entity\Paiement;
use App\Model\Entity\StatutDette;
use App\Model\Entity\User;
use App\Model\Entity\Vente;
use DateTime;
use PDO;

class DetteRepository extends AbstractRepository
{
    public function findById(int $id): ?Dette
    {
        $stmt = $this->pdo->prepare(
            "SELECT d.*, 
                    c.nom AS client_nom, c.prenom AS client_prenom, c.telephone AS client_telephone, 
                    c.email AS client_email, c.adresse AS client_adresse, c.limite_credit AS client_limite_credit, 
                    c.total_dettes_actuelles AS client_total_dettes,
                    s.code AS statut_code, s.libelle AS statut_libelle,
                    v.numero_facture AS vente_numero_facture
             FROM dettes d
             LEFT JOIN clients c ON d.client_id = c.id
             LEFT JOIN statuts_dette s ON d.statut_id = s.id
             LEFT JOIN ventes v ON d.vente_id = v.id
             WHERE d.id = :id
             LIMIT 1"
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $dette = $this->hydrate($row);
        $dette = $this->attachDetails($dette);

        return $dette;
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query(
            "SELECT d.*, 
                    c.nom AS client_nom, c.prenom AS client_prenom, c.telephone AS client_telephone, 
                    c.email AS client_email, c.adresse AS client_adresse, c.limite_credit AS client_limite_credit, 
                    c.total_dettes_actuelles AS client_total_dettes,
                    s.code AS statut_code, s.libelle AS statut_libelle,
                    v.numero_facture AS vente_numero_facture
             FROM dettes d
             LEFT JOIN clients c ON d.client_id = c.id
             LEFT JOIN statuts_dette s ON d.statut_id = s.id
             LEFT JOIN ventes v ON d.vente_id = v.id
             ORDER BY d.date_creation DESC, d.id DESC"
        );
        $rows = $stmt->fetchAll();

        return array_map(function ($row) {
            $dette = $this->hydrate($row);
            return $this->attachDetails($dette);
        }, $rows);
    }

    public function findDettesActives(): array
    {
        $stmt = $this->pdo->query(
            "SELECT d.*, 
                    c.nom AS client_nom, c.prenom AS client_prenom, c.telephone AS client_telephone, 
                    c.email AS client_email, c.adresse AS client_adresse, c.limite_credit AS client_limite_credit, 
                    c.total_dettes_actuelles AS client_total_dettes,
                    s.code AS statut_code, s.libelle AS statut_libelle,
                    v.numero_facture AS vente_numero_facture
             FROM dettes d
             LEFT JOIN clients c ON d.client_id = c.id
             LEFT JOIN statuts_dette s ON d.statut_id = s.id
             LEFT JOIN ventes v ON d.vente_id = v.id
             WHERE d.montant_restant > 0 AND d.statut_id != 2
             ORDER BY d.date_creation DESC, d.id DESC"
        );
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    public function findByClient(int $clientId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT d.*, 
                    c.nom AS client_nom, c.prenom AS client_prenom, c.telephone AS client_telephone, 
                    c.email AS client_email, c.adresse AS client_adresse, c.limite_credit AS client_limite_credit, 
                    c.total_dettes_actuelles AS client_total_dettes,
                    s.code AS statut_code, s.libelle AS statut_libelle,
                    v.numero_facture AS vente_numero_facture
             FROM dettes d
             LEFT JOIN clients c ON d.client_id = c.id
             LEFT JOIN statuts_dette s ON d.statut_id = s.id
             LEFT JOIN ventes v ON d.vente_id = v.id
             WHERE d.client_id = :client_id
             ORDER BY d.date_creation DESC, d.id DESC"
        );
        $stmt->bindValue(':client_id', $clientId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    public function findByStatut(int $statutId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT d.*, 
                    c.nom AS client_nom, c.prenom AS client_prenom, c.telephone AS client_telephone, 
                    c.email AS client_email, c.adresse AS client_adresse, c.limite_credit AS client_limite_credit, 
                    c.total_dettes_actuelles AS client_total_dettes,
                    s.code AS statut_code, s.libelle AS statut_libelle,
                    v.numero_facture AS vente_numero_facture
             FROM dettes d
             LEFT JOIN clients c ON d.client_id = c.id
             LEFT JOIN statuts_dette s ON d.statut_id = s.id
             LEFT JOIN ventes v ON d.vente_id = v.id
             WHERE d.statut_id = :statut_id
             ORDER BY d.date_creation DESC, d.id DESC"
        );
        $stmt->bindValue(':statut_id', $statutId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    public function findEnRetard(): array
    {
        $stmt = $this->pdo->query(
            "SELECT d.*, 
                    c.nom AS client_nom, c.prenom AS client_prenom, c.telephone AS client_telephone, 
                    c.email AS client_email, c.adresse AS client_adresse, c.limite_credit AS client_limite_credit, 
                    c.total_dettes_actuelles AS client_total_dettes,
                    s.code AS statut_code, s.libelle AS statut_libelle,
                    v.numero_facture AS vente_numero_facture
             FROM dettes d
             LEFT JOIN clients c ON d.client_id = c.id
             LEFT JOIN statuts_dette s ON d.statut_id = s.id
             LEFT JOIN ventes v ON d.vente_id = v.id
             WHERE d.montant_restant > 0 
               AND (d.statut_id = 3 OR (d.date_echeance IS NOT NULL AND d.date_echeance < CURRENT_TIMESTAMP))
             ORDER BY d.date_echeance ASC"
        );
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    public function save(Dette $dette): bool
    {
        if ($dette->getId() === null) {
            $stmt = $this->pdo->prepare(
                "INSERT INTO dettes (vente_id, client_id, montant_total, montant_restant, date_creation, date_echeance, statut_id)
                 VALUES (:vente_id, :client_id, :montant_total, :montant_restant, :date_creation, :date_echeance, :statut_id)"
            );
            $stmt->bindValue(':vente_id', $dette->getVenteId(), $dette->getVenteId() ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':client_id', $dette->getClientId(), PDO::PARAM_INT);
            $stmt->bindValue(':montant_total', $dette->getMontantTotal());
            $stmt->bindValue(':montant_restant', $dette->getMontantRestant());
            $stmt->bindValue(':date_creation', $dette->getDateCreation()->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(
                ':date_echeance', 
                $dette->getDateEcheance() ? $dette->getDateEcheance()->format('Y-m-d H:i:s') : null, 
                $dette->getDateEcheance() ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $stmt->bindValue(':statut_id', $dette->getStatutId(), PDO::PARAM_INT);

            $result = $stmt->execute();
            if ($result) {
                $dette->setId((int)$this->pdo->lastInsertId());
            }
            return $result;
        }

        $stmt = $this->pdo->prepare(
            "UPDATE dettes SET
                vente_id = :vente_id,
                client_id = :client_id,
                montant_total = :montant_total,
                montant_restant = :montant_restant,
                date_creation = :date_creation,
                date_echeance = :date_echeance,
                statut_id = :statut_id
             WHERE id = :id"
        );
        $stmt->bindValue(':vente_id', $dette->getVenteId(), $dette->getVenteId() ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':client_id', $dette->getClientId(), PDO::PARAM_INT);
        $stmt->bindValue(':montant_total', $dette->getMontantTotal());
        $stmt->bindValue(':montant_restant', $dette->getMontantRestant());
        $stmt->bindValue(':date_creation', $dette->getDateCreation()->format('Y-m-d H:i:s'), PDO::PARAM_STR);
        $stmt->bindValue(
            ':date_echeance', 
            $dette->getDateEcheance() ? $dette->getDateEcheance()->format('Y-m-d H:i:s') : null, 
            $dette->getDateEcheance() ? PDO::PARAM_STR : PDO::PARAM_NULL
        );
        $stmt->bindValue(':statut_id', $dette->getStatutId(), PDO::PARAM_INT);
        $stmt->bindValue(':id', $dette->getId(), PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function updateMontantRestantEtStatut(int $detteId, float $montantRestant, int $statutId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE dettes 
             SET montant_restant = :montant_restant, statut_id = :statut_id 
             WHERE id = :id"
        );
        $stmt->bindValue(':montant_restant', max(0.0, $montantRestant));
        $stmt->bindValue(':statut_id', $statutId, PDO::PARAM_INT);
        $stmt->bindValue(':id', $detteId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function findPaiementsByDetteId(int $detteId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT p.*, 
                    m.code AS mode_code, m.libelle AS mode_libelle, m.est_actif AS mode_est_actif,
                    u.nom AS user_nom, u.prenom AS user_prenom, u.email AS user_email
             FROM paiements p
             LEFT JOIN modes_paiement m ON p.mode_paiement_id = m.id
             LEFT JOIN utilisateurs u ON p.user_id = u.id
             WHERE p.dette_id = :dette_id
             ORDER BY p.date_paiement DESC, p.id DESC"
        );
        $stmt->bindValue(':dette_id', $detteId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydratePaiement'], $rows);
    }

    public function savePaiement(Paiement $paiement): bool
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO paiements (dette_id, montant, date_paiement, mode_paiement_id, reference_paiement, user_id)
             VALUES (:dette_id, :montant, :date_paiement, :mode_paiement_id, :reference_paiement, :user_id)"
        );
        $stmt->bindValue(':dette_id', $paiement->getDetteId(), PDO::PARAM_INT);
        $stmt->bindValue(':montant', $paiement->getMontant());
        $stmt->bindValue(':date_paiement', $paiement->getDatePaiement()->format('Y-m-d H:i:s'), PDO::PARAM_STR);
        $stmt->bindValue(':mode_paiement_id', $paiement->getModePaiementId(), PDO::PARAM_INT);
        $stmt->bindValue(':reference_paiement', $paiement->getReferencePaiement(), PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $paiement->getUserId(), PDO::PARAM_INT);

        $result = $stmt->execute();
        if ($result) {
            $paiement->setId((int)$this->pdo->lastInsertId());
        }
        return $result;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM dettes WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function count(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM dettes");
        return (int)$stmt->fetchColumn();
    }

    public function countActives(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM dettes WHERE montant_restant > 0 AND statut_id != 2");
        return (int)$stmt->fetchColumn();
    }

    public function countSoldees(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM dettes WHERE statut_id = 2 OR montant_restant <= 0");
        return (int)$stmt->fetchColumn();
    }

    public function getTotalEncours(): float
    {
        $stmt = $this->pdo->query("SELECT COALESCE(SUM(montant_restant), 0) FROM dettes WHERE montant_restant > 0");
        return (float)$stmt->fetchColumn();
    }

    public function getTotalRecouvrements(): float
    {
        $stmt = $this->pdo->query("SELECT COALESCE(SUM(montant), 0) FROM paiements");
        return (float)$stmt->fetchColumn();
    }

    public function getTotalCreancesInitiales(): float
    {
        $stmt = $this->pdo->query("SELECT COALESCE(SUM(montant_total), 0) FROM dettes");
        return (float)$stmt->fetchColumn();
    }

    public function findLignesVenteByVenteId(int $venteId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT lv.*, 
                    p.code AS produit_code, p.libelle AS produit_libelle, p.prix_vente AS produit_prix_vente
             FROM lignes_vente lv
             LEFT JOIN produits p ON lv.produit_id = p.id
             WHERE lv.vente_id = :vente_id
             ORDER BY lv.id ASC"
        );
        $stmt->bindValue(':vente_id', $venteId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $lignes = [];
        foreach ($rows as $row) {
            $produit = null;
            if (!empty($row['produit_id'])) {
                $produit = new \App\Model\Entity\Produit(
                    id: (int)$row['produit_id'],
                    code: $row['produit_code'] ?? '',
                    libelle: $row['produit_libelle'] ?? '',
                    prixVente: isset($row['produit_prix_vente']) ? (float)$row['produit_prix_vente'] : 0.0
                );
            }

            $lignes[] = new \App\Model\Entity\LigneVente(
                id: (int)$row['id'],
                venteId: (int)$row['vente_id'],
                produitId: (int)$row['produit_id'],
                produit: $produit,
                quantite: (int)$row['quantite'],
                prixUnitaire: (float)$row['prix_unitaire'],
                remise: isset($row['remise']) ? (float)$row['remise'] : 0.0,
                sousTotal: isset($row['sous_total']) ? (float)$row['sous_total'] : null
            );
        }

        return $lignes;
    }

    protected function attachDetails(Dette $dette): Dette
    {
        if ($dette->getId() !== null) {
            $paiements = $this->findPaiementsByDetteId($dette->getId());
            foreach ($paiements as $paiement) {
                $paiement->setDette($dette);
            }
            $reflection = new \ReflectionClass($dette);
            if ($reflection->hasProperty('paiements')) {
                $prop = $reflection->getProperty('paiements');
                $prop->setAccessible(true);
                $prop->setValue($dette, $paiements);
            }
        }

        if ($dette->getVenteId() !== null && $dette->getVente() !== null) {
            $lignes = $this->findLignesVenteByVenteId($dette->getVenteId());
            foreach ($lignes as $ligne) {
                $dette->getVente()->ajouterLigne($ligne);
            }
        }

        return $dette;
    }

    protected function hydrate(array $row): Dette
    {
        $client = null;
        if (!empty($row['client_id'])) {
            $client = new Client(
                id: (int)$row['client_id'],
                nom: $row['client_nom'] ?? '',
                prenom: $row['client_prenom'] ?? '',
                telephone: $row['client_telephone'] ?? '',
                email: $row['client_email'] ?? null,
                adresse: $row['client_adresse'] ?? null,
                limiteCredit: isset($row['client_limite_credit']) ? (float)$row['client_limite_credit'] : 0.0,
                totalDettesActuelles: isset($row['client_total_dettes']) ? (float)$row['client_total_dettes'] : 0.0
            );
        }

        $statut = null;
        if (!empty($row['statut_id'])) {
            $statut = new StatutDette(
                id: (int)$row['statut_id'],
                code: $row['statut_code'] ?? '',
                libelle: $row['statut_libelle'] ?? ''
            );
        }

        $vente = null;
        if (!empty($row['vente_id'])) {
            $vente = new Vente(
                id: (int)$row['vente_id'],
                numeroFacture: $row['vente_numero_facture'] ?? ''
            );
        }

        $dateCreation = !empty($row['date_creation']) ? new DateTime($row['date_creation']) : new DateTime();
        $dateEcheance = !empty($row['date_echeance']) ? new DateTime($row['date_echeance']) : null;

        return new Dette(
            id: isset($row['id']) ? (int)$row['id'] : null,
            venteId: isset($row['vente_id']) ? (int)$row['vente_id'] : null,
            vente: $vente,
            clientId: isset($row['client_id']) ? (int)$row['client_id'] : 0,
            client: $client,
            montantTotal: isset($row['montant_total']) ? (float)$row['montant_total'] : 0.0,
            montantRestant: isset($row['montant_restant']) ? (float)$row['montant_restant'] : 0.0,
            dateCreation: $dateCreation,
            dateEcheance: $dateEcheance,
            statutId: isset($row['statut_id']) ? (int)$row['statut_id'] : 1,
            statut: $statut
        );
    }

    public function hydratePaiement(array $row): Paiement
    {
        $mode = null;
        if (!empty($row['mode_paiement_id'])) {
            $mode = new ModePaiement(
                id: (int)$row['mode_paiement_id'],
                code: $row['mode_code'] ?? '',
                libelle: $row['mode_libelle'] ?? '',
                estActif: !empty($row['mode_est_actif'])
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

        $datePaiement = !empty($row['date_paiement']) ? new DateTime($row['date_paiement']) : new DateTime();

        return new Paiement(
            id: isset($row['id']) ? (int)$row['id'] : null,
            detteId: isset($row['dette_id']) ? (int)$row['dette_id'] : 0,
            montant: isset($row['montant']) ? (float)$row['montant'] : 0.0,
            datePaiement: $datePaiement,
            modePaiementId: isset($row['mode_paiement_id']) ? (int)$row['mode_paiement_id'] : 1,
            modePaiement: $mode,
            referencePaiement: $row['reference_paiement'] ?? null,
            userId: isset($row['user_id']) ? (int)$row['user_id'] : 1,
            agent: $agent
        );
    }
}
