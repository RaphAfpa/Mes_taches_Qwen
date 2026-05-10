<?php
// api/index.php

// --- CORS CONFIGURATION ---------------------------------

$allowedOrigins = [
    'https://ton-domaine.fr',
    'https://www.ton-domaine.fr'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Préflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// --- BOOTSTRAP ------------------------------------------

require_once 'core/router.php';
require_once 'models/Database.php';
require_once 'models/User.php';
require_once 'models/Task.php';
require_once 'controllers/UserController.php';
require_once 'controllers/TaskController.php';

// --- DEPENDENCIES ---------------------------------------

$db = (new Database())->getConnection();

$userController = new UserController($db);
$taskController = new TaskController($db);

$router = new Router();

// --- ROUTES ---------------------------------------------

$router->addRoute('POST', '/login', [$userController, 'login']);
$router->addRoute('POST', '/register', [$userController, 'register']);
$router->addRoute('POST', '/logout', [$userController, 'logout']); // ✅ POST
$router->addRoute('GET',  '/profile', [$userController, 'profile']);
$router->addRoute('PUT',  '/profile', [$userController, 'updateProfile']);
$router->addRoute('DELETE', '/profile', [$userController, 'deleteAccount']);

$router->addRoute('GET',  '/tasks', [$taskController, 'getTasks']);
$router->addRoute('POST', '/tasks', [$taskController, 'createTask']);
$router->addRoute('PUT',  '/tasks', [$taskController, 'updateTask']);
$router->addRoute('DELETE', '/tasks', [$taskController, 'deleteTask']);

// --- DISPATCH -------------------------------------------

$router->dispatch();