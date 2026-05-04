// js/app.js

/**
 * Fichier principal de l'application JavaScript.
 *
 * Ce fichier contient la logique front-end pour la gestion des tâches,
 * l'authentification des utilisateurs et les interactions avec l'API.
 * Il est structuré en sections pour une meilleure organisation du code.
 */

// --- Fonctions utilitaires ---

/**
 * Affiche un élément HTML en modifiant son style d'affichage à 'block'.
 *
 * @param {string} elementId L'ID de l'élément HTML à afficher.
 * Rôle : Simplifier l'affichage d'éléments cachés.
 * Pourquoi préférable : Permet de manipuler la visibilité des éléments de manière réutilisable.
 */
function showElement(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.style.display = 'block';
    }
}

/**
 * Cache un élément HTML en modifiant son style d'affichage à 'none'.
 *
 * @param {string} elementId L'ID de l'élément HTML à cacher.
 * Rôle : Simplifier le masquage d'éléments visibles.
 * Pourquoi préférable : Permet de manipuler la visibilité des éléments de manière réutilisable.
 */
function hideElement(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.style.display = 'none';
    }
}

/**
 * Fonction d'alerte simplifiée. Actuellement désactivée pour la production.
 *
 * @param {string} message Le message à afficher.
 * @param {string} type Le type d'alerte (ex: 'info', 'success', 'error').
 * Rôle : Fournir un mécanisme de feedback à l'utilisateur.
 * Pourquoi préférable : Dans un environnement de production, cette fonction serait remplacée
 * par un système de notification UI plus sophistiqué (ex: toasts, modales) pour une meilleure
 * expérience utilisateur et un contrôle accru sur l'affichage des messages.
 */
function showAlert(message, type = 'info') {
    // Alerts are disabled as per user request to remove debug code.
    // In a production environment, this would be replaced with a more sophisticated UI notification.
}

/**
 * Envoie une requête POST à une URL spécifiée avec des données JSON.
 *
 * @param {string} url L'URL de l'API.
 * @param {object} data Les données à envoyer au format JSON.
 * @returns {Promise<object>} Une promesse qui résout avec la réponse JSON de l'API.
 * Rôle : Faciliter les requêtes d'envoi de données (création, connexion).
 * Pourquoi préférable : Utilise l'API Fetch moderne pour les requêtes HTTP asynchrones,
 * qui est basée sur les promesses et offre une interface plus puissante et flexible
 * que les anciennes méthodes comme XMLHttpRequest.
 */
async function postData(url, data) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data),
        credentials: 'same-origin'
    });
    return response.json();
}

/**
 * Envoie une requête GET à une URL spécifiée.
 *
 * @param {string} url L'URL de l'API.
 * @returns {Promise<object>} Une promesse qui résout avec la réponse JSON de l'API.
 * Rôle : Faciliter les requêtes de récupération de données.
 * Pourquoi préférable : Utilise l'API Fetch pour des requêtes GET asynchrones,
 * permettant de récupérer des données sans recharger la page.
 */
async function getData(url) {
    const response = await fetch(url, {
        credentials: 'same-origin'
    });
    return response.json();
}

/**
 * Envoie une requête PUT à une URL spécifiée avec des données JSON.
 *
 * @param {string} url L'URL de l'API.
 * @param {object} data Les données à envoyer au format JSON.
 * @returns {Promise<object>} Une promesse qui résout avec la réponse JSON de l'API.
 * Rôle : Faciliter les requêtes de mise à jour de données.
 * Pourquoi préférable : Utilise l'API Fetch pour les requêtes PUT asynchrones,
 * standard pour les opérations de mise à jour dans les API RESTful.
 */
async function putData(url, data) {
    const response = await fetch(url, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data),
        credentials: 'same-origin'
    });
    return response.json();
}

/**
 * Envoie une requête DELETE à une URL spécifiée.
 *
 * @param {string} url L'URL de l'API.
 * @returns {Promise<object>} Une promesse qui résout avec la réponse JSON de l'API.
 * Rôle : Faciliter les requêtes de suppression de données.
 * Pourquoi préférable : Utilise l'API Fetch pour les requêtes DELETE asynchrones,
 * standard pour les opérations de suppression dans les API RESTful.
 */
async function deleteData(url) {
    const response = await fetch(url, {
        method: 'DELETE',
        credentials: 'same-origin'
    });
    return response.json();
}

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
 */
async function checkAuthAndRedirect() {
    const currentPage = window.location.pathname.split('/').pop();

    try {
        const user = await getData('/api/profile');
        if (user && user.username) {
            // Connecté
            if (currentPage === 'index.php' || currentPage === '') {
                window.location.href = 'taches.php'; // Redirige vers la page des tâches
            }
            else if (currentPage === 'taches.php') {
                document.getElementById('user-pseudo').textContent = user.username;
                fetchTasks(); // Récupère les tâches une fois l'utilisateur authentifié
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
 * - Utilise `postData` pour une communication asynchrone avec l'API.
 * - Redirige l'utilisateur vers la page des tâches en cas de succès.
 */
async function handleLogin(event) {
    event.preventDefault(); // Empêche le rechargement de la page
    const form = event.target;
    const username = form.username.value;
    const password = form.password.value;

    const result = await postData('/api/login', { username, password });

    if (result.message === "Connexion réussie.") {
        window.location.href = 'taches.php';
    } else {
        showAlert(result.message, 'error'); // Affiche un message d'erreur
    }
}

/**
 * Gère la soumission du formulaire d'inscription.
 *
 * @param {Event} event L'événement de soumission du formulaire.
 * Rôle : Envoyer les informations du nouvel utilisateur à l'API pour la création de compte.
 * Pourquoi préférable :
 * - Intercepte la soumission par défaut du formulaire pour gérer l'inscription via AJAX.
 * - Utilise `postData` pour une communication asynchrone avec l'API.
 * - Redirige l'utilisateur vers la page de connexion après une inscription réussie.
 */
async function handleRegister(event) {
    event.preventDefault(); // Empêche le rechargement de la page
    const form = event.target;
    const username = form.username.value;
    const email = form.email.value;
    const password = form.password.value;

    const result = await postData('/api/register', { username, email, password });

    if (result.message === "Compte créé avec succès.") {
        showAlert(result.message, 'success');
        window.location.href = 'index.php'; // Redirige vers la page de connexion
    } else {
        showAlert(result.message, 'error'); // Affiche un message d'erreur
    }
}

/**
 * Gère la déconnexion de l'utilisateur.
 *
 * @param {Event} event L'événement de clic sur le bouton de déconnexion.
 * Rôle : Envoyer une requête à l'API pour terminer la session utilisateur.
 * Pourquoi préférable :
 * - Utilise `getData` pour une communication asynchrone avec l'API.
 * - Redirige l'utilisateur vers la page d'accueil/connexion après la déconnexion.
 */
async function handleLogout() {
    const result = await getData('/api/logout');
    if (result.message === "Déconnexion réussie.") {
        window.location.href = 'index.php';
    } else {
        showAlert(result.message, 'error'); // Affiche un message d'erreur
    }
}

// --- Logique de gestion des tâches ---

/**
 * Récupère les tâches de l'utilisateur depuis l'API, avec des options de filtrage et de tri.
 *
 * Rôle : Charger et afficher la liste des tâches de l'utilisateur.
 * Pourquoi préférable : Permet de filtrer et trier les tâches dynamiquement sans recharger la page.
 * - Utilise `getData` pour une récupération asynchrone des données.
 * - Gère les erreurs de chargement des tâches et affiche un message à l'utilisateur.
 */
async function fetchTasks() {
    const category = document.getElementById('category-filter')?.value || 'all';
    const status = document.getElementById('status-filter')?.value || 'all';
    const sort = document.getElementById('sort-filter')?.value || 'created_at DESC';
    const hideCompleted = document.getElementById('hide-completed-filter')?.checked; // Récupère l'état de la case à cocher

    let url = `/api/tasks?`;
    if (category !== 'all') url += `category=${category}&`;
    if (status !== 'all') url += `status=${status}&`;
    url += `sort=${sort}`;

    const taskListContainer = document.getElementById('task-list'); // Get container here

    try {
        const response = await fetch(url, { credentials: 'same-origin' }); // Get the full response object, with credentials
        const data = await response.json(); // Parse JSON

        if (response.ok) { // Check if response status is 2xx (including 200 OK)
            let tasksToRender = data.tasks; // Récupère le tableau de tâches

            // Si la case est cochée, filtre les tâches terminées
            if (hideCompleted && tasksToRender) {
                tasksToRender = tasksToRender.filter(task => task.status !== 'Terminée');
            }

            if (tasksToRender && tasksToRender.length > 0) {
                renderTasks(tasksToRender); // Affiche les tâches (potentiellement filtrées)
            } else {
                // Affiche un message si aucune tâche ne reste après le filtrage
                taskListContainer.innerHTML = `<h3>${data.message || 'Aucune tâche à afficher selon les filtres actuels.'}</h3>`;
            }
        } else {
            // API returned an error status (e.g., 401, 500)
            taskListContainer.innerHTML = `<h3>${data.message || 'Erreur lors du chargement des tâches.'}</h3>`; // Display API message or generic error
            showAlert(data.message || 'Erreur lors du chargement des tâches.', 'error');
        }
    } catch (error) {
        // Network error or JSON parsing error
        taskListContainer.innerHTML = '<h3>Erreur réseau ou de traitement des données.</h3>'; // Generic error message
        showAlert('Erreur réseau ou de traitement des données.', 'error');
    }
}

/**
 * Rend (affiche) la liste des tâches dans l'interface utilisateur.
 *
 * @param {Array<object>} tasks Un tableau d'objets tâche à afficher.
 * Rôle : Mettre à jour dynamiquement le DOM pour présenter les tâches.
 * Pourquoi préférable : L'utilisation de littéraux de gabarit (template literals)
 * permet d'intégrer facilement des variables et des expressions JavaScript
 * dans une chaîne de caractères HTML, rendant le code plus lisible et maintenable.
 * Les intitulés sont mis en gras avec <strong> pour améliorer la hiérarchie visuelle
 * et la lisibilité des informations détaillées de la tâche.
 */
function renderTasks(tasks) {
    const taskListContainer = document.getElementById('task-list');
    if (!taskListContainer) return;

    taskListContainer.innerHTML = '<h3>Liste des tâches</h3>'; // Efface les tâches précédentes

    tasks.forEach(task => {
        const taskCard = document.createElement('div');
        taskCard.classList.add('task-card');
        taskCard.dataset.taskId = task.id; // Stocke l'ID de la tâche dans un attribut de données

        let statusColor;
        // Détermine la couleur du statut pour l'affichage visuel
        switch (task.status) {
            case 'Prévue': statusColor = 'var(--color-planned)'; break;
            case 'En cours': statusColor = 'var(--color-in-progress)'; break;
            case 'Dépassée': statusColor = 'var(--color-overdue)'; break;
            case 'Terminée': statusColor = 'var(--color-completed)'; break;
            default: statusColor = '#ccc';
        }

        /**
         * Construit le HTML de la carte de tâche.
         * Rôle : Générer la représentation HTML d'une tâche individuelle.
         * Pourquoi préférable : L'utilisation de littéraux de gabarit (template literals)
         * permet d'intégrer facilement des variables et des expressions JavaScript
         * dans une chaîne de caractères HTML, rendant le code plus lisible et maintenable.
         * Les intitulés sont mis en gras avec <strong> pour améliorer la hiérarchie visuelle
         * et la lisibilité des informations détaillées de la tâche.
         */
        taskCard.innerHTML = `
            <div class="task-header">
                <span class="task-status" style="background-color: ${statusColor};"></span>
                <span class="task-name">${task.name} (${task.status})</span>
            </div>
            <div class="task-details" style="display: none;">
                <p><strong>Catégorie:</strong> ${task.category || 'N/A'}</p>
                <p><strong>Statut:</strong> ${task.status}</p>
                <p><strong>Date de début:</strong> ${task.start_date || 'N/A'}</p>
                <p><strong>Date d'échéance:</strong> ${task.due_date || 'N/A'}</p>
                <p><strong>Description:</strong> ${task.description || 'N/A'}</p>
                <p><strong>Créée le:</strong> ${new Date(task.created_at).toLocaleDateString()}</p>
                <button class="blue-gray edit-task-button" data-id="${task.id}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                        <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.121l6.813-6.814z"/>
                        <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
                    </svg>
                </button>
                ${task.status === 'Terminée' ? `
                <button class="blue-gray revert-task-status-button" data-id="${task.id}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-arrow-counterclockwise" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M8 3a5 5 0 1 1-4.546 2.916c.083.603.917.603 1.006.0C4.364 5.012 6.01 3 8 3zm0 1a4 4 0 1 0 0 8 4 4 0 0 0 0-8z"/>
                        <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/>
                    </svg>
                </button>
                ` : `
                <button class="green complete-task-button" data-id="${task.id}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-check-lg" viewBox="0 0 16 16">
                        <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425a.247.247 0 0 1 .02-.022Z"/>
                    </svg>
                </button>
                `}
                <button class="red delete-task-button" data-id="${task.id}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                        <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                    </svg>
                </button>
            </div>
        `;
        taskListContainer.appendChild(taskCard);
    });

    // Attache les écouteurs d'événements aux nouveaux boutons
    document.querySelectorAll('.edit-task-button').forEach(button => {
        button.addEventListener('click', handleEditTask);
    });
    document.querySelectorAll('.complete-task-button').forEach(button => {
        button.addEventListener('click', handleCompleteTask);
    });
    document.querySelectorAll('.delete-task-button').forEach(button => {
        button.addEventListener('click', handleDeleteTask);
    });
    document.querySelectorAll('.revert-task-status-button').forEach(button => {
        button.addEventListener('click', handleRevertTaskStatus);
    });
}

/**
 * Bascule la visibilité des détails d'une tâche (effet accordéon).
 *
 * @param {HTMLElement} taskHeaderElement L'élément d'en-tête de la tâche cliquée.
 * Rôle : Afficher ou masquer les informations détaillées d'une tâche.
 * Pourquoi préférable : Améliore l'expérience utilisateur en permettant de voir
 * plus d'informations sur une tâche sans quitter la page.
 */
function toggleTaskDetails(taskHeaderElement) {
    const taskDetails = taskHeaderElement.nextElementSibling; // Récupère l'élément suivant (les détails)
    if (taskDetails && taskDetails.classList.contains('task-details')) {
        // Bascule le style d'affichage entre 'block' (visible) et 'none' (caché)
        taskDetails.style.display = taskDetails.style.display === 'block' ? 'none' : 'block';
    }
}

// --- Opérations CRUD sur les tâches ---

/**
 * Gère l'affichage et la soumission du formulaire de création de tâche.
 *
 * @param {Event} event L'événement de clic sur le bouton "Modifier".
 * Rôle : Permettre à l'utilisateur de créer une nouvelle tâche via une interface dynamique.
 * Pourquoi préférable : Crée le formulaire dynamiquement dans le DOM, évitant d'avoir une page HTML séparée
 *   pour la création de tâche (bien qu'une page statique existe aussi).
 * - Gère la soumission du formulaire via AJAX (`postData`), offrant une expérience utilisateur fluide.
 * - Intègre la logique de rafraîchissement des tâches après la création.
 */
async function handleCreateTask() {
    hideElement('main-content-area'); // Cache la zone de contenu principale

    // HTML du formulaire de création de tâche
    const taskFormHtml = `
        <h3>Créer une nouvelle tâche</h3>
        <form id="task-form">
            <label for="task-name">Nom de la tâche:</label>
            <input type="text" id="task-name" name="name" required>

            <label for="task-category">Catégorie:</label>
            <select id="task-category" name="category">
                <option value="">Sélectionner une catégorie</option>
                <option value="Travail">Travail</option>
                <option value="Bricolage">Bricolage</option>
                <option value="Loisirs">Loisirs</option>
            </select>

            <label for="task-start-date">Date de début:</label>
            <input type="date" id="task-start-date" name="start_date">

            <label for="task-due-date">Date d'échéance:</label>
            <input type="date" id="task-due-date" name="due_date">

            <label for="task-description">Description:</label>
            <textarea id="task-description" name="description" rows="3"></textarea>

            <button type="submit" class="green">Ajouter la tâche</button>
            <button type="button" class="blue-gray" id="cancel-task-form">Annuler</button>
        </form>
    `;
    const mainContainer = document.querySelector('main.container');
    mainContainer.insertAdjacentHTML('afterbegin', taskFormHtml); // Insère le formulaire au début du conteneur principal

    // Écouteur d'événement pour la soumission du formulaire de création
    document.getElementById('task-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.target;
        const newTask = {
            name: form.name.value,
            category: form.category.value || null,
            start_date: form.start_date.value || null,
            due_date: form.due_date.value || null,
            description: form.description.value || null,
        };
        const result = await postData('/api/tasks', newTask); // Envoie les données à l'API
        showAlert(result.message, result.message.includes('succès') ? 'success' : 'error');
        if (result.message.includes('succès')) {
            document.getElementById('task-form').remove(); // Supprime le formulaire après succès
            showElement('main-content-area'); // Réaffiche la zone de contenu principale
            fetchTasks(); // Rafraîchit la liste des tâches
        }
    });

    document.getElementById('cancel-task-form').addEventListener('click', () => {
        document.getElementById('task-form').remove(); // Supprime le formulaire
        showElement('main-content-area'); // Réaffiche la zone de contenu principale
    });
}

/**
 * Gère l'affichage et la soumission du formulaire de modification de tâche.
 *
 * @param {Event} event L'événement de clic sur le bouton "Modifier".
 * Rôle : Permettre à l'utilisateur de modifier une tâche existante.
 * Pourquoi préférable :
 * - Récupère les détails de la tâche à modifier pour pré-remplir le formulaire,
 *   améliorant l'expérience utilisateur.
 * - Gère la soumission du formulaire via AJAX (`putData`), offrant une expérience fluide.
 * - Intègre la logique de rafraîchissement des tâches après la modification.
 * - **Amélioration future :** Au lieu de re-récupérer toutes les tâches (`getData('/api/tasks')`)
 *   pour trouver la tâche à modifier, il serait plus efficace d'avoir un endpoint API
 *   pour récupérer une seule tâche par ID (ex: `/api/tasks/{id}`).
 */
async function handleEditTask(event) {
    const button = event.target.closest('.edit-task-button');
    const taskId = button ? button.dataset.id : undefined;
    if (!taskId) return; // Quitte si pas d'ID

    hideElement('main-content-area'); // Masque la liste des tâches

    try {
        // Récupère toutes les tâches pour trouver celle à modifier
        const { tasks } = await getData('/api/tasks');
        const taskToEdit = tasks.find(task => task.id == taskId);

        if (!taskToEdit) {
            showAlert('Tâche non trouvée.', 'error');
            showElement('main-content-area'); // Réaffiche la liste si la tâche n'est pas trouvée
            return;
        }

        // HTML du formulaire de modification de tâche, pré-rempli avec les données existantes
        const editFormHtml = `
            <div id="edit-task-form-container">
                <h3>Modifier la tâche</h3>
                <form id="edit-task-form">
                    <input type="hidden" name="id" value="${taskToEdit.id}">
                    <label for="edit-task-name">Nom de la tâche:</label>
                    <input type="text" id="edit-task-name" name="name" value="${taskToEdit.name}" required>

                    <label for="edit-task-category">Catégorie:</label>
                    <select id="edit-task-category" name="category">
                        <option value="">Sélectionner une catégorie</option>
                        <option value="Travail" ${taskToEdit.category === 'Travail' ? 'selected' : ''}>Travail</option>
                        <option value="Bricolage" ${taskToEdit.category === 'Bricolage' ? 'selected' : ''}>Bricolage</option>
                        <option value="Loisirs" ${taskToEdit.category === 'Loisirs' ? 'selected' : ''}>Loisirs</option>
                    </select>

                    <label for="edit-task-start-date">Date de début:</label>
                    <input type="date" id="edit-task-start-date" name="start_date" value="${taskToEdit.start_date || ''}">

                    <label for="edit-task-due-date">Date d'échéance:</label>
                    <input type="date" id="edit-task-due-date" name="due_date" value="${taskToEdit.due_date || ''}">

                    <label for="edit-task-description">Description:</label>
                    <textarea id="edit-task-description" name="description" rows="3">${taskToEdit.description || ''}</textarea>

                    <button type="submit" class="green">Mettre à jour</button>
                    <button type="button" class="blue-gray" id="cancel-edit-task-form">Annuler</button>
                </form>
            </div>
        `;
        const mainContainer = document.querySelector('main.container');
        mainContainer.insertAdjacentHTML('afterbegin', editFormHtml);

        // Écouteur d'événement pour la soumission du formulaire de modification
        document.getElementById('edit-task-form').addEventListener('submit', async (event) => {
            event.preventDefault();
            const form = event.target;
            const updatedTask = {
                id: form.id.value,
                name: form.name.value,
                category: form.category.value || null,
                start_date: form.start_date.value || null,
                due_date: form.due_date.value || null,
                description: form.description.value || null,
            };
            try { // Add try-catch around putData
                const result = await putData('/api/tasks', updatedTask); // Envoie les données à l'API
                if (result && result.message && result.message.includes('succès')) { // Check for result and message
                    document.getElementById('edit-task-form-container').remove();
                    showElement('main-content-area'); // Réaffiche la liste
                    fetchTasks(); // Rafraîchit la liste des tâches
                } else {
                    showAlert(result.message || "Erreur inconnue lors de la mise à jour.", 'error'); // Show error
                }
            } catch (error) {
                showAlert("Erreur réseau ou serveur lors de la mise à jour.", 'error'); // Show network error
            }
        });

        // Écouteur d'événement pour le bouton d'annulation
        document.getElementById('cancel-edit-task-form').addEventListener('click', () => {
            document.getElementById('edit-task-form-container').remove();
            showElement('main-content-area'); // Réaffiche la liste
        });

    } catch (error) {
        showAlert('Erreur lors du chargement de la tâche pour modification.', 'error');
        showElement('main-content-area'); // Réaffiche la liste en cas d'erreur
    }
}

/**
 * Gère l'action de marquer une tâche comme "Terminée".
 *
 * @param {Event} event L'événement de clic sur le bouton "Terminer".
 * Rôle : Mettre à jour le statut d'une tâche à "Terminée" via l'API.
 * Pourquoi préférable :
 * - Permet une interaction rapide pour changer le statut d'une tâche.
 * - Utilise `putData` pour une mise à jour asynchrone.
 * - Rafraîchit la liste des tâches pour refléter le changement.
 */
async function handleCompleteTask(event) {
    const button = event.target.closest('.complete-task-button');
    const taskId = button ? button.dataset.id : undefined;

    try {
        // Récupère les détails de la tâche pour s'assurer d'avoir toutes les données avant la mise à jour
        const { tasks } = await getData('/api/tasks');
        const taskToComplete = tasks.find(task => task.id == taskId);

        if (!taskToComplete) {
            showAlert('Tâche non trouvée.', 'error');
            return;
        }

        taskToComplete.status = 'Terminée'; // Définit le statut à "Terminée"

        const result = await putData('/api/tasks', taskToComplete); // Envoie la mise à jour à l'API
        showAlert(result.message, result.message.includes('succès') ? 'success' : 'error');
        if (result.message.includes('succès')) {
            fetchTasks();
        }
    }
    catch (error) {
        showAlert('Erreur lors de la mise à jour de la tâche.', 'error');
    }
}

/**
 * Gère l'action de suppression d'une tâche.
 *
 * @param {Event} event L'événement de clic sur le bouton "Supprimer".
 * Rôle : Supprimer une tâche de la base de données via l'API.
 * Pourquoi préférable :
 * - Demande une confirmation à l'utilisateur pour éviter les suppressions accidentelles.
 * - Utilise `deleteData` pour une suppression asynchrone.
 * - Rafraîchit la liste des tâches après la suppression.
 */
async function handleDeleteTask(event) {
    const button = event.target.closest('.delete-task-button');
    const taskId = button ? button.dataset.id : undefined;

    const confirmDelete = confirm("Voulez-vous vraiment supprimer cette tâche ?");
    if (confirmDelete) {
        const result = await deleteData(`/api/tasks?id=${taskId}`); // Envoie la requête de suppression à l'API
        showAlert(result.message, result.message.includes('succès') ? 'success' : 'error');
        if (result.message.includes('succès')) {
            fetchTasks();
        }
    }
}

/**
 * Gère l'action de revenir sur le statut d'une tâche "Terminée".
 * Calcule le nouveau statut (Prévue, En cours, Dépassée) basé sur les dates.
 *
 * @param {Event} event L'événement de clic sur le bouton "Annuler le statut".
 * Rôle : Permettre à l'utilisateur de réactiver une tâche terminée.
 * Pourquoi préférable : Offre une flexibilité pour corriger les erreurs ou réouvrir des tâches.
 */
async function handleRevertTaskStatus(event) {
    const button = event.target.closest('.revert-task-status-button');
    const taskId = button ? button.dataset.id : undefined;

    try {
        const { tasks } = await getData('/api/tasks');
        const taskToRevert = tasks.find(task => task.id == taskId);

        if (!taskToRevert) {
            showAlert('Tâche non trouvée.', 'error');
            return;
        }

        let newStatus = 'En cours'; // Default status

        const today = new Date();
        today.setHours(0, 0, 0, 0); // Normalize to start of day

        const startDate = taskToRevert.start_date ? new Date(taskToRevert.start_date) : null;
        const dueDate = taskToRevert.due_date ? new Date(taskToRevert.due_date) : null;

        if (startDate && startDate > today) {
            newStatus = 'Prévue';
        } else if (dueDate && dueDate < today) {
            newStatus = 'Dépassée';
        } else {
            newStatus = 'En cours';
        }

        taskToRevert.status = newStatus;

        const result = await putData('/api/tasks', taskToRevert);
        showAlert(result.message, result.message.includes('succès') ? 'success' : 'error');
        if (result.message.includes('succès')) {
            fetchTasks();
        }
    } catch (error) {
        showAlert('Erreur lors de l\'annulation du statut de la tâche.', 'error');
    }
}

// --- Logique de gestion du profil ---

/**
 * Récupère et affiche les informations du profil de l'utilisateur.
 *
 * Rôle : Charger les données du profil de l'utilisateur connecté et les afficher sur la page de profil.
 * Pourquoi préférable : Utilise `getData` pour récupérer les informations du profil de manière asynchrone.
 * - Met à jour dynamiquement les éléments HTML avec les données du profil.
 */
async function fetchProfile() {
    try {
        const user = await getData('/api/profile');
        if (user && user.username) {
            document.getElementById('profile-username').textContent = user.username;
            document.getElementById('profile-email').textContent = user.email;
        } else {
            showAlert('Impossible de charger le profil.', 'error');
        }
    }
    catch (error) {
        showAlert('Erreur lors du chargement du profil.', 'error');
    }
}

/**
 * Gère la soumission du formulaire de mise à jour du profil (principalement le mot de passe).
 *
 * @param {Event} event L'événement de soumission du formulaire.
 * Rôle : Permettre à l'utilisateur de modifier son mot de passe.
 * Pourquoi préférable :
 * - Valide la correspondance des nouveaux mots de passe.
 * - Utilise `putData` pour envoyer les données de mise à jour à l'API de manière asynchrone.
 * - Cache le formulaire de changement de mot de passe après une mise à jour réussie.
 * - **Note :** Actuellement, seuls les champs de mot de passe sont gérés pour la mise à jour.
 *   Pour permettre la modification du nom d'utilisateur ou de l'email, des champs de saisie
 *   devraient être ajoutés au HTML et leur valeur inclus dans `profileData`.
 */
async function handleUpdateProfile(event) {
    event.preventDefault();
    const form = event.target;
    const currentPassword = form.elements['current-password']?.value; // Non utilisé côté client pour la vérification ici
    const newPassword = form.elements['new-password']?.value;
    const confirmNewPassword = form.elements['confirm-new-password']?.value;

    if (newPassword && newPassword !== confirmNewPassword) {
        showAlert('Les nouveaux mots de passe ne correspondent pas.', 'error');
        return;

    }

    // Les champs username et email sont récupérés du texte affiché, non éditables directement ici.
    const profileData = {
        username: document.getElementById('profile-username').textContent,
        email: document.getElementById('profile-email').textContent,
    };

    if (newPassword) {
        profileData.password = newPassword;
        // Dans une application réelle, le mot de passe actuel serait envoyé pour vérification côté serveur.
    }

    const result = await putData('/api/profile', profileData);
    showAlert(result.message, result.message.includes('succès') ? 'success' : 'error');
    if (result.message.includes('succès')) {
        hideElement('password-change-form');
        fetchProfile(); // Rafraîchit le profil pour afficher les données mises à jour
    }
}

/**
 * Gère la suppression du compte de l'utilisateur.
 *
 * @param {Event} event L'événement de clic sur le bouton "Supprimer".
 * Rôle : Permettre à l'utilisateur de supprimer définitivement son compte.
 * Pourquoi préférable :
 * - Demande une confirmation explicite pour éviter les suppressions accidentelles.
 * - Utilise `deleteData` pour envoyer la requête de suppression à l'API.
 * - Redirige l'utilisateur vers la page d'accueil/connexion après la suppression réussie.
 */
async function handleDeleteAccount() {
    const confirmDelete = confirm("Voulez-vous vraiment supprimer votre compte ? Cette action est irréversible.");
    if (confirmDelete) {
        const result = await deleteData('/api/profile');
        showAlert(result.message, result.message.includes('succès') ? 'success' : 'error');
        if (result.message.includes('succès')) {
            window.location.href = 'index.php'; // Redirige vers la page d'accueil après suppression
        }
    }
}

// --- Écouteurs d'événements ---

/**
 * Attache les écouteurs d'événements une fois que le DOM est entièrement chargé.
 *
 * Rôle : Initialiser toutes les interactions JavaScript de la page.
 * Pourquoi préférable :
 * - `DOMContentLoaded` garantit que le script s'exécute après que tout le HTML
 *   a été analysé et que tous les éléments sont disponibles dans le DOM.
 * - Organise les écouteurs par section (connexion/inscription, tâches, profil)
 *   pour une meilleure lisibilité et maintenabilité.
 */
document.addEventListener('DOMContentLoaded', () => {
    checkAuthAndRedirect(); // Vérifie l'authentification au chargement de la page

    // Écouteurs spécifiques à la page d'accueil (index.php)
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    if (document.getElementById('login-button')) {
        document.getElementById('login-button').addEventListener('click', () => {
            if (loginForm) {
                loginForm.style.display = 'block';
                if (registerForm) registerForm.style.display = 'none';
            }
        });
    }

    if (document.getElementById('register-button')) {
        document.getElementById('register-button').addEventListener('click', () => {
            if (registerForm) {
                registerForm.style.display = 'block';
                if (loginForm) loginForm.style.display = 'none';
            }
        });
    }

    // Attache les écouteurs de soumission une seule fois
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }

    // Écouteurs spécifiques à la page des tâches (taches.php)
    if (document.getElementById('logout-button')) {
        document.getElementById('logout-button').addEventListener('click', handleLogout);
    }

    if (document.getElementById('menu-button')) {
        document.getElementById('menu-button').addEventListener('click', () => {
            const menu = document.getElementById('header-menu');
            menu.classList.toggle('menu-open'); // Bascule la classe pour ouvrir/fermer le menu
        });
    }

    if (document.getElementById('create-task-button')) {
        document.getElementById('create-task-button').addEventListener('click', handleCreateTask);
    }

    // Ajoute un écouteur d'événement pour l'accordéon des tâches (délégation pour les tâches ajoutées dynamiquement)
    document.getElementById('task-list')?.addEventListener('click', (event) => {
        const taskHeaderElement = event.target.closest('.task-header');
        if (taskHeaderElement) {
            toggleTaskDetails(taskHeaderElement); // Passe l'élément directement
        } else {
            // Clic en dehors de l'en-tête de tâche
        }
    });

    // Écouteurs pour les filtres et le tri
    document.getElementById('category-filter')?.addEventListener('change', fetchTasks);
    document.getElementById('status-filter')?.addEventListener('change', fetchTasks);
    document.getElementById('sort-filter')?.addEventListener('change', fetchTasks);
    document.getElementById('hide-completed-filter')?.addEventListener('change', fetchTasks);

    // Écouteurs spécifiques à la page de profil (profil.php)
    if (window.location.pathname.split('/').pop() === 'profil.php') {
        fetchProfile(); // Récupère les données du profil au chargement de la page
    }

    if (document.getElementById('change-password-button')) {
        document.getElementById('change-password-button').addEventListener('click', () => {
            showElement('password-change-form');
        });
    }

    if (document.getElementById('cancel-password-change')) {
        document.getElementById('cancel-password-change').addEventListener('click', () => {
            hideElement('password-change-form');
        });
    }

    if (document.getElementById('password-change-form')) {
        document.getElementById('password-change-form').addEventListener('submit', handleUpdateProfile);
    }

    if (document.getElementById('delete-account-button')) {
        document.getElementById('delete-account-button').addEventListener('click', handleDeleteAccount);
    }
});