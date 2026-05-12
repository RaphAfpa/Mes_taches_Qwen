<?php
// api/index.php

/**
 * =====================================================
 * FRONT CONTROLLER DE L’API
 * =====================================================
 *
 * Rôle :
 * - Point d’entrée unique de l’API
 * - Initialisation de l’application
 * - Déclaration des routes
 * - Gestion globale des erreurs
 *
 * GARANTIE :
 * ➜ Aucune erreur PHP ne peut sortir en HTML
 * ➜ L’API renvoie TOUJOURS du JSON
 */

/* =====================================================
   CONFIGURATION PHP (SÉCURISÉE)
   ===================================================== */

// Afficher toutes les erreurs côté serveur
error_reporting(E_ALL);

// ❌ Ne JAMAIS afficher d’erreur HTML au client
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

// ✅ Journalisation uniquement
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/php_error.log');

/* =====================================================
   GESTION GLOBALE DES ERREURS PHP
   ===================================================== */

/**
 * Capture TOUTES les erreurs fatales PHP
 * (Error, Exception, TypeError, PDOException, etc.)
 * et renvoie toujours du JSON valide.
 */
set_exception_handler(function (Throwable $e): void {
    http_response_code(500);
    header('Content-Type: application/json');

    echo json_encode([
        'success' => false,
        'data'    => null,
        'message' => 'Erreur serveur interne'
    ]);

    // Log détaillé côté serveur
    error_log('[API ERROR] ' . $e->getMessage());
    error_log($e->getTraceAsString());

    exit;
});

/* =====================================================
   CONFIGURATION CORS
   ===================================================== */

$allowedOrigins = [
    'https://tdl.afpa21.fr',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

// Préflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/* =====================================================
   BOOTSTRAP DE L’APPLICATION
   ===================================================== */

require_once __DIR__ . '/core/router.php';

require_once __DIR__ . '/models/Database.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Task.php';

require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/controllers/TaskController.php';

/* =====================================================
   INITIALISATION DES DÉPENDANCES
   ===================================================== */

$db = (new Database())->getConnection();

$userController = new UserController($db);
$taskController = new TaskController($db);

$router = new Router();

/* =====================================================
   DÉCLARATION DES ROUTES
   ===================================================== */

// Authentification
$router->addRoute('POST', '/login',    [$userController, 'login']);
$router->addRoute('POST', '/register', [$userController, 'register']);
$router->addRoute('POST', '/logout',   [$userController, 'logout']);

// Profil utilisateur
$router->addRoute('GET',    '/profile', [$userController, 'profile']);
$router->addRoute('PUT',    '/profile', [$userController, 'updateProfile']);
$router->addRoute('DELETE', '/profile', [$userController, 'deleteAccount']);

// Tâches
$router->addRoute('GET',    '/tasks', [$taskController, 'getTasks']);
$router->addRoute('POST',   '/tasks', [$taskController, 'createTask']);
$router->addRoute('PUT',    '/tasks', [$taskController, 'updateTask']);
$router->addRoute('DELETE', '/tasks', [$taskController, 'deleteTask']);

/* =====================================================
   DISPATCH FINAL
   ===================================================== */

$router->dispatch();