<?php

namespace App\Core;

use App\Controller\AuthController;
use App\Controller\DetteController;
use App\Controller\POSController;
use App\Controller\SupplyController;
use App\Model\Repository\CategorieRepository;
use App\Model\Repository\ClientRepository;
use App\Model\Repository\FournisseurRepository;
use App\Model\Repository\ProduitRepository;
use App\Service\VenteService;

class Router
{
    private array $routes = [];

    public function registerDefaultRoutes(): void
    {
        $this->get('/login', AuthController::class, 'login');
        $this->post('/login', AuthController::class, 'login');
        $this->get('/logout', AuthController::class, 'logout');
        $this->post('/logout', AuthController::class, 'logout');

        $this->get('/', POSController::class, 'index');
        $this->get('/pos', POSController::class, 'index');
        $this->match(['GET', 'POST'], '/pos/ajouter', POSController::class, 'ajouterArticle');
        $this->match(['GET', 'POST'], '/pos/quantite', POSController::class, 'modifierQuantite');
        $this->match(['GET', 'POST'], '/pos/supprimer', POSController::class, 'supprimerArticle');
        $this->match(['GET', 'POST'], '/pos/vider', POSController::class, 'viderPanier');
        $this->match(['GET', 'POST'], '/pos/valider', POSController::class, 'validerVente');

        $this->get('/dettes', DetteController::class, 'index');
        $this->match(['GET', 'POST'], '/dettes/rembourser', DetteController::class, 'rembourser');
        $this->get('/dettes/{id}', DetteController::class, 'details');

        $this->get('/supplies', SupplyController::class, 'index');
        $this->get('/approvisionnements', SupplyController::class, 'index');
        $this->match(['GET', 'POST'], '/supplies/receptionner', SupplyController::class, 'receptionner');
        $this->match(['GET', 'POST'], '/approvisionnements/receptionner', SupplyController::class, 'receptionner');
        $this->match(['GET', 'POST'], '/supplies/creer', SupplyController::class, 'creer');
        $this->match(['GET', 'POST'], '/approvisionnements/creer', SupplyController::class, 'creer');

        $this->get('/dashboard', function() {
            SessionManager::start();
            $venteService = new VenteService();
            $statistiques = $venteService->getStatistiquesDuJour();
            $ventesRecentes = $venteService->getVentesDuJour();
            require __DIR__ . '/../views/dashboard/index.php';
        });

        $this->get('/catalog', function() {
            SessionManager::start();
            $produitRepo = new ProduitRepository();
            $clientRepo = new ClientRepository();
            $fournisseurRepo = new FournisseurRepository();
            $produits = $produitRepo->findAll();
            $clients = $clientRepo->findAll();
            $fournisseurs = $fournisseurRepo->findAll();
            $valeurStock = $produitRepo->getValeurTotaleStock();
            require __DIR__ . '/../views/catalogue/index.php';
        });

        $this->get('/catalogue', function() {
            SessionManager::start();
            $produitRepo = new ProduitRepository();
            $clientRepo = new ClientRepository();
            $fournisseurRepo = new FournisseurRepository();
            $produits = $produitRepo->findAll();
            $clients = $clientRepo->findAll();
            $fournisseurs = $fournisseurRepo->findAll();
            $valeurStock = $produitRepo->getValeurTotaleStock();
            require __DIR__ . '/../views/catalogue/index.php';
        });
    }

    public function get(string $path, string|callable $controller, ?string $action = null): void
    {
        $this->add('GET', $path, $controller, $action);
    }

    public function post(string $path, string|callable $controller, ?string $action = null): void
    {
        $this->add('POST', $path, $controller, $action);
    }

    public function match(array $methods, string $path, string|callable $controller, ?string $action = null): void
    {
        foreach ($methods as $method) {
            $this->add($method, $path, $controller, $action);
        }
    }

    public function add(string $method, string $path, string|callable $controller, ?string $action = null): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => rtrim($path, '/') ?: '/',
            'controller' => $controller,
            'action' => $action
        ];
    }

    public function dispatch(?string $method = null, ?string $uri = null): mixed
    {
        $requestMethod = strtoupper($method ?? $_SERVER['REQUEST_METHOD'] ?? 'GET');
        $requestUri = rtrim(parse_url($uri ?? $_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $requestMethod) {
                continue;
            }

            $pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([^/]+)', $route['path']);
            if (preg_match('#^' . $pattern . '$#', $requestUri, $matches)) {
                array_shift($matches);

                if (is_callable($route['controller'])) {
                    return call_user_func_array($route['controller'], $matches);
                }

                $controllerClass = $route['controller'];
                if (!class_exists($controllerClass)) {
                    $controllerClass = 'App\\Controller\\' . $controllerClass;
                }

                $controller = new $controllerClass();
                $action = $route['action'] ?? 'index';

                return call_user_func_array([$controller, $action], $matches);
            }
        }

        if (!headers_sent()) {
            http_response_code(404);
        }
        echo "404 - Page non trouvée";
        return null;
    }
}
