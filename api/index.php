<?php
// api/index.php - API Entry Point

// Gestion des erreurs
ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('error_reporting', E_ALL);
ini_set('error_log', __DIR__ . '/php_error.log');


header('Content-Type: application/json');

// Allow from any origin
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}

// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");         

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

    exit(0);
}

// Include necessary files
require_once 'core/router.php';
require_once 'models/Database.php';
require_once 'models/User.php';
require_once 'models/Task.php';
require_once 'controllers/UserController.php';
require_once 'controllers/TaskController.php';

// Instantiate database and get connection
$database = new Database();
$db = $database->getConnection();

// Instantiate controllers with database connection
$userController = new UserController($db);
$taskController = new TaskController($db);

// Create router instance
$router = new Router();

// Define API routes
// User routes
$router->addRoute('POST', '/login', [$userController, 'login']);
$router->addRoute('POST', '/register', [$userController, 'register']);
$router->addRoute('GET', '/logout', [$userController, 'logout']);
$router->addRoute('GET', '/profile', [$userController, 'getProfile']);
$router->addRoute('PUT', '/profile', [$userController, 'updateProfile']);
$router->addRoute('DELETE', '/profile', [$userController, 'deleteAccount']);

// Task routes
$router->addRoute('GET', '/tasks', [$taskController, 'getTasks']);
$router->addRoute('POST', '/tasks', [$taskController, 'createTask']);
$router->addRoute('PUT', '/tasks', [$taskController, 'updateTask']);
$router->addRoute('DELETE', '/tasks', [$taskController, 'deleteTask']);

// Dispatch the request
$router->dispatch();