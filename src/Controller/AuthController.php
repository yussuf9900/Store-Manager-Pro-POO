<?php

namespace App\Controller;

use App\Core\SessionManager;
use App\Service\AuthManager;
use Throwable;

class AuthController
{
    public static function login(): void
    {
        SessionManager::start();

        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($requestMethod === 'POST') {
            try {
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $role = trim($_POST['role'] ?? '');

                $user = null;
                if (!empty($email) && !empty($password)) {
                    $user = AuthManager::authenticate($email, $password);
                } elseif (!empty($role)) {
                    $user = AuthManager::authenticateQuickProfile($role);
                }

                if ($user) {
                    SessionManager::setFlash('success', 'Bienvenue, ' . $user->getNomComplet() . ' !');
                    $target = AuthManager::getDefaultRouteForUser($user);
                    if (!headers_sent()) {
                        header('Location: ' . $target);
                        exit;
                    }
                    return;
                } else {
                    SessionManager::setFlash('error', 'Identifiants invalides. Veuillez vérifier votre email et mot de passe.');
                }
            } catch (Throwable $e) {
                SessionManager::setFlash('error', $e->getMessage());
            }

            if (!headers_sent()) {
                header('Location: /login');
                exit;
            }
        }

        if (AuthManager::isAuthenticated()) {
            $user = AuthManager::getCurrentUser();
            $target = AuthManager::getDefaultRouteForUser($user);
            if (!headers_sent()) {
                header('Location: ' . $target);
                exit;
            }
        }

        $flashSuccess = SessionManager::getFlash('success');
        $flashError = SessionManager::getFlash('error');

        require dirname(__DIR__) . '/views/auth/login.php';
    }

    public static function logout(): void
    {
        AuthManager::logout();
        SessionManager::setFlash('success', 'Vous avez été déconnecté avec succès.');

        if (!headers_sent()) {
            header('Location: /login');
            exit;
        }

        require dirname(__DIR__) . '/views/auth/login.php';
    }
}
