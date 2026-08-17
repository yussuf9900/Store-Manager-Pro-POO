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

    public static function index(): void
    {
        SessionManager::start();

        $cartItems = SessionManager::get(self::SESSION_CART_KEY, []);
        $cartTotals = VenteService::calculerTotauxPanier($cartItems);

        $categories = CategorieRepository::findAll();
        $produits = ProduitRepository::findAll();
        $clients = ClientRepository::findAll();

        $modesPaiement = self::getModesPaiement();

        $statistiques = VenteService::getStatistiquesDuJour();
        $ventesRecentes = VenteService::getVentesDuJour();

        $derniereVenteId = SessionManager::get('derniere_vente_validee_id');
        $derniereVente = $derniereVenteId ? VenteService::getVente((int)$derniereVenteId) : null;
        if ($derniereVenteId) {
            SessionManager::remove('derniere_vente_validee_id');
        }

        $currentUser = SessionManager::getUser();

        self::render('pos/index', [
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

    public static function ajouterArticle(): void
    {
        SessionManager::start();

        $produitId = $_POST['produit_id'] ?? $_GET['produit_id'] ?? null;
        $code = $_POST['code'] ?? $_GET['code'] ?? null;
        $quantite = max(1, (int)($_POST['quantite'] ?? $_GET['quantite'] ?? 1));
        $remise = max(0.0, (float)($_POST['remise'] ?? $_GET['remise'] ?? 0.0));

        $cible = $produitId ?: $code;

        if (!$cible) {
            self::respondWithError("Aucun article sélectionné.", 400);
            return;
        }

        try {
            $cart = SessionManager::get(self::SESSION_CART_KEY, []);
            $idRecherche = is_numeric($cible) ? (int)$cible : null;

            $qteExistante = 0;
            $remiseExistante = 0.0;

            if ($idRecherche && isset($cart[$idRecherche])) {
                $qteExistante = (int)$cart[$idRecherche]['quantite'];
                $remiseExistante = (float)($cart[$idRecherche]['remise'] ?? 0.0);
            } else {
                foreach ($cart as $k => $item) {
                    if (strtoupper($item['code']) === strtoupper((string)$cible)) {
                        $idRecherche = $k;
                        $qteExistante = (int)$item['quantite'];
                        $remiseExistante = (float)($item['remise'] ?? 0.0);
                        break;
                    }
                }
            }

            $nouvelleQte = $qteExistante + $quantite;
            $nouvelleRemise = $remiseExistante + $remise;

            $lignePreparee = VenteService::preparerLigneArticle($cible, $nouvelleQte, $nouvelleRemise);

            $cart[$lignePreparee['produit_id']] = $lignePreparee;
            SessionManager::set(self::SESSION_CART_KEY, $cart);

            $totals = VenteService::calculerTotauxPanier($cart);

            if (self::isAjax()) {
                self::jsonResponse([
                    'success' => true,
                    'message' => "Article ajouté au panier.",
                    'item' => $lignePreparee,
                    'cart' => $cart,
                    'totals' => $totals
                ]);
                return;
            }

            self::redirect('/pos');

        } catch (Throwable $e) {
            self::respondWithError($e->getMessage(), 400);
        }
    }

    public static function modifierQuantite(): void
    {
        SessionManager::start();

        $produitId = (int)($_POST['produit_id'] ?? $_GET['produit_id'] ?? 0);
        $nouvelleQuantite = (int)($_POST['quantite'] ?? $_GET['quantite'] ?? 0);
        $remise = isset($_POST['remise']) ? max(0.0, (float)$_POST['remise']) : null;

        if ($produitId <= 0) {
            self::respondWithError("Produit non spécifié.", 400);
            return;
        }

        $cart = SessionManager::get(self::SESSION_CART_KEY, []);

        if (!isset($cart[$produitId])) {
            self::respondWithError("L'article demandé n'est pas dans votre panier.", 404);
            return;
        }

        if ($nouvelleQuantite <= 0) {
            unset($cart[$produitId]);
            SessionManager::set(self::SESSION_CART_KEY, $cart);
            $totals = VenteService::calculerTotauxPanier($cart);

            if (self::isAjax()) {
                self::jsonResponse([
                    'success' => true,
                    'message' => "Article retiré du panier.",
                    'cart' => $cart,
                    'totals' => $totals
                ]);
                return;
            }

            self::redirect('/pos');
            return;
        }

        try {
            $remiseAppliquee = $remise !== null ? $remise : (float)($cart[$produitId]['remise'] ?? 0.0);
            $ligne = VenteService::preparerLigneArticle($produitId, $nouvelleQuantite, $remiseAppliquee);

            $cart[$produitId] = $ligne;
            SessionManager::set(self::SESSION_CART_KEY, $cart);

            $totals = VenteService::calculerTotauxPanier($cart);

            if (self::isAjax()) {
                self::jsonResponse([
                    'success' => true,
                    'message' => "Quantité mise à jour.",
                    'item' => $ligne,
                    'cart' => $cart,
                    'totals' => $totals
                ]);
                return;
            }

            self::redirect('/pos');

        } catch (Throwable $e) {
            self::respondWithError($e->getMessage(), 400);
        }
    }

    public static function supprimerArticle(): void
    {
        SessionManager::start();

        $produitId = (int)($_POST['produit_id'] ?? $_GET['produit_id'] ?? 0);

        if ($produitId <= 0) {
            self::respondWithError("Produit non spécifié.", 400);
            return;
        }

        $cart = SessionManager::get(self::SESSION_CART_KEY, []);

        if (isset($cart[$produitId])) {
            unset($cart[$produitId]);
            SessionManager::set(self::SESSION_CART_KEY, $cart);
        }

        $totals = VenteService::calculerTotauxPanier($cart);

        if (self::isAjax()) {
            self::jsonResponse([
                'success' => true,
                'message' => "Article retiré du panier.",
                'cart' => $cart,
                'totals' => $totals
            ]);
            return;
        }

        self::redirect('/pos');
    }

    public static function viderPanier(): void
    {
        SessionManager::start();
        SessionManager::remove(self::SESSION_CART_KEY);

        $totals = VenteService::calculerTotauxPanier([]);

        if (self::isAjax()) {
            self::jsonResponse([
                'success' => true,
                'message' => "Panier vidé avec succès.",
                'cart' => [],
                'totals' => $totals
            ]);
            return;
        }

        self::redirect('/pos');
    }

    public static function validerVente(): void
    {
        SessionManager::start();

        $cart = SessionManager::get(self::SESSION_CART_KEY, []);

        if (empty($cart)) {
            self::respondWithError("Impossible de finaliser la vente : le panier est vide.", 400);
            return;
        }

        $clientId = !empty($_POST['client_id']) ? (int)$_POST['client_id'] : null;
        $modePaiementId = (int)($_POST['mode_paiement_id'] ?? 1);
        $montantPaye = (float)($_POST['montant_paye'] ?? 0.0);
        $dateEcheanceStr = $_POST['date_echeance'] ?? null;
        $dateEcheance = !empty($dateEcheanceStr) ? new DateTime($dateEcheanceStr) : null;

        $userId = (int)SessionManager::get('user_id', 1);

        try {
            $vente = VenteService::validerVente(
                $userId,
                $clientId,
                $modePaiementId,
                $montantPaye,
                array_values($cart),
                $dateEcheance
            );

            SessionManager::remove(self::SESSION_CART_KEY);
            SessionManager::set('derniere_vente_validee_id', $vente->getId());
            SessionManager::setFlash('success', "Vente " . $vente->getNumeroFacture() . " enregistrée avec succès !");

            if (self::isAjax()) {
                self::jsonResponse([
                    'success' => true,
                    'message' => "Vente validée avec succès !",
                    'vente_id' => $vente->getId(),
                    'numero_facture' => $vente->getNumeroFacture(),
                    'montant_total' => $vente->getMontantTotal(),
                    'montant_paye' => $vente->getMontantPaye(),
                    'montant_restant' => $vente->getMontantRestant(),
                    'recu_url' => '/pos/recu?facture=' . urlencode($vente->getNumeroFacture()),
                    'panier_vide' => true
                ]);
                return;
            }

            self::redirect('/pos');

        } catch (Throwable $e) {
            SessionManager::setFlash('error', $e->getMessage());

            if (self::isAjax()) {
                self::jsonResponse([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 400);
                return;
            }

            self::redirect('/pos');
        }
    }

    private static function getModesPaiement(): array
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->query("SELECT * FROM modes_paiement WHERE est_actif = TRUE ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private static function jsonResponse(array $data, int $statusCode = 200): void
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private static function respondWithError(string $message, int $statusCode = 400): void
    {
        SessionManager::setFlash('error', $message);

        if (self::isAjax()) {
            self::jsonResponse(['success' => false, 'error' => $message], $statusCode);
            return;
        }

        self::redirect('/pos');
    }

    private static function redirect(string $url): void
    {
        if (!headers_sent()) {
            header("Location: {$url}");
        } else {
            echo "<script>window.location.href = '{$url}';</script>";
        }
        exit;
    }

    private static function render(string $viewPath, array $data = []): void
    {
        extract($data);

        $viewFile = dirname(__DIR__) . '/views/' . $viewPath . '.php';

        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "Erreur : La vue '{$viewPath}' est introuvable (recherché dans: {$viewFile}).";
        }
    }
}
