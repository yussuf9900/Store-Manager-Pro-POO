<?php

namespace App\Service;

use App\Core\Database;
use App\Model\Entity\Vente;
use App\Model\Entity\LigneVente;
use App\Model\Entity\Client;
use App\Model\Entity\Produit;
use App\Model\Entity\User;
use App\Model\Entity\ModePaiement;
use App\Model\Repository\ProduitRepository;
use App\Model\Repository\ClientRepository;
use DateTime;
use InvalidArgumentException;
use RuntimeException;
use PDO;
use Throwable;

class VenteService
{
    private Database $database;
    private ProduitRepository $produitRepository;
    private ClientRepository $clientRepository;

    public function __construct(
        ?Database $database = null,
        ?ProduitRepository $produitRepository = null,
        ?ClientRepository $clientRepository = null
    ) {
        $this->database = $database ?? Database::getInstance();
        $this->produitRepository = $produitRepository ?? new ProduitRepository();
        $this->clientRepository = $clientRepository ?? new ClientRepository();
    }

    /* =========================================================================
     * 1. CALCULS & PRÉPARATION MÉTIER DU PANIER
     * ========================================================================= */

    public function calculerTotauxPanier(array $articles): array
    {
        $totalBrut = 0.0;
        $totalRemises = 0.0;
        $nombreArticles = 0;

        foreach ($articles as $item) {
            $prixUnitaire = (float)($item['prix_unitaire'] ?? 0.0);
            $quantite = (int)($item['quantite'] ?? 0);
            $remise = (float)($item['remise'] ?? 0.0);

            $totalBrut += ($prixUnitaire * $quantite);
            $totalRemises += $remise;
            $nombreArticles += $quantite;
        }

        $totalNet = max(0.0, $totalBrut - $totalRemises);

        return [
            'total_brut' => $totalBrut,
            'total_remises' => $totalRemises,
            'total_net' => $totalNet,
            'nombre_articles' => $nombreArticles,
            'nombre_references' => count($articles),
            'est_vide' => empty($articles)
        ];
    }

    public function preparerLigneArticle(int|string $produitIdOuCode, int $quantite = 1, float $remise = 0.0): array
    {
        if ($quantite <= 0) {
            throw new InvalidArgumentException("La quantité commandée doit être strictement positive.");
        }

        if ($remise < 0) {
            throw new InvalidArgumentException("La remise ne peut pas être négative.");
        }

        $produit = is_numeric($produitIdOuCode)
            ? $this->produitRepository->findById((int)$produitIdOuCode)
            : $this->produitRepository->findByCode((string)$produitIdOuCode);

        if (!$produit) {
            throw new InvalidArgumentException("L'article demandé '" . htmlspecialchars((string)$produitIdOuCode) . "' est introuvable dans le catalogue.");
        }

        if ($produit->getQteStock() < $quantite) {
            throw new RuntimeException(
                sprintf(
                    "Stock insuffisant pour l'article '%s' (ID #%d) : Quantité demandée = %d, Stock disponible = %d.",
                    $produit->getLibelle(),
                    $produit->getId(),
                    $quantite,
                    $produit->getQteStock()
                )
            );
        }

        $sousTotal = max(0.0, ($produit->getPrixVente() * $quantite) - $remise);

        return [
            'produit_id' => $produit->getId(),
            'code' => $produit->getCode(),
            'libelle' => $produit->getLibelle(),
            'prix_unitaire' => $produit->getPrixVente(),
            'quantite' => $quantite,
            'remise' => $remise,
            'sous_total' => $sousTotal,
            'stock_disponible' => $produit->getQteStock()
        ];
    }

    /* =========================================================================
     * 2. VALIDATION DE VENTE & TRANSACTION SQL PDO
     * ========================================================================= */

    public function validerVente(
        int $userId,
        ?int $clientId = null,
        int $modePaiementId = 1,
        float $montantPaye = 0.0,
        array $articles = [],
        ?DateTime $dateEcheance = null
    ): Vente {
        // 1. Vérification panier non vide
        if (empty($articles)) {
            throw new InvalidArgumentException("Impossible de valider la transaction : le panier de vente est vide.");
        }

        // 2. Vérification préliminaire des articles et calculs financiers
        $lignesPreparees = [];
        $montantTotalCalcule = 0.0;

        foreach ($articles as $item) {
            $produitId = (int)($item['produit_id'] ?? $item['id'] ?? 0);
            $quantite = (int)($item['quantite'] ?? 1);
            $remise = max(0.0, (float)($item['remise'] ?? 0.0));

            if ($produitId <= 0 || $quantite <= 0) {
                throw new InvalidArgumentException("Données d'article invalides (Produit ID: {$produitId}, Quantité: {$quantite}).");
            }

            $produit = $this->produitRepository->findById($produitId);
            if (!$produit) {
                throw new InvalidArgumentException("L'article ID #{$produitId} n'existe pas en base de données.");
            }

            if ($produit->getQteStock() < $quantite) {
                throw new RuntimeException(
                    sprintf(
                        "Stock insuffisant pour l'article '%s' (ID #%d) : Quantité demandée = %d, Stock disponible = %d.",
                        $produit->getLibelle(),
                        $produitId,
                        $quantite,
                        $produit->getQteStock()
                    )
                );
            }

            $prixUnitaire = isset($item['prix_unitaire']) && (float)$item['prix_unitaire'] > 0
                ? (float)$item['prix_unitaire']
                : $produit->getPrixVente();

            $sousTotal = max(0.0, ($prixUnitaire * $quantite) - $remise);
            $montantTotalCalcule += $sousTotal;

            $lignesPreparees[] = [
                'produit' => $produit,
                'produit_id' => $produitId,
                'quantite' => $quantite,
                'prix_unitaire' => $prixUnitaire,
                'remise' => $remise,
                'sous_total' => $sousTotal
            ];
        }

        // 3. Traitement des montants et modes de règlement
        $isVenteDette = ($modePaiementId === 5); // 5 = DETTE
        $montantPaye = max(0.0, $montantPaye);

        if (!$isVenteDette && $montantPaye <= 0.0) {
            // Pour les ventes comptant (Espèces, Wave, OM, Carte), paiement complet par défaut
            $montantPaye = $montantTotalCalcule;
        }

        $montantRestant = max(0.0, $montantTotalCalcule - $montantPaye);
        $estACredit = ($montantRestant > 0 || $isVenteDette);

        // 4. Contrôle strict du risque client et du plafond de crédit
        $client = null;
        if ($estACredit) {
            if ($clientId === null || $clientId <= 0) {
                throw new InvalidArgumentException("Un client nominatif est obligatoire pour toute vente à crédit ou avec reste à payer (Montant restant : " . number_format($montantRestant, 0, ',', ' ') . " FCFA).");
            }

            $client = $this->clientRepository->findById($clientId);
            if (!$client) {
                throw new InvalidArgumentException("Le compte client sélectionné (ID #{$clientId}) est introuvable.");
            }

            if (!$client->peutPrendreCredit($montantRestant)) {
                throw new RuntimeException(
                    sprintf(
                        "Plafond de crédit dépassé pour le client '%s' (ID #%d) : Limite autorisée = %.2f FCFA, Encours actuel = %.2f FCFA, Nouveau crédit = %.2f FCFA, Disponible restant = %.2f FCFA.",
                        $client->getNomComplet(),
                        $client->getId(),
                        $client->getLimiteCredit(),
                        $client->getTotalDettesActuelles(),
                        $montantRestant,
                        $client->getCreditDisponible()
                    )
                );
            }
        } elseif ($clientId !== null && $clientId > 0) {
            $client = $this->clientRepository->findById($clientId);
        }

        // 5. Exécution sous Transaction SQL PDO Atomique
        $pdo = Database::getPDO();
        $pdo->beginTransaction();

        try {
            // A. Génération du numéro de facture unique
            $numeroFacture = $this->genererNumeroFacture();

            // B. Insertion de la vente principale
            $dateVenteStr = (new DateTime())->format('Y-m-d H:i:s');
            $stmtVente = $pdo->prepare(
                "INSERT INTO ventes (numero_facture, date_vente, montant_total, montant_paye, montant_restant, mode_paiement_id, statut, client_id, user_id)
                 VALUES (:numero_facture, :date_vente, :montant_total, :montant_paye, :montant_restant, :mode_paiement_id, :statut, :client_id, :user_id)"
            );
            $stmtVente->bindValue(':numero_facture', $numeroFacture, PDO::PARAM_STR);
            $stmtVente->bindValue(':date_vente', $dateVenteStr, PDO::PARAM_STR);
            $stmtVente->bindValue(':montant_total', $montantTotalCalcule);
            $stmtVente->bindValue(':montant_paye', $montantPaye);
            $stmtVente->bindValue(':montant_restant', $montantRestant);
            $stmtVente->bindValue(':mode_paiement_id', $modePaiementId, PDO::PARAM_INT);
            $stmtVente->bindValue(':statut', 'VALIDEE', PDO::PARAM_STR);
            $stmtVente->bindValue(':client_id', $clientId !== null ? $clientId : null, $clientId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmtVente->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmtVente->execute();

            $venteId = (int)$pdo->lastInsertId();

            // C. Insertion des lignes et Décrémentation Atomique de Stock
            $stmtLigne = $pdo->prepare(
                "INSERT INTO lignes_vente (vente_id, produit_id, quantite, prix_unitaire, remise, sous_total)
                 VALUES (:vente_id, :produit_id, :quantite, :prix_unitaire, :remise, :sous_total)"
            );

            $stmtDecStock = $pdo->prepare(
                "UPDATE produits SET qte_stock = qte_stock - :qte WHERE id = :id AND qte_stock >= :qte"
            );

            foreach ($lignesPreparees as $ligne) {
                // Enregistrement de la ligne de vente
                $stmtLigne->bindValue(':vente_id', $venteId, PDO::PARAM_INT);
                $stmtLigne->bindValue(':produit_id', $ligne['produit_id'], PDO::PARAM_INT);
                $stmtLigne->bindValue(':quantite', $ligne['quantite'], PDO::PARAM_INT);
                $stmtLigne->bindValue(':prix_unitaire', $ligne['prix_unitaire']);
                $stmtLigne->bindValue(':remise', $ligne['remise']);
                $stmtLigne->bindValue(':sous_total', $ligne['sous_total']);
                $stmtLigne->execute();

                // Décrémentation atomique
                $stmtDecStock->bindValue(':qte', $ligne['quantite'], PDO::PARAM_INT);
                $stmtDecStock->bindValue(':id', $ligne['produit_id'], PDO::PARAM_INT);
                $stmtDecStock->execute();

                if ($stmtDecStock->rowCount() === 0) {
                    throw new RuntimeException(
                        "Rupture de stock concurrente survenue lors de la validation de l'article '{$ligne['produit']->getLibelle()}'."
                    );
                }
            }

            // D. Traitement de la dette et mise à jour client si vente à crédit
            if ($estACredit && $montantRestant > 0 && $clientId !== null) {
                $echeance = $dateEcheance ?? (new DateTime())->modify('+30 days');

                $stmtDette = $pdo->prepare(
                    "INSERT INTO dettes (vente_id, client_id, montant_total, montant_restant, date_creation, date_echeance, statut_id)
                     VALUES (:vente_id, :client_id, :montant_total, :montant_restant, :date_creation, :date_echeance, :statut_id)"
                );
                $stmtDette->bindValue(':vente_id', $venteId, PDO::PARAM_INT);
                $stmtDette->bindValue(':client_id', $clientId, PDO::PARAM_INT);
                $stmtDette->bindValue(':montant_total', $montantRestant);
                $stmtDette->bindValue(':montant_restant', $montantRestant);
                $stmtDette->bindValue(':date_creation', $dateVenteStr, PDO::PARAM_STR);
                $stmtDette->bindValue(':date_echeance', $echeance->format('Y-m-d H:i:s'), PDO::PARAM_STR);
                $stmtDette->bindValue(':statut_id', 1, PDO::PARAM_INT); // 1 = NON_SOLDEE
                $stmtDette->execute();

                $detteId = (int)$pdo->lastInsertId();

                // Mise à jour de l'encours de dette client
                $stmtClient = $pdo->prepare(
                    "UPDATE clients SET total_dettes_actuelles = total_dettes_actuelles + :montant WHERE id = :id"
                );
                $stmtClient->bindValue(':montant', $montantRestant);
                $stmtClient->bindValue(':id', $clientId, PDO::PARAM_INT);
                $stmtClient->execute();

                // Si un acompte initial a été versé, l'enregistrer dans l'historique des paiements
                if ($montantPaye > 0) {
                    $stmtPaiement = $pdo->prepare(
                        "INSERT INTO paiements (dette_id, montant, date_paiement, mode_paiement_id, reference_paiement, user_id)
                         VALUES (:dette_id, :montant, :date_paiement, :mode_paiement_id, :ref, :user_id)"
                    );
                    $stmtPaiement->bindValue(':dette_id', $detteId, PDO::PARAM_INT);
                    $stmtPaiement->bindValue(':montant', $montantPaye);
                    $stmtPaiement->bindValue(':date_paiement', $dateVenteStr, PDO::PARAM_STR);
                    $stmtPaiement->bindValue(':mode_paiement_id', $modePaiementId, PDO::PARAM_INT);
                    $stmtPaiement->bindValue(':ref', 'ACOMPTE-' . $numeroFacture, PDO::PARAM_STR);
                    $stmtPaiement->bindValue(':user_id', $userId, PDO::PARAM_INT);
                    $stmtPaiement->execute();
                }
            }

            // E. Validation finale de la transaction
            $pdo->commit();

            // Recharger et retourner l'objet Vente complet
            return $this->getVente($venteId);

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /* =========================================================================
     * 3. CONSULTATION & STATISTIQUES
     * ========================================================================= */

    public function getVente(int $id): ?Vente
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare("{$this->getBaseSelect()} WHERE v.id = :id LIMIT 1");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return $this->hydrateVenteWithLignes($row);
    }

    public function getVenteByFacture(string $numeroFacture): ?Vente
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare("{$this->getBaseSelect()} WHERE UPPER(v.numero_facture) = :num LIMIT 1");
        $stmt->bindValue(':num', strtoupper(trim($numeroFacture)), PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return $this->hydrateVenteWithLignes($row);
    }

    public function getVentesDuJour(?DateTime $date = null): array
    {
        $targetDate = ($date ?? new DateTime())->format('Y-m-d');
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare("{$this->getBaseSelect()} WHERE DATE(v.date_vente) = :date_jour ORDER BY v.date_vente DESC, v.id DESC");
        $stmt->bindValue(':date_jour', $targetDate, PDO::PARAM_STR);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrateVenteWithLignes'], $rows);
    }

    public function getVentesClient(int $clientId): array
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare("{$this->getBaseSelect()} WHERE v.client_id = :client_id ORDER BY v.date_vente DESC, v.id DESC");
        $stmt->bindValue(':client_id', $clientId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrateVenteWithLignes'], $rows);
    }

    public function getStatistiquesDuJour(?DateTime $date = null): array
    {
        $targetDate = ($date ?? new DateTime())->format('Y-m-d');
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare(
            "SELECT 
                COUNT(*) AS total_ventes,
                COALESCE(SUM(montant_total), 0) AS total_ca,
                COALESCE(SUM(montant_paye), 0) AS total_encaisse,
                COALESCE(SUM(montant_restant), 0) AS total_credit,
                COALESCE(AVG(montant_total), 0) AS panier_moyen
             FROM ventes 
             WHERE statut = 'VALIDEE' AND DATE(date_vente) = :date_jour"
        );
        $stmt->bindValue(':date_jour', $targetDate, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();

        return [
            'date' => $targetDate,
            'total_ventes' => (int)($row['total_ventes'] ?? 0),
            'chiffre_affaires' => (float)($row['total_ca'] ?? 0.0),
            'montant_encaisse' => (float)($row['total_encaisse'] ?? 0.0),
            'montant_credit' => (float)($row['total_credit'] ?? 0.0),
            'panier_moyen' => round((float)($row['panier_moyen'] ?? 0.0), 2)
        ];
    }

    /* =========================================================================
     * 4. MÉTHODES D'HYDRATATION & REQUÊTES INTERNES
     * ========================================================================= */

    private function getBaseSelect(): string
    {
        return "SELECT v.*,
            c.id AS client_id_pk, c.nom AS client_nom, c.prenom AS client_prenom, c.telephone AS client_telephone,
            c.email AS client_email, c.adresse AS client_adresse, c.limite_credit AS client_limite_credit,
            c.total_dettes_actuelles AS client_total_dettes,
            u.id AS user_id_pk, u.nom AS user_nom, u.prenom AS user_prenom, u.email AS user_email, u.role_id AS user_role_id,
            mp.id AS mp_id, mp.code AS mp_code, mp.libelle AS mp_libelle, mp.est_actif AS mp_actif
        FROM ventes v
        LEFT JOIN clients c ON v.client_id = c.id
        LEFT JOIN utilisateurs u ON v.user_id = u.id
        LEFT JOIN modes_paiement mp ON v.mode_paiement_id = mp.id";
    }

    private function hydrateVenteWithLignes(array $row): Vente
    {
        $client = null;
        if (!empty($row['client_id_pk'])) {
            $client = new Client(
                id: (int)$row['client_id_pk'],
                nom: $row['client_nom'] ?? '',
                prenom: $row['client_prenom'] ?? '',
                telephone: $row['client_telephone'] ?? '',
                email: $row['client_email'] ?? null,
                adresse: $row['client_adresse'] ?? null,
                limiteCredit: isset($row['client_limite_credit']) ? (float)$row['client_limite_credit'] : 0.0,
                totalDettesActuelles: isset($row['client_total_dettes']) ? (float)$row['client_total_dettes'] : 0.0
            );
        }

        $vendeur = null;
        if (!empty($row['user_id_pk'])) {
            $vendeur = new User(
                id: (int)$row['user_id_pk'],
                nom: $row['user_nom'] ?? '',
                prenom: $row['user_prenom'] ?? '',
                email: $row['user_email'] ?? '',
                roleId: isset($row['user_role_id']) ? (int)$row['user_role_id'] : 1
            );
        }

        $modePaiement = null;
        if (!empty($row['mp_id'])) {
            $modePaiement = new ModePaiement(
                id: (int)$row['mp_id'],
                code: $row['mp_code'] ?? '',
                libelle: $row['mp_libelle'] ?? '',
                estActif: !empty($row['mp_actif'])
            );
        }

        $vente = new Vente(
            id: isset($row['id']) ? (int)$row['id'] : null,
            numeroFacture: $row['numero_facture'] ?? '',
            dateVente: !empty($row['date_vente']) ? new DateTime($row['date_vente']) : new DateTime(),
            montantTotal: isset($row['montant_total']) ? (float)$row['montant_total'] : 0.0,
            montantPaye: isset($row['montant_paye']) ? (float)$row['montant_paye'] : 0.0,
            montantRestant: isset($row['montant_restant']) ? (float)$row['montant_restant'] : 0.0,
            modePaiementId: isset($row['mode_paiement_id']) ? (int)$row['mode_paiement_id'] : 1,
            modePaiement: $modePaiement,
            statut: $row['statut'] ?? 'VALIDEE',
            clientId: isset($row['client_id']) && $row['client_id'] !== null ? (int)$row['client_id'] : null,
            client: $client,
            userId: isset($row['user_id']) ? (int)$row['user_id'] : 1,
            vendeur: $vendeur,
            dateCreation: !empty($row['created_at']) ? new DateTime($row['created_at']) : new DateTime()
        );

        if ($vente->getId() !== null) {
            $vente->setLignes($this->getLignesByVenteId($vente->getId()));
        }

        return $vente;
    }

    private function getLignesByVenteId(int $venteId): array
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare(
            "SELECT lv.*, 
                    p.id AS prod_id, p.code AS prod_code, p.libelle AS prod_libelle, p.description AS prod_description,
                    p.prix_achat AS prod_prix_achat, p.prix_vente AS prod_prix_vente, p.qte_stock AS prod_qte_stock,
                    p.seuil_alerte AS prod_seuil_alerte, p.categorie_id AS prod_cat_id
             FROM lignes_vente lv
             JOIN produits p ON lv.produit_id = p.id
             WHERE lv.vente_id = :vente_id
             ORDER BY lv.id ASC"
        );
        $stmt->bindValue(':vente_id', $venteId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return array_map(function ($row) {
            $produit = new Produit(
                id: (int)$row['prod_id'],
                code: $row['prod_code'] ?? '',
                libelle: $row['prod_libelle'] ?? '',
                description: $row['prod_description'] ?? null,
                prixAchat: isset($row['prod_prix_achat']) ? (float)$row['prod_prix_achat'] : 0.0,
                prixVente: isset($row['prod_prix_vente']) ? (float)$row['prod_prix_vente'] : 0.0,
                qteStock: isset($row['prod_qte_stock']) ? (int)$row['prod_qte_stock'] : 0,
                seuilAlerte: isset($row['prod_seuil_alerte']) ? (int)$row['prod_seuil_alerte'] : 5,
                categorieId: isset($row['prod_cat_id']) ? (int)$row['prod_cat_id'] : null
            );

            return new LigneVente(
                id: isset($row['id']) ? (int)$row['id'] : null,
                venteId: isset($row['vente_id']) ? (int)$row['vente_id'] : null,
                produitId: (int)$row['produit_id'],
                produit: $produit,
                quantite: (int)$row['quantite'],
                prixUnitaire: (float)$row['prix_unitaire'],
                remise: (float)($row['remise'] ?? 0.0),
                sousTotal: (float)$row['sous_total']
            );
        }, $rows);
    }

    private function genererNumeroFacture(): string
    {
        $datePart = (new DateTime())->format('Ymd');
        $randomPart = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

        return sprintf("FACT-%s-%s", $datePart, $randomPart);
    }
}
