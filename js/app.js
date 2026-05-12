/**
 * js/app.js
 *
 * Rôle :
 * - Gérer la page d’accueil : affichage connexion / inscription
 * - Gérer l’authentification : login / register / logout
 * - Initialiser la page taches.php
 * - Initialiser la page creer_tache.php
 * - Charger et afficher les tâches
 * - Gérer le menu utilisateur
 * - Supprimer une tâche avec confirmation stylée
 * - Créer une tâche via la page dédiée
 * - Gérer le token CSRF
 *
 * Principe :
 * - Le HTML fournit la structure.
 * - Le JavaScript gère les interactions utilisateur et les appels API.
 * - La validation serveur reste obligatoire et prioritaire.
 */

/* ========================
   CSRF
   ======================== */

let CSRF_TOKEN = null;

/* ========================
   VARIABLES GLOBALES UI
   ======================== */

let loginButton = null;
let registerButton = null;

/* ========================
   API FETCH
   ======================== */

/**
 * Centralise les appels à l’API.
 *
 * Rôle :
 * - Ajouter les headers JSON.
 * - Envoyer les cookies de session.
 * - Ajouter le token CSRF sur les requêtes mutatives protégées.
 * - Lire les réponses JSON.
 * - Faire remonter les messages d’erreur API.
 */
async function apiFetch(url, options = {}) {
    const method = options.method || 'GET';

    const headers = {
        'Content-Type': 'application/json',
        ...(options.headers || {})
    };

    /*
        La protection CSRF est volontairement désactivée pour les routes
        de login et d’inscription, car aucun token ne peut exister avant
        l’ouverture de session. Toutes les autres requêtes mutatives
        restent protégées.
    */
    if (['POST', 'PUT', 'DELETE'].includes(method)) {
        const exempt = ['/api/login', '/api/register'];

        if (!exempt.includes(url)) {
            if (!CSRF_TOKEN) {
                throw new Error('CSRF token manquant');
            }

            headers['X-CSRF-Token'] = CSRF_TOKEN;
        }
    }

    const response = await fetch(url, {
        credentials: 'same-origin',
        headers,
        ...options
    });

    const text = await response.text();

    if (!text) {
        throw new Error(`Réponse serveur vide (${response.status})`);
    }

    let data;

    try {
        data = JSON.parse(text);
    } catch {
        throw new Error('Réponse serveur invalide');
    }

    if (!response.ok) {
        throw new Error(data.message || `Erreur serveur ${response.status}`);
    }

    if (!data.success) {
        throw new Error(data.message || 'Erreur API');
    }

    return data;
}

/* ========================
   PAGE ACCUEIL : LOGIN / REGISTER
   ======================== */

/**
 * Affiche le formulaire de connexion et masque le formulaire d’inscription.
 */
function showLoginForm() {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    if (loginForm && registerForm) {
        loginForm.style.display = 'block';
        registerForm.style.display = 'none';
    }
}

/**
 * Affiche le formulaire d’inscription et masque le formulaire de connexion.
 */
function showRegisterForm() {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    if (loginForm && registerForm) {
        registerForm.style.display = 'block';
        loginForm.style.display = 'none';
    }
}

/**
 * Connexion utilisateur.
 *
 * Rôle :
 * - Envoyer les identifiants à POST /api/login.
 * - Laisser le serveur créer la session.
 * - Rediriger vers taches.php après succès.
 */
async function login(event) {
    event.preventDefault();

    const form = event.target;

    try {
        await apiFetch('/api/login', {
            method: 'POST',
            body: JSON.stringify({
                username: form.username.value,
                password: form.password.value
            })
        });

        window.location.href = 'taches.php';
    } catch (error) {
        alert(error.message);
    }
}

/**
 * Inscription utilisateur.
 *
 * Rôle :
 * - Envoyer les données à POST /api/register.
 * - Réinitialiser le formulaire après succès.
 * - Revenir au formulaire de connexion sans popup de succès.
 */
async function register(event) {
    event.preventDefault();

    const form = event.target;

    try {
        await apiFetch('/api/register', {
            method: 'POST',
            body: JSON.stringify({
                username: form.username.value,
                email: form.email.value,
                password: form.password.value
            })
        });

        form.reset();
        showLoginForm();
    } catch (error) {
        alert(error.message);
    }
}

/**
 * Initialise la page d’accueil.
 *
 * Rôle :
 * - Brancher les boutons "Se connecter" et "Créer un compte".
 * - Brancher les formulaires login / register.
 */
function initHomePage() {
    loginButton = document.getElementById('login-button');
    registerButton = document.getElementById('register-button');

    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    if (loginButton) {
        loginButton.addEventListener('click', showLoginForm);
    }

    if (registerButton) {
        registerButton.addEventListener('click', showRegisterForm);
    }

    if (loginForm) {
        loginForm.addEventListener('submit', login);
    }

    if (registerForm) {
        registerForm.addEventListener('submit', register);
    }
}

/* ========================
   AUTH : LOGOUT
   ======================== */

/**
 * Déconnecte l’utilisateur.
 *
 * Rôle :
 * - Appeler POST /api/logout.
 * - Laisser le serveur invalider la session.
 * - Rediriger vers la page d’accueil.
 */
async function logout(event) {
    event.preventDefault();

    try {
        await apiFetch('/api/logout', { method: 'POST' });
        window.location.href = 'index.php';
    } catch (error) {
        alert(error.message);
    }
}

/* ========================
   PROFIL / SESSION
   ======================== */

/**
 * Charge le profil utilisateur.
 *
 * Rôle :
 * - Vérifier que l’utilisateur est authentifié.
 * - Récupérer le token CSRF.
 * - Mettre à jour le pseudo si l’élément existe dans la page.
 *
 * Utilisé par :
 * - taches.php
 * - creer_tache.php
 */
async function loadProfile() {
    const res = await apiFetch('/api/profile');

    CSRF_TOKEN = res.data.csrf;

    const pseudo = document.getElementById('user-pseudo');

    if (pseudo) {
        pseudo.textContent = res.data.username;
    }

    return res.data;
}

/* ========================
   PAGE TÂCHES : CHARGEMENT
   ======================== */

/**
 * Charge les tâches de l’utilisateur connecté.
 *
 * Rôle :
 * - Lire les filtres sélectionnés.
 * - Interroger GET /api/tasks.
 * - Déléguer l’affichage à renderTasks().
 */
async function loadTasks() {
    const list = document.getElementById('task-list');

    if (!list) return;

    const category = document.getElementById('category-filter')?.value ?? 'all';
    const status = document.getElementById('status-filter')?.value ?? 'all';
    const sort = document.getElementById('sort-filter')?.value ?? 'created_at DESC';

    const params = new URLSearchParams({ category, status, sort });
    const res = await apiFetch(`/api/tasks?${params.toString()}`);

    renderTasks(res.data);
}

/**
 * Formate une date technique au format utilisateur français.
 *
 * Entrée attendue :
 * - YYYY-MM-DD
 *
 * Sortie attendue :
 * - 13 mai 2026
 *
 * Important :
 * - La base de données et l’API conservent le format technique YYYY-MM-DD.
 * - Cette fonction ne sert qu’à l’affichage utilisateur.
 * - La date est construite manuellement pour éviter les décalages liés aux fuseaux horaires.
 */
function formatDisplayDate(value) {
    if (!value) {
        return '—';
    }

    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);

    if (!match) {
        return value;
    }

    const year = Number(match[1]);
    const month = Number(match[2]);
    const day = Number(match[3]);

    const monthNames = [
        'janvier',
        'février',
        'mars',
        'avril',
        'mai',
        'juin',
        'juillet',
        'août',
        'septembre',
        'octobre',
        'novembre',
        'décembre'
    ];

    if (month < 1 || month > 12 || day < 1 || day > 31) {
        return value;
    }

    return `${day} ${monthNames[month - 1]} ${year}`;
}

/**
 * Crée un paragraphe de détail pour une tâche.
 *
 * Rôle :
 * - Éviter d’injecter directement du texte utilisateur dans innerHTML.
 * - Afficher la description comme texte simple.
 */
function createDetailParagraph(label, value) {
    const paragraph = document.createElement('p');

    if (label) {
        const strong = document.createElement('strong');
        strong.textContent = `${label} : `;
        paragraph.appendChild(strong);
    }

    paragraph.appendChild(document.createTextNode(value ?? '—'));

    return paragraph;
}

/**
 * Affiche les tâches dans la section #task-list.
 *
 * Rôle :
 * - Générer une structure d’accordéon accessible.
 * - Afficher les détails de la tâche seulement à l’ouverture.
 * - Ajouter le bouton de suppression dans la zone dépliée.
 */
function renderTasks(tasks) {
    const list = document.getElementById('task-list');

    if (!list) return;

    list.innerHTML = '<h3 id="task-list-title">Liste des tâches</h3>';

    if (!tasks.length) {
        list.insertAdjacentHTML(
            'beforeend',
            `<p class="empty-state">
                Aucune tâche pour l’instant.
                Utilisez « Créer une tâche » pour commencer.
             </p>`
        );
        return;
    }

    tasks.forEach(task => {
        const id = `task-${task.id}`;

        const article = document.createElement('article');
        article.className = 'task-item';

        const header = document.createElement('button');
        header.className = 'task-header';
        header.id = `${id}-header`;
        header.type = 'button';
        header.setAttribute('aria-expanded', 'false');
        header.setAttribute('aria-controls', `${id}-details`);
        header.textContent = `${task.name} (${task.status})`;

        const details = document.createElement('div');
        details.className = 'task-details';
        details.id = `${id}-details`;
        details.setAttribute('role', 'region');
        details.setAttribute('aria-labelledby', `${id}-header`);
        details.hidden = true;

        details.appendChild(createDetailParagraph(null, task.description || 'Aucune description.'));
        details.appendChild(createDetailParagraph('Catégorie', task.category));
        details.appendChild(createDetailParagraph('Début', formatDisplayDate(task.start_date)));
        details.appendChild(createDetailParagraph('Échéance', formatDisplayDate(task.due_date)));

        const deleteButton = document.createElement('button');
        deleteButton.type = 'button';
        deleteButton.className = 'delete-task-button';
        deleteButton.setAttribute('aria-label', 'Supprimer la tâche');
        deleteButton.setAttribute('title', 'Supprimer');
        deleteButton.dataset.taskId = String(task.id);
        deleteButton.textContent = '🗑';

        deleteButton.addEventListener('click', () => openDeleteConfirm(task.id));

        details.appendChild(deleteButton);

        header.addEventListener('click', () => {
            const open = header.getAttribute('aria-expanded') === 'true';
            header.setAttribute('aria-expanded', String(!open));
            details.hidden = open;
        });

        article.appendChild(header);
        article.appendChild(details);
        list.appendChild(article);
    });
}

/* ========================
   POPUP SUPPRESSION
   ======================== */

/**
 * Ouvre une confirmation stylée avant suppression.
 *
 * Rôle :
 * - Éviter une suppression accidentelle.
 * - Laisser l’utilisateur confirmer ou annuler.
 */
function openDeleteConfirm(taskId) {
    const overlay = document.createElement('div');
    overlay.className = 'confirm-overlay';

    overlay.innerHTML = `
        <div class="confirm-box" role="dialog" aria-modal="true">
            <p>Supprimer ?</p>
            <div class="confirm-actions">
                <button type="button" class="confirm-yes red">Oui</button>
                <button type="button" class="confirm-no blue-gray">Non</button>
            </div>
        </div>
    `;

    document.body.appendChild(overlay);

    overlay.querySelector('.confirm-no').addEventListener('click', () => {
        overlay.remove();
    });

    overlay.querySelector('.confirm-yes').addEventListener('click', async () => {
        try {
            await apiFetch('/api/tasks', {
                method: 'DELETE',
                body: JSON.stringify({ id: taskId })
            });

            overlay.remove();
            loadTasks();
        } catch (error) {
            overlay.remove();
            alert(error.message);
        }
    });
}

/* ========================
   MENU
   ======================== */

/**
 * Initialise le menu utilisateur.
 *
 * Rôle :
 * - Ouvrir / fermer le menu.
 * - Fermer le menu au clic extérieur.
 * - Mettre à jour aria-expanded.
 */
function initMenu() {
    const menuButton = document.getElementById('menu-button');
    const menuNav = document.getElementById('header-menu');
    const container = document.getElementById('user-info-and-menu');

    if (!menuButton || !menuNav || !container) return;

    menuNav.hidden = true;
    menuButton.setAttribute('aria-expanded', 'false');

    const closeMenu = () => {
        menuNav.hidden = true;
        menuButton.setAttribute('aria-expanded', 'false');
    };

    const openMenu = () => {
        menuNav.hidden = false;
        menuButton.setAttribute('aria-expanded', 'true');
    };

    menuButton.addEventListener('click', event => {
        event.stopPropagation();

        const open = menuButton.getAttribute('aria-expanded') === 'true';
        open ? closeMenu() : openMenu();
    });

    document.addEventListener('click', event => {
        if (!container.contains(event.target)) {
            closeMenu();
        }
    });

    menuNav.querySelectorAll('a').forEach(link =>
        link.addEventListener('click', closeMenu)
    );
}

/* ========================
   PAGE CRÉATION TÂCHE
   ======================== */

/**
 * Vérifie qu’une chaîne respecte strictement le format HTML date.
 *
 * Format attendu :
 * YYYY-MM-DD
 *
 * Important :
 * - Ne pas utiliser toISOString() ici.
 * - toISOString() convertit la date en UTC et peut décaler la date
 *   selon le fuseau horaire du navigateur.
 * - On valide donc manuellement année / mois / jour.
 */
function isValidHtmlDate(value) {
    if (!value) return false;

    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);

    if (!match) {
        return false;
    }

    const year = Number(match[1]);
    const month = Number(match[2]);
    const day = Number(match[3]);

    if (month < 1 || month > 12) {
        return false;
    }

    if (day < 1 || day > 31) {
        return false;
    }

    const date = new Date(year, month - 1, day);

    return date.getFullYear() === year
        && date.getMonth() === month - 1
        && date.getDate() === day;
}

/**
 * Affiche un message d’erreur sur la page de création.
 */
function showCreateTaskError(message) {
    const messageBox = document.getElementById('create-task-message');

    if (messageBox) {
        messageBox.textContent = message;
    }
}

/**
 * Efface le message d’erreur de la page de création.
 */
function clearCreateTaskError() {
    const messageBox = document.getElementById('create-task-message');

    if (messageBox) {
        messageBox.textContent = '';
    }
}

/**
 * Valide côté client les données du formulaire de création.
 *
 * Rôle :
 * - Donner un retour rapide à l’utilisateur.
 * - Éviter les appels API inutiles.
 *
 * Important :
 * - Cette validation ne remplace jamais la validation serveur.
 */
function validateCreateTaskForm(form) {
    const name = form.name.value.trim();
    const category = form.category.value;
    const startDate = form.start_date.value;
    const dueDate = form.due_date.value;
    const description = form.description.value.trim();

    const allowedCategories = ['Travail', 'Bricolage', 'Loisirs'];

    if (name.length < 3) {
        return 'Le nom de la tâche doit contenir au moins 3 caractères.';
    }

    if (name.length > 50) {
        return 'Le nom de la tâche ne doit pas dépasser 50 caractères.';
    }

    if (!allowedCategories.includes(category)) {
        return 'Veuillez sélectionner une catégorie valide.';
    }

    if (!dueDate) {
        return 'La date d’échéance est obligatoire.';
    }

    if (!isValidHtmlDate(dueDate)) {
        return 'La date d’échéance est invalide.';
    }

    if (startDate && !isValidHtmlDate(startDate)) {
        return 'La date de début est invalide.';
    }

    if (startDate && dueDate && startDate > dueDate) {
        return 'La date de début ne peut pas être postérieure à la date d’échéance.';
    }

    if (description.length > 500) {
        return 'La description ne doit pas dépasser 500 caractères.';
    }

    return null;
}

/**
 * Soumet le formulaire de création de tâche.
 *
 * Rôle :
 * - Valider côté client.
 * - Envoyer POST /api/tasks.
 * - Rediriger vers taches.php après succès.
 */
async function submitCreateTask(event) {
    event.preventDefault();

    const form = event.target;
    clearCreateTaskError();

    const error = validateCreateTaskForm(form);

    if (error) {
        showCreateTaskError(error);
        return;
    }

    const payload = {
        name: form.name.value.trim(),
        category: form.category.value,
        start_date: form.start_date.value || null,
        due_date: form.due_date.value,
        description: form.description.value.trim()
    };

    try {
        await apiFetch('/api/tasks', {
            method: 'POST',
            body: JSON.stringify(payload)
        });

        window.location.href = 'taches.php';
    } catch (apiError) {
        showCreateTaskError(apiError.message);
    }
}

/**
 * Initialise la page creer_tache.php.
 *
 * Rôle :
 * - Vérifier que l’utilisateur est connecté.
 * - Récupérer le token CSRF.
 * - Brancher le formulaire.
 */
async function initCreateTaskPage() {
    try {
        await loadProfile();
    } catch {
        window.location.href = 'index.php';
        return;
    }

    const form = document.getElementById('create-task-form');

    if (form) {
        form.addEventListener('submit', submitCreateTask);
    }
}

/* ========================
   INIT
   ======================== */

document.addEventListener('DOMContentLoaded', () => {
    /*
        Initialisation de la page d’accueil.
        Le marqueur utilisé est la présence des boutons login/register.
    */
    if (
        document.getElementById('login-button') ||
        document.getElementById('register-button') ||
        document.getElementById('login-form') ||
        document.getElementById('register-form')
    ) {
        initHomePage();
    }

    /*
        Initialisation de taches.php.
        Le marqueur utilisé est #task-list, spécifique à la page de liste.
    */
    if (document.getElementById('task-list')) {
        loadProfile().then(() => {
            initMenu();
            loadTasks();
        }).catch(() => {
            window.location.href = 'index.php';
        });

        document.getElementById('logout-button')
            ?.addEventListener('click', logout);

        document.getElementById('category-filter')
            ?.addEventListener('change', loadTasks);

        document.getElementById('status-filter')
            ?.addEventListener('change', loadTasks);

        document.getElementById('sort-filter')
            ?.addEventListener('change', loadTasks);
    }

    /*
        Initialisation de creer_tache.php.
        Le marqueur utilisé est #create-task-form, spécifique à la page de création.
    */
    if (document.getElementById('create-task-form')) {
        initCreateTaskPage();
    }
});