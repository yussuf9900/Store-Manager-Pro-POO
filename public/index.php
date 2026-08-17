<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Router;
use App\Core\SessionManager;

SessionManager::start();

Router::registerDefaultRoutes();
Router::dispatch();
