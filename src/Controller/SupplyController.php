<?php

namespace App\Controller;

use App\Core\SessionManager;
use App\Model\Entity\User;
use App\Model\Repository\FournisseurRepository;
use App\Model\Repository\ProduitRepository;
use App\Service\SupplyService;
use Throwable;

class SupplyController
{
    public static function index(): void
    {
        $currentUser = SessionManager::getUser();
        if (!$currentUser && SessionManager::isLoggedIn()) {
            $currentUser = new User(
                id: SessionManager::get('user_id', 1),
                nom: SessionManager::get('user_nom', 'Admin'),
                prenom: SessionManager::get('user_prenom', 'Boutique'),
                email: SessionManager::get('user_email', 'admin@storemanager.pro')
            );
        }

        $approvisionnements = SupplyService::getAllApprovisionnements();
        $stats = SupplyService::getStatistiquesAppro();
        $fournisseurs = FournisseurRepository::findAll();
        $produits = ProduitRepository::findAll();

        $totalCoutEntrees = $stats['total_cout_entrees'];

        require dirname(__DIR__) . '/views/approvisionnements/index.php';
    }

    public static function receptionner(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if (!headers_sent()) {
                header('Location: /supplies');
            }
            return;
        }

        $approId = (int)($_POST['approvisionnement_id'] ?? 0);
        $quantitesLivrees = isset($_POST['quantites_livrees']) && is_array($_POST['quantites_livrees']) 
            ? $_POST['quantites_livrees'] 
            : null;
        $userId = (int)SessionManager::get('user_id', 1);

        try {
            $result = SupplyService::receptionnerBL($approId, $quantitesLivrees, $userId);
            SessionManager::setFlash('success', $result['message']);
        } catch (Throwable $e) {
            SessionManager::setFlash('error', $e->getMessage());
        }

        if (!headers_sent()) {
            header('Location: /supplies');
        }
    }

    public static function creer(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if (!headers_sent()) {
                header('Location: /supplies');
            }
            return;
        }

        $fournisseurId = (int)($_POST['fournisseur_id'] ?? 0);
        $numeroBL = !empty($_POST['numero_bl']) ? trim($_POST['numero_bl']) : null;
        $articles = $_POST['articles'] ?? [];
        $receptionnerDirect = !empty($_POST['receptionner_directement']);
        $userId = (int)SessionManager::get('user_id', 1);

        try {
            $appro = SupplyService::creerApprovisionnement(
                $fournisseurId,
                $articles,
                $userId,
                $numeroBL,
                $receptionnerDirect
            );
            SessionManager::setFlash('success', "Bon de livraison " . $appro->getNumeroBL() . " enregistré avec succès !");
        } catch (Throwable $e) {
            SessionManager::setFlash('error', $e->getMessage());
        }

        if (!headers_sent()) {
            header('Location: /supplies');
        }
    }
}
