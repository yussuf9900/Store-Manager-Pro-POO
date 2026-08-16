<?php

namespace App\Controller;

use App\Core\SessionManager;
use App\Model\Entity\User;
use App\Service\DebtService;
use Throwable;

class DetteController
{
    private DebtService $debtService;

    public function __construct(?DebtService $debtService = null)
    {
        $this->debtService = $debtService ?? new DebtService();
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

        $dettes = $this->debtService->getAllDettes();
        $stats = $this->debtService->getStatistiquesDettes();

        $totalEncours = $stats['total_encours'];
        $totalRecouvrements = $stats['total_recouvrements'];

        require dirname(__DIR__) . '/views/dettes/index.php';
    }

    public function rembourser(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if (!headers_sent()) {
                header('Location: /dettes');
            }
            return;
        }

        $detteId = (int)($_POST['dette_id'] ?? 0);
        $montant = (float)($_POST['montant'] ?? 0.0);
        $modePaiementId = (int)($_POST['mode_paiement_id'] ?? 1);
        $reference = !empty($_POST['reference_paiement']) ? trim($_POST['reference_paiement']) : null;
        $userId = (int)SessionManager::get('user_id', 1);

        try {
            $result = $this->debtService->enregistrerRemboursement(
                $detteId,
                $montant,
                $modePaiementId,
                $userId,
                $reference
            );

            SessionManager::setFlash('success', $result['message']);
        } catch (Throwable $e) {
            SessionManager::setFlash('error', $e->getMessage());
        }

        if (!headers_sent()) {
            header('Location: /dettes');
        }
    }
}
