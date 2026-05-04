// js/auth.js

/**
 * Fichier JavaScript dédié à la logique d'authentification.
 *
 * Ce fichier contient les fonctions principales pour la vérification de l'authentification,
 * la connexion, l'inscription et la déconnexion des utilisateurs.
 *
 * Rôle : Gérer les interactions front-end liées à l'authentification et à la session utilisateur.
 *
 * Pourquoi préférable :
 * - Sépare la logique d'authentification du reste de la logique de l'application (`app.js`),
 *   ce qui améliore la modularité et la lisibilité du code.
 * - Centralise les appels aux API d'authentification.
 * - Expose certaines fonctions globalement (`window.functionName`) pour être utilisées
 *   par d'autres scripts ou directement dans le HTML (bien que cette pratique soit
 *   généralement déconseillée dans le développement JavaScript moderne au profit des modules).
 */

// --- Logique d'authentification ---

/**
 * Vérifie le statut d'authentification de l'utilisateur et redirige si nécessaire.
 *
 * Rôle : Assurer que l'utilisateur est sur la bonne page en fonction de son statut de connexion.
 * Pourquoi préférable :
 * - Améliore l'expérience utilisateur en redirigeant automatiquement vers le tableau de bord
 *   si déjà connecté, ou vers la page de connexion si non authentifié.
 * - Utilise une requête à l'API `/api/profile` comme mécanisme de vérification,
 *   ce qui est une méthode fiable pour confirmer l'état de la session côté serveur.
 * - Gère les erreurs potentielles lors de la vérification d'authentification.
 */
async function checkAuthAndRedirect() {
    const currentPage = window.location.pathname.split('/').pop();

    try {
        // Utilise window.getData car la fonction getData est exposée globalement par app.js
        const user = await window.getData('/api/profile');
        if (user && user.username) {
            // Connecté
            if (currentPage === 'index.php' || currentPage === '') {
                window.location.href = 'taches.php'; // Redirige vers la page des tâches
            }
            else if (currentPage === 'taches.php') {
                document.getElementById('user-pseudo').textContent = user.username;
                // fetchTasks() est dans app.js, sera appelé depuis app.js après la vérification d'authentification
            }
            else if (currentPage === 'profil.php') {
                document.getElementById('profile-username').textContent = user.username;
                document.getElementById('profile-email').textContent = user.email;
            }
        } else {
            // Non connecté
            if (currentPage !== 'index.php' && currentPage !== '') {
                window.location.href = 'index.php'; // Redirige vers la page de connexion
            }
        }
    } catch (error) {
        // En cas d'erreur (ex: session expirée, API inaccessible), redirige vers la page de connexion.
        if (currentPage !== 'index.php' && currentPage !== '') {
            window.location.href = 'index.php';
        }
    }
}

/**
 * Gère la soumission du formulaire de connexion.
 *
 * @param {Event} event L'événement de soumission du formulaire.
 * Rôle : Envoyer les identifiants de l'utilisateur à l'API pour authentification.
 * Pourquoi préférable :
 * - Intercepte la soumission par défaut du formulaire pour gérer la connexion via AJAX.
 * - Utilise `window.postData` (exposé globalement par `app.js`) pour une communication asynchrone avec l'API.
 * - Redirige l'utilisateur vers la page des tâches en cas de succès.
 */
async function handleLogin(event) {
    event.preventDefault(); // Empêche le rechargement de la page
    const form = event.target;
    const username = form.username.value;
    const password = form.password.value;

    const result = await window.postData('/api/login', { username, password });

    if (result.message === "Connexion réussie.") {
        window.location.href = 'taches.php';
    } else {
        // window.showAlert(result.message, 'error'); // Les alertes sont désactivées globalement
    }
}

/**
 * Gère la soumission du formulaire d'inscription.
 *
 * @param {Event} event L'événement de soumission du formulaire.
 * Rôle : Envoyer les informations du nouvel utilisateur à l'API pour la création de compte.
 * Pourquoi préférable :
 * - Intercepte la soumission par défaut du formulaire pour gérer l'inscription via AJAX.
 * - Utilise `window.postData` pour une communication asynchrone avec l'API.
 * - Redirige l'utilisateur vers la page de connexion après une inscription réussie.
 */
async function handleRegister(event) {
    event.preventDefault(); // Empêche le rechargement de la page
    const form = event.target;
    const username = form.username.value;
    const email = form.email.value;
    const password = form.password.value;

    const result = await window.postData('/api/register', { username, email, password });

    if (result.message === "Compte créé avec succès.") {
        // window.showAlert(result.message, 'success'); // Les alertes sont désactivées globalement
        window.location.href = 'index.php'; // Redirige vers la page de connexion
    } else {
        // window.showAlert(result.message, 'error'); // Les alertes sont désactivées globalement
    }
}

/**
 * Gère la déconnexion de l'utilisateur.
 *
 * @param {Event} event L'événement de clic sur le bouton de déconnexion.
 * Rôle : Envoyer une requête à l'API pour terminer la session utilisateur.
 * Pourquoi préférable :
 * - Utilise `window.getData` pour une communication asynchrone avec l'API.
 * - Redirige l'utilisateur vers la page d'accueil/connexion après la déconnexion.
 */
async function handleLogout() {
    const result = await window.getData('/api/logout');
    if (result.message === "Déconnexion réussie.") {
        window.location.href = 'index.php';
    } else {
        // window.showAlert(result.message, 'error'); // Les alertes sont désactivées globalement
    }
}

/**
 * Expose les fonctions d'authentification globalement.
 *
 * Rôle : Rendre ces fonctions accessibles depuis d'autres scripts ou directement
 * depuis le scope global de la fenêtre (window).
 * Pourquoi préférable : Permet une interopérabilité avec d'autres parties du code
 * qui pourraient ne pas être structurées en modules. Cependant, dans les applications
 * JavaScript modernes, l'utilisation de modules (ESM) est la méthode préférée
 * pour gérer les dépendances et éviter la pollution de l'espace de noms global.
 */
window.checkAuthAndRedirect = checkAuthAndRedirect;
window.handleLogin = handleLogin;
window.handleRegister = handleRegister;
window.handleLogout = handleLogout;