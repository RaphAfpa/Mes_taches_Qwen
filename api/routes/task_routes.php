<?php
// api/routes/task_routes.php

/**
 * Fichier de routage pour les tâches.
 *
 * Ce fichier contient la logique de routage pour les tâches.
 * Il utilise le contrôleur TaskController pour gérer les requêtes.
 */

require_once __DIR__ . '/../controllers/TaskController.php';

// Initialise le contrôleur TaskController
$taskController = new TaskController($db);

// Route pour récupérer les tâches
if ($_SERVER['REQUEST_METHOD'] === 'GET' && strpos($_SERVER['REQUEST_URI'], '/api/tasks') !== false) {
    $taskController->getTasks();
}

// Route pour créer une nouvelle tâche
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/api/tasks') !== false) {
    $taskController->createTask();
}

// Route pour mettre à jour une tâche existante
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && strpos($_SERVER['REQUEST_URI'], '/api/tasks') !== false) {
    $taskController->updateTask();
}

// Route pour supprimer une tâche
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && strpos($_SERVER['REQUEST_URI'], '/api/tasks') !== false) {
    $taskController->deleteTask();
}
?>