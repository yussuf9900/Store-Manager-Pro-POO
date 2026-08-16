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
    private SupplyService $supplyService;
    private FournisseurRepository $fournisseurRepository;
    private ProduitRepository $produitRepository;

    public function __construct(
        ?SupplyService $supplyService = null,
        ?FournisseurRepository $fournisseurRepository = null,
        ?ProduitRepository $produitRepository = null
    ) {
        $this->supplyService = $supplyService ?? new SupplyService();
        $this->fournisseurRepository = $fournisseurRepository ?? new FournisseurRepository();
        $this->produitRepository = $produitRepository ?? new ProduitRepository();
    }

    public function index(): void
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

        $approvisionnements = $this->supplyService->getAllApprovisionnements();
        $stats = $this->supplyService->getStatistiquesAppro();
        $fournisseurs = $this->fournisseurRepository->findAll();
        $produits = $this->produitRepository->findAll();

        $totalCoutEntrees = $stats['total_cout_entrees'];

        require dirname(__DIR__) . '/views/approvisionnements/index.php';
    }

    public function receptionner(): void
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
            $result = $this->supplyService->receptionnerBL($approId, $quantitesLivrees, $userId);
            SessionManager::setFlash('success', $result['message']);
        } catch (Throwable $e) {
            SessionManager::setFlash('error', $e->getMessage());
        }

        if (!headers_sent()) {
            header('Location: /supplies');
        }
    }

    public function creer(): void
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
            $appro = $this->supplyService->creerApprovisionnement(
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
