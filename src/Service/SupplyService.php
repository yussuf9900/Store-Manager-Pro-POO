<?php

namespace App\Service;

use App\Core\Database;
use App\Model\Entity\Approvisionnement;
use App\Model\Entity\Fournisseur;
use App\Model\Entity\LigneApprovisionnement;
use App\Model\Entity\StatutAppro;
use App\Model\Entity\User;
use App\Model\Repository\ApprovisionnementRepository;
use App\Model\Repository\FournisseurRepository;
use App\Model\Repository\ProduitRepository;
use DateTime;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

class SupplyService
{
    public static function creerApprovisionnement(
        int $fournisseurId,
        array $articles,
        int $userId = 1,
        ?string $numeroBL = null,
        bool $receptionnerImmediatement = false
    ): Approvisionnement {
        $fournisseur = FournisseurRepository::findById($fournisseurId);
        if (!$fournisseur) {
            throw new InvalidArgumentException("Le fournisseur sélectionné est introuvable.");
        }

        if (empty($articles)) {
            throw new InvalidArgumentException("Un bon de livraison doit contenir au moins un article.");
        }

        if ($numeroBL === null || trim($numeroBL) === '') {
            $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $fournisseur->getNom()), 0, 3));
            if (empty($prefix)) {
                $prefix = 'BL';
            }
            $numeroBL = 'BL-' . $prefix . '-' . str_pad((string)rand(100, 999), 3, '0', STR_PAD_LEFT);
        } else {
            $numeroBL = trim($numeroBL);
        }

        $pdo = Database::getPDO();
        $pdo->beginTransaction();

        try {
            $lignes = [];
            $total = 0.0;

            foreach ($articles as $art) {
                $produitId = (int)($art['produit_id'] ?? $art['id'] ?? 0);
                $produit = ProduitRepository::findById($produitId);
                if (!$produit) {
                    throw new RuntimeException("Produit #{$produitId} introuvable.");
                }

                $quantite = (int)($art['quantite'] ?? $art['qte'] ?? 1);
                if ($quantite <= 0) {
                    throw new InvalidArgumentException("La quantité commandée doit être strictement positive.");
                }

                $prixAchat = isset($art['prix_achat_unitaire']) 
                    ? (float)$art['prix_achat_unitaire'] 
                    : (isset($art['prix_achat']) ? (float)$art['prix_achat'] : $produit->getPrixAchat());

                if ($prixAchat < 0) {
                    throw new InvalidArgumentException("Le prix d'achat unitaire ne peut pas être négatif.");
                }

                $ligne = new LigneApprovisionnement(
                    produit: $produit,
                    quantite: $quantite,
                    prixAchatUnitaire: $prixAchat
                );

                $lignes[] = $ligne;
                $total += $ligne->getSousTotal();
            }

            $statutCode = $receptionnerImmediatement ? StatutAppro::RECU : StatutAppro::EN_ATTENTE;
            $statutId = $receptionnerImmediatement ? 2 : 1;
            $statutLibelle = $receptionnerImmediatement ? 'Réceptionné' : 'En attente';

            $appro = new Approvisionnement(
                numeroBL: $numeroBL,
                dateAppro: new DateTime(),
                montantTotal: $total,
                statut: new StatutAppro(id: $statutId, code: $statutCode, libelle: $statutLibelle),
                fournisseur: $fournisseur,
                agentStock: new User(id: $userId),
                lignes: $lignes
            );

            ApprovisionnementRepository::save($appro);

            foreach ($lignes as $ligne) {
                $ligne->setApprovisionnement($appro);
                ApprovisionnementRepository::saveLigne($ligne);

                if ($receptionnerImmediatement && $ligne->getProduit()?->getId()) {
                    ProduitRepository::incrementStock($ligne->getProduit()->getId(), $ligne->getQuantite());
                }
            }

            $pdo->commit();

            return $appro;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function receptionnerBL(
        int $approvisionnementId,
        ?array $quantitesLivrees = null,
        int $userId = 1
    ): array {
        $appro = ApprovisionnementRepository::findById($approvisionnementId);
        if (!$appro) {
            throw new InvalidArgumentException("Le bon de livraison #{$approvisionnementId} est introuvable.");
        }

        if ($appro->isRecu()) {
            throw new RuntimeException("Ce bon de livraison ({$appro->getNumeroBL()}) a déjà été réceptionné.");
        }

        if ($appro->isAnnule()) {
            throw new RuntimeException("Ce bon de livraison ({$appro->getNumeroBL()}) est annulé et ne peut pas être réceptionné.");
        }

        $lignes = $appro->getLignes();
        if (empty($lignes)) {
            throw new RuntimeException("Le bon de livraison ne contient aucun article à réceptionner.");
        }

        $pdo = Database::getPDO();
        $pdo->beginTransaction();

        try {
            $totalArticles = 0;
            $nouveauTotal = 0.0;

            foreach ($lignes as $ligne) {
                $qteReçue = $ligne->getQuantite();
                $prodId = $ligne->getProduit()?->getId();

                if ($quantitesLivrees !== null) {
                    if ($ligne->getId() !== null && isset($quantitesLivrees[$ligne->getId()])) {
                        $qteReçue = (int)$quantitesLivrees[$ligne->getId()];
                    } elseif ($prodId !== null && isset($quantitesLivrees[$prodId])) {
                        $qteReçue = (int)$quantitesLivrees[$prodId];
                    }
                }

                if ($qteReçue < 0) {
                    throw new InvalidArgumentException("La quantité livrée ne peut pas être négative.");
                }

                if ($qteReçue !== $ligne->getQuantite()) {
                    $ligne->setQuantite($qteReçue);
                    ApprovisionnementRepository::updateLigne($ligne);
                }

                if ($qteReçue > 0 && $prodId !== null) {
                    ProduitRepository::incrementStock($prodId, $qteReçue);
                }

                $totalArticles += $qteReçue;
                $nouveauTotal += $ligne->getSousTotal();
            }

            ApprovisionnementRepository::updateMontantTotal($approvisionnementId, $nouveauTotal);
            ApprovisionnementRepository::updateStatut($approvisionnementId, 2);

            $pdo->commit();

            return [
                'success' => true,
                'approvisionnement_id' => $approvisionnementId,
                'numero_bl' => $appro->getNumeroBL(),
                'total_articles' => $totalArticles,
                'montant_total' => $nouveauTotal,
                'message' => "Bon de livraison " . $appro->getNumeroBL() . " réceptionné avec succès ! " . $totalArticles . " articles ajoutés au stock."
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function getApprovisionnement(int $id): ?Approvisionnement
    {
        return ApprovisionnementRepository::findById($id);
    }

    public static function getApprovisionnementByBL(string $numeroBL): ?Approvisionnement
    {
        return ApprovisionnementRepository::findByNumeroBL($numeroBL);
    }

    public static function getAllApprovisionnements(): array
    {
        return ApprovisionnementRepository::findAll();
    }

    public static function getApprovisionnementsEnAttente(): array
    {
        return ApprovisionnementRepository::findEnAttente();
    }

    public static function getApprovisionnementsRecus(): array
    {
        return ApprovisionnementRepository::findRecus();
    }

    public static function getStatistiquesAppro(): array
    {
        $totalCout = ApprovisionnementRepository::getTotalCoutEntrees();
        $nombreTotal = ApprovisionnementRepository::count();
        $nombreRecus = count(ApprovisionnementRepository::findRecus());
        $nombreEnAttente = count(ApprovisionnementRepository::findEnAttente());
        $fournisseurs = FournisseurRepository::findAll();

        return [
            'total_cout_entrees' => $totalCout,
            'nombre_bl' => $nombreTotal,
            'nombre_recus' => $nombreRecus,
            'nombre_en_attente' => $nombreEnAttente,
            'fournisseurs_actifs' => count($fournisseurs)
        ];
    }
}
