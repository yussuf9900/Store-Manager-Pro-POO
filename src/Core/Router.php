<?php

namespace App\Core;

use App\Controller\AuthController;
use App\Controller\DetteController;
use App\Controller\POSController;
use App\Controller\SupplyController;
use App\Model\Repository\ClientRepository;
use App\Model\Repository\FournisseurRepository;
use App\Model\Repository\ProduitRepository;
use App\Service\VenteService;

class Router
{
    private static array $routes = [];

    public static function registerDefaultRoutes(): void
    {
        self::get('/login', AuthController::class, 'login');
        self::post('/login', AuthController::class, 'login');
        self::get('/logout', AuthController::class, 'logout');
        self::post('/logout', AuthController::class, 'logout');

        self::get('/', POSController::class, 'index');
        self::get('/pos', POSController::class, 'index');
        self::match(['GET', 'POST'], '/pos/ajouter', POSController::class, 'ajouterArticle');
        self::match(['GET', 'POST'], '/pos/quantite', POSController::class, 'modifierQuantite');
        self::match(['GET', 'POST'], '/pos/supprimer', POSController::class, 'supprimerArticle');
        self::match(['GET', 'POST'], '/pos/vider', POSController::class, 'viderPanier');
        self::match(['GET', 'POST'], '/pos/valider', POSController::class, 'validerVente');

        self::get('/dettes', DetteController::class, 'index');
        self::match(['GET', 'POST'], '/dettes/rembourser', DetteController::class, 'rembourser');
        self::get('/dettes/{id}', DetteController::class, 'details');

        self::get('/supplies', SupplyController::class, 'index');
        self::get('/approvisionnements', SupplyController::class, 'index');
        self::match(['GET', 'POST'], '/supplies/receptionner', SupplyController::class, 'receptionner');
        self::match(['GET', 'POST'], '/approvisionnements/receptionner', SupplyController::class, 'receptionner');
        self::match(['GET', 'POST'], '/supplies/creer', SupplyController::class, 'creer');
        self::match(['GET', 'POST'], '/approvisionnements/creer', SupplyController::class, 'creer');

        self::get('/dashboard', function() {
            SessionManager::start();
            $statistiques = VenteService::getStatistiquesDuJour();
            $ventesRecentes = VenteService::getVentesDuJour();
            require dirname(__DIR__) . '/views/dashboard/index.php';
        });

        self::get('/catalog', function() {
            SessionManager::start();
            $produits = ProduitRepository::findAll();
            $clients = ClientRepository::findAll();
            $fournisseurs = FournisseurRepository::findAll();
            $valeurStock = ProduitRepository::getValeurTotaleStock();
            require dirname(__DIR__) . '/views/catalogue/index.php';
        });

        self::get('/catalogue', function() {
            SessionManager::start();
            $produits = ProduitRepository::findAll();
            $clients = ClientRepository::findAll();
            $fournisseurs = FournisseurRepository::findAll();
            $valeurStock = ProduitRepository::getValeurTotaleStock();
            require dirname(__DIR__) . '/views/catalogue/index.php';
        });
    }

    public static function get(string $path, string|callable|array $controller, ?string $action = null): void
    {
        self::add('GET', $path, $controller, $action);
    }

    public static function post(string $path, string|callable|array $controller, ?string $action = null): void
    {
        self::add('POST', $path, $controller, $action);
    }

    public static function match(array $methods, string $path, string|callable|array $controller, ?string $action = null): void
    {
        foreach ($methods as $method) {
            self::add($method, $path, $controller, $action);
        }
    }

    public static function add(string $method, string $path, string|callable|array $controller, ?string $action = null): void
    {
        self::$routes[] = [
            'method' => strtoupper($method),
            'path' => rtrim($path, '/') ?: '/',
            'controller' => $controller,
            'action' => $action
        ];
    }

    public static function dispatch(?string $method = null, ?string $uri = null): mixed
    {
        $requestMethod = strtoupper($method ?? $_SERVER['REQUEST_METHOD'] ?? 'GET');
        $requestUri = rtrim(parse_url($uri ?? $_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/') ?: '/';

        foreach (self::$routes as $route) {
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
                if (is_string($controllerClass) && !class_exists($controllerClass)) {
                    $controllerClass = 'App\\Controller\\' . $controllerClass;
                }

                $action = $route['action'] ?? 'index';

                if (is_string($controllerClass) && method_exists($controllerClass, $action)) {
                    return forward_static_call_array([$controllerClass, $action], $matches);
                }

                if (is_array($controllerClass)) {
                    return forward_static_call_array($controllerClass, $matches);
                }

                return call_user_func_array([$controllerClass, $action], $matches);
            }
        }

        if (!headers_sent()) {
            http_response_code(404);
        }
        echo "404 - Page non trouvée";
        return null;
    }

    public static function clear(): void
    {
        self::$routes = [];
    }
}
