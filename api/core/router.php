<?php
// api/core/router.php

/**
 * Classe Router
 *
 * Gère le routage des requêtes HTTP entrantes vers les méthodes de contrôleur appropriées.
 * Son rôle est de mapper une URL et une méthode HTTP spécifiques à une fonction PHP (callback)
 * qui sera exécutée pour traiter la requête.
 *
 * Pourquoi préférable :
 * - Centralise la définition des routes de l'API, rendant l'application plus organisée et facile à maintenir.
 * - Permet une séparation claire des préoccupations entre la gestion des requêtes (routage)
 *   et la logique métier (contrôleurs).
 * - Facilite l'ajout de nouvelles fonctionnalités en définissant simplement de nouvelles routes.
 * - Gère la suppression du préfixe de base de l'URL (`/api` dans ce cas) pour un routage interne cohérent.
 */
class Router {
    private $routes = []; // Tableau associatif pour stocker les routes enregistrées.

    /**
     * Ajoute une nouvelle route au routeur.
     *
     * @param string $method La méthode HTTP (GET, POST, PUT, DELETE) pour cette route.
     * @param string $path Le chemin de l'URL pour cette route (ex: '/login', '/tasks').
     * @param callable $callback La fonction ou méthode de contrôleur à exécuter lorsque cette route est appelée.
     *
     * Rôle : Enregistrer les chemins d'accès de l'API et les fonctions associées.
     * Pourquoi préférable :
     * - Permet de définir des points d'accès clairs pour l'API.
     * - Utilise un tableau associatif pour un accès rapide aux callbacks basés sur la méthode et le chemin.
     * - `strtoupper($method)` assure que les méthodes HTTP sont stockées de manière cohérente (en majuscules).
     */
    public function addRoute($method, $path, $callback) {
        $this->routes[strtoupper($method)][$path] = $callback;
    }

    /**
     * Dispatche la requête HTTP entrante vers la fonction de contrôleur correspondante.
     *
     * Analyse la méthode HTTP et le chemin de l'URL de la requête actuelle,
     * puis tente de trouver une route correspondante parmi celles qui sont enregistrées.
     * Si une correspondance est trouvée, la fonction (callback) associée est exécutée.
     * Si aucune route ne correspond, une réponse 404 (Not Found) est envoyée.
     *
     * Rôle : Exécuter la logique appropriée en fonction de la requête du client.
     * Pourquoi préférable :
     * - Gère la logique complexe de correspondance d'URL, y compris la suppression du préfixe de base.
     *   Ceci est essentiel lorsque l'API est déployée dans un sous-répertoire (ex: `/api/`).
     * - Utilise `parse_url` et `dirname($_SERVER['SCRIPT_NAME'])` pour extraire le chemin de manière robuste.
     * - `call_user_func` permet d'appeler dynamiquement la méthode du contrôleur.
     * - Fournit une gestion des erreurs de base en renvoyant un statut 404 pour les routes non trouvées.
     */
    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD']; // Récupère la méthode HTTP de la requête (GET, POST, etc.)
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); // Récupère le chemin de l'URL de la requête

        // Supprime le préfixe de base de l'URL si l'API est dans un sous-répertoire (ex: /api)
        // Ceci est crucial car le fichier .htaccess redirige toutes les requêtes vers api/index.php,
        // donc le routage interne doit être relatif à la base /api.
        $script_name = $_SERVER['SCRIPT_NAME']; // Ex: /api/index.php
        $base_path = dirname($script_name); // Ex: /api

        // Si le chemin de la requête commence par le chemin de base, le supprime.
        if (strpos($path, $base_path) === 0) {
            $path = substr($path, strlen($base_path));
        }

        // Si le chemin devient vide après la suppression du chemin de base (ex: /api/ devient /), le définit à '/'.
        if (empty($path)) {
            $path = '/';
        }

        // Vérifie si des routes sont définies pour la méthode HTTP actuelle.
        if (isset($this->routes[$method])) {
            // Parcourt les routes enregistrées pour cette méthode.
            foreach ($this->routes[$method] as $routePath => $callback) {
                // Correspondance simple et directe du chemin pour l'instant.
                // Peut être étendu pour des expressions régulières si des routes plus complexes sont nécessaires.
                if ($routePath === $path) {
                    // Exécute la fonction (callback) associée à la route trouvée.
                    call_user_func($callback);
                    return; // Arrête l'exécution après avoir trouvé et exécuté la route.
                }
            }
        }

        // Si aucune route ne correspond à la requête, renvoie une réponse 404 (Not Found).
        http_response_code(404);
        echo json_encode(['message' => 'Not Found']);
    }
}
?>