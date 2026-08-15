<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Router;
use App\Core\SessionManager;

SessionManager::start();

$router = new Router();
$router->registerDefaultRoutes();
$router->dispatch();
