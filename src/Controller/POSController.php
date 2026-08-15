<?php

namespace App\Controller;

use App\Core\Database;
use App\Core\SessionManager;
use App\Model\Repository\ProduitRepository;
use App\Model\Repository\ClientRepository;
use App\Model\Repository\CategorieRepository;
use App\Service\VenteService;
use DateTime;
use Throwable;
use PDO;

class POSController
{
    private const SESSION_CART_KEY = 'pos_cart';

    private VenteService $venteService;
    private ProduitRepository $produitRepository;
    private ClientRepository $clientRepository;
    private CategorieRepository $categorieRepository;

    public function __construct(
        ?VenteService $venteService = null,
        ?ProduitRepository $produitRepository = null,
        ?ClientRepository $clientRepository = null,
        ?CategorieRepository $categorieRepository = null
    ) {
        $this->venteService = $venteService ?? new VenteService();
        $this->produitRepository = $produitRepository ?? new ProduitRepository();
        $this->clientRepository = $clientRepository ?? new ClientRepository();
        $this->categorieRepository = $categorieRepository ?? new CategorieRepository();
    }

    public function index(): void
    {
        SessionManager::start();

        $cartItems = SessionManager::get(self::SESSION_CART_KEY, []);
        $cartTotals = $this->venteService->calculerTotauxPanier($cartItems);

        $categories = $this->categorieRepository->findAll();
        $produits = $this->produitRepository->findAll();
        $clients = $this->clientRepository->findAll();

        $modesPaiement = $this->getModesPaiement();

        $statistiques = $this->venteService->getStatistiquesDuJour();
        $ventesRecentes = $this->venteService->getVentesDuJour();

        $derniereVenteId = SessionManager::get('derniere_vente_validee_id');
        $derniereVente = $derniereVenteId ? $this->venteService->getVente((int)$derniereVenteId) : null;
        if ($derniereVenteId) {
            SessionManager::remove('derniere_vente_validee_id');
        }

        $currentUser = SessionManager::getUser();

        $this->render('pos/index', [
            'cartItems' => $cartItems,
            'cartTotals' => $cartTotals,
            'categories' => $categories,
            'produits' => $produits,
            'clients' => $clients,
            'modesPaiement' => $modesPaiement,
            'statistiques' => $statistiques,
            'ventesRecentes' => $ventesRecentes,
            'derniereVente' => $derniereVente,
            'currentUser' => $currentUser,
            'flashSuccess' => SessionManager::getFlash('success'),
            'flashError' => SessionManager::getFlash('error')
        ]);
    }

    public function ajouterArticle(): void
    {
        SessionManager::start();

        $produitId = $_POST['produit_id'] ?? $_GET['produit_id'] ?? null;
        $code = $_POST['code'] ?? $_GET['code'] ?? null;
        $quantite = max(1, (int)($_POST['quantite'] ?? $_GET['quantite'] ?? 1));
        $remise = max(0.0, (float)($_POST['remise'] ?? $_GET['remise'] ?? 0.0));

        $cible = $produitId ?: $code;

        if (!$cible) {
            $this->respondWithError("Aucun article sélectionné.", 400);
            return;
        }

        try {
            $cart = SessionManager::get(self::SESSION_CART_KEY, []);
            $idRecherche = is_numeric($cible) ? (int)$cible : null;

            $qteExistante = 0;
            $remiseExistante = 0.0;

            if ($idRecherche && isset($cart[$idRecherche])) {
                $qteExistante = (int)$cart[$idRecherche]['quantite'];
                $remiseExistante = (float)$cart[$idRecherche]['remise'];
            }

            $nouvelleQte = $qteExistante + $quantite;
            $nouvelleRemise = $remiseExistante + $remise;

            $lignePreparee = $this->venteService->preparerLigneArticle($cible, $nouvelleQte, $nouvelleRemise);
            $realId = $lignePreparee['produit_id'];

            $cart[$realId] = $lignePreparee;
            SessionManager::set(self::SESSION_CART_KEY, $cart);

            SessionManager::setFlash('success', "Article '{$lignePreparee['libelle']}' ajouté au panier (Qté: {$nouvelleQte}).");

            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => "Article ajouté",
                    'cart' => $cart,
                    'totals' => $this->venteService->calculerTotauxPanier($cart)
                ]);
                return;
            }

            $this->redirect('/pos');

        } catch (Throwable $e) {
            $this->respondWithError($e->getMessage(), 400);
        }
    }

    public function modifierQuantite(): void
    {
        SessionManager::start();

        $produitId = (int)($_POST['produit_id'] ?? $_GET['produit_id'] ?? 0);
        $quantite = (int)($_POST['quantite'] ?? $_GET['quantite'] ?? 0);

        if ($produitId <= 0) {
            $this->respondWithError("Identifiant d'article invalide.", 400);
            return;
        }

        $cart = SessionManager::get(self::SESSION_CART_KEY, []);

        if (!isset($cart[$produitId])) {
            $this->respondWithError("L'article demandé n'est pas dans le panier.", 404);
            return;
        }

        if ($quantite <= 0) {
            unset($cart[$produitId]);
            SessionManager::set(self::SESSION_CART_KEY, $cart);
            SessionManager::setFlash('success', "Article retiré du panier.");
        } else {
            try {
                $remiseActuelle = (float)($cart[$produitId]['remise'] ?? 0.0);
                $lignePreparee = $this->venteService->preparerLigneArticle($produitId, $quantite, $remiseActuelle);
                $cart[$produitId] = $lignePreparee;
                SessionManager::set(self::SESSION_CART_KEY, $cart);
                SessionManager::setFlash('success', "Quantité mise à jour ({$quantite}).");
            } catch (Throwable $e) {
                $this->respondWithError($e->getMessage(), 400);
                return;
            }
        }

        if ($this->isAjax()) {
            $this->jsonResponse([
                'success' => true,
                'cart' => $cart,
                'totals' => $this->venteService->calculerTotauxPanier($cart)
            ]);
            return;
        }

        $this->redirect('/pos');
    }

    public function supprimerArticle(): void
    {
        SessionManager::start();

        $produitId = (int)($_POST['produit_id'] ?? $_GET['produit_id'] ?? 0);
        $cart = SessionManager::get(self::SESSION_CART_KEY, []);

        if (isset($cart[$produitId])) {
            $nom = $cart[$produitId]['libelle'] ?? 'Article';
            unset($cart[$produitId]);
            SessionManager::set(self::SESSION_CART_KEY, $cart);
            SessionManager::setFlash('success', "L'article '{$nom}' a été retiré du panier.");
        }

        if ($this->isAjax()) {
            $this->jsonResponse([
                'success' => true,
                'cart' => $cart,
                'totals' => $this->venteService->calculerTotauxPanier($cart)
            ]);
            return;
        }

        $this->redirect('/pos');
    }

    public function viderPanier(): void
    {
        SessionManager::start();
        SessionManager::remove(self::SESSION_CART_KEY);
        SessionManager::setFlash('success', "Le panier a été vidé.");

        if ($this->isAjax()) {
            $this->jsonResponse([
                'success' => true,
                'cart' => [],
                'totals' => $this->venteService->calculerTotauxPanier([])
            ]);
            return;
        }

        $this->redirect('/pos');
    }

    public function validerVente(): void
    {
        SessionManager::start();

        $sessionCart = SessionManager::get(self::SESSION_CART_KEY, []);
        $articles = [];

        if (!empty($_POST['product_ids']) && is_array($_POST['product_ids'])) {
            foreach ($_POST['product_ids'] as $idx => $pid) {
                $pIdInt = (int)$pid;
                $qte = (int)($_POST['product_qtys'][$idx] ?? 1);
                if ($pIdInt > 0 && $qte > 0) {
                    $articles[] = $this->venteService->preparerLigneArticle($pIdInt, $qte);
                }
            }
        } elseif (!empty($sessionCart)) {
            $articles = array_values($sessionCart);
        }

        if (empty($articles)) {
            SessionManager::setFlash('error', "Impossible de valider : le panier est vide.");
            $this->redirect('/pos');
            return;
        }

        $clientId = !empty($_POST['client_id']) ? (int)$_POST['client_id'] : null;

        $modePaiementId = (int)($_POST['mode_paiement_id'] ?? 0);
        if ($modePaiementId <= 0 && !empty($_POST['mode_reglement'])) {
            $modeReg = strtoupper(trim((string)$_POST['mode_reglement']));
            $modePaiementId = match ($modeReg) {
                'ESPECES', 'ESPÈCES', 'CASH' => 1,
                'WAVE' => 2,
                'ORANGE MONEY', 'OM', 'ORANGE_MONEY' => 3,
                'CARTE BANCAIRE', 'CB', 'CARTE', 'CARTE_BANCAIRE' => 4,
                'DETTE', 'CREDIT', 'CRÉDIT', 'A CREDIT', 'À CRÉDIT' => 5,
                default => 1
            };
        }
        if ($modePaiementId <= 0) {
            $modePaiementId = 1;
        }

        $montantPaye = isset($_POST['montant_verse'])
            ? (float)$_POST['montant_verse']
            : (isset($_POST['montant_paye']) ? (float)$_POST['montant_paye'] : 0.0);

        $dateEcheance = !empty($_POST['date_echeance']) ? new DateTime($_POST['date_echeance']) : null;

        $user = SessionManager::getUser();
        $userId = $user ? $user->getId() : 2;

        try {
            $vente = $this->venteService->validerVente(
                userId: $userId,
                clientId: $clientId,
                modePaiementId: $modePaiementId,
                montantPaye: $montantPaye,
                articles: $articles,
                dateEcheance: $dateEcheance
            );

            SessionManager::remove(self::SESSION_CART_KEY);

            SessionManager::set('derniere_vente_validee_id', $vente->getId());
            SessionManager::setFlash('success', "Vente n° {$vente->getNumeroFacture()} enregistrée avec succès ! Montant : " . number_format($vente->getMontantTotal(), 0, ',', ' ') . " FCFA.");

            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => "Vente validée avec succès",
                    'vente_id' => $vente->getId(),
                    'numero_facture' => $vente->getNumeroFacture(),
                    'montant_total' => $vente->getMontantTotal(),
                    'montant_paye' => $vente->getMontantPaye(),
                    'montant_restant' => $vente->getMontantRestant()
                ]);
                return;
            }

            $this->redirect('/pos');

        } catch (Throwable $e) {
            SessionManager::setFlash('error', $e->getMessage());

            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 400);
                return;
            }

            $this->redirect('/pos');
        }
    }

    public function facture(int $id): void
    {
        SessionManager::start();

        $vente = $this->venteService->getVente($id);
        if (!$vente) {
            SessionManager::setFlash('error', "La facture demandée (ID #{$id}) est introuvable.");
            $this->redirect('/pos');
            return;
        }

        $this->render('pos/facture', [
            'vente' => $vente
        ]);
    }

    private function getModesPaiement(): array
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->query("SELECT * FROM modes_paiement WHERE est_actif = TRUE ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function respondWithError(string $message, int $statusCode = 400): void
    {
        SessionManager::setFlash('error', $message);

        if ($this->isAjax()) {
            $this->jsonResponse(['success' => false, 'error' => $message], $statusCode);
            return;
        }

        $this->redirect('/pos');
    }

    private function redirect(string $url): void
    {
        if (!headers_sent()) {
            header("Location: {$url}");
        } else {
            echo "<script>window.location.href = '{$url}';</script>";
        }
        exit;
    }

    private function render(string $viewPath, array $data = []): void
    {
        extract($data);

        $rootViewsDir = dirname(__DIR__, 2) . '/views/' . $viewPath . '.php';
        $srcViewsDir = dirname(__DIR__) . '/view/' . $viewPath . '.php';

        if (file_exists($rootViewsDir)) {
            require $rootViewsDir;
        } elseif (file_exists($srcViewsDir)) {
            require $srcViewsDir;
        } else {
            echo "Erreur : La vue '{$viewPath}' est introuvable (recherché dans: {$rootViewsDir}).";
        }
    }
}
