/**
 * js/app.js
 *
 * Rôle :
 * - Gérer les appels API (avec CSRF)
 * - Orchestrer l’affichage des tâches
 * - Implémenter l’accordéon des tâches (UX + accessibilité)
 *
 * Principes stricts :
 * - Le HTML définit la structure (voir taches.php)
 * - Le JS NE DEVINE RIEN : il peuple et anime seulement
 * - Accessibilité clavier et ARIA obligatoires
 */

/* ========================
   CSRF
   ======================== */

let CSRF_TOKEN = null;

/* ========================
   CLIENT API UNIFIÉ
   ======================== */

async function apiFetch(url, options = {}) {
    const method = options.method || 'GET';

    const headers = {
        'Content-Type': 'application/json',
        ...(options.headers || {})
    };

    // Injection automatique du token CSRF pour les mutations
    if (['POST', 'PUT', 'DELETE'].includes(method)) {
        if (!CSRF_TOKEN) {
            throw new Error('CSRF token manquant');
        }
        headers['X-CSRF-Token'] = CSRF_TOKEN;
    }

    const response = await fetch(url, {
        credentials: 'same-origin',
        headers,
        ...options
    });

    const data = await response.json();

    if (!response.ok) {
        if (response.status === 401) {
            window.location.href = 'index.php';
        }
        throw new Error(data.message || 'Erreur API');
    }

    return data;
}

/* ========================
   AUTHENTIFICATION
   ======================== */

async function login(event) {
    event.preventDefault();
    const form = event.target;

    await apiFetch('/api/login', {
        method: 'POST',
        body: JSON.stringify({
            username: form.username.value,
            password: form.password.value
        })
    });

    window.location.href = 'taches.php';
}

async function logout() {
    await apiFetch('/api/logout', { method: 'POST' });
    window.location.href = 'index.php';
}

/* ========================
   CHARGEMENT DU PROFIL (CSRF)
   ======================== */

/**
 * Rôle :
 * - Récupérer les infos utilisateur
 * - Initialiser le token CSRF côté frontend
 */
async function loadProfile() {
    const user = await apiFetch('/api/profile');

    CSRF_TOKEN = user.data.csrf;

    const pseudo = document.getElementById('user-pseudo');
    if (pseudo) {
        pseudo.textContent = user.data.username;
    }
}

/* ========================
   TÂCHES : RÉCUPÉRATION
   ======================== */

async function loadTasks() {
    const params = new URLSearchParams({
        category: document.getElementById('category-filter')?.value || 'all',
        status: document.getElementById('status-filter')?.value || 'all',
        sort: document.getElementById('sort-filter')?.value || 'created_at DESC'
    });

    const result = await apiFetch(`/api/tasks?${params.toString()}`);
    renderTasks(result.data || []);
}

/* ========================
   ACCORDÉON DES TÂCHES (POINT CLÉ)
   ======================== */

/**
 * Rôle :
 * - Générer la structure de l’accordéon EXACTEMENT comme définie en HTML
 * - Gérer :
 *   - clic souris
 *   - navigation clavier (Enter / Espace)
 *   - aria-expanded / aria-controls
 */
function renderTasks(tasks) {
    const container = document.getElementById('task-list');
    if (!container) return;

    // Nettoyage du contenu (garde le titre)
    container.innerHTML = '<h3 id="task-list-title">Liste des tâches</h3>';

    // État vide UX
    if (!tasks.length) {
        const empty = document.createElement('p');
        empty.className = 'empty-state';
        empty.textContent =
            'Aucune tâche pour l’instant. Utilisez « Créer une tâche ».';
        container.appendChild(empty);
        return;
    }

    tasks.forEach((task, index) => {
        const article = document.createElement('article');
        article.className = 'task-item';

        const headerId = `task-header-${task.id}`;
        const detailsId = `task-details-${task.id}`;

        // Bouton d’en‑tête (contrôleur de l’accordéon)
        const headerBtn = document.createElement('button');
        headerBtn.className = 'task-header';
        headerBtn.id = headerId;
        headerBtn.type = 'button';
        headerBtn.setAttribute('aria-expanded', 'false');
        headerBtn.setAttribute('aria-controls', detailsId);
        headerBtn.textContent = `${task.name} (${task.status})`;

        // Zone de détails
        const details = document.createElement('div');
        details.className = 'task-details';
        details.id = detailsId;
        details.setAttribute('role', 'region');
        details.setAttribute('aria-labelledby', headerId);
        details.hidden = true;

        const desc = document.createElement('p');
        desc.textContent = task.description || 'Aucune description';

        // Actions (exemple minimal)
        const actions = document.createElement('div');
        actions.className = 'task-actions';

        const deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.textContent = 'Supprimer';
        deleteBtn.addEventListener('click', () => deleteTask(task.id));

        actions.appendChild(deleteBtn);
        details.appendChild(desc);
        details.appendChild(actions);

        /**
         * Gestion accordéon :
         * - clic souris
         * - clavier (Enter / Espace)
         */
        const toggle = () => {
            const isOpen = headerBtn.getAttribute('aria-expanded') === 'true';
            headerBtn.setAttribute('aria-expanded', String(!isOpen));
            details.hidden = isOpen;
        };

        headerBtn.addEventListener('click', toggle);
        headerBtn.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggle();
            }
        });

        article.appendChild(headerBtn);
        article.appendChild(details);
        container.appendChild(article);
    });
}

/* ========================
   CRUD TÂCHES
   ======================== */

async function createTask(event) {
    event.preventDefault();
    const form = event.target;

    await apiFetch('/api/tasks', {
        method: 'POST',
        body: JSON.stringify({
            name: form.name.value,
            category: form.category.value || null,
            start_date: form.start_date.value || null,
            due_date: form.due_date.value || null,
            description: form.description.value || null
        })
    });

    form.reset();
    loadTasks();
}

async function deleteTask(id) {
    if (!confirm('Supprimer cette tâche ?')) return;

    await apiFetch('/api/tasks', {
        method: 'DELETE',
        body: JSON.stringify({ id })
    });

    loadTasks();
}

/* ========================
   INIT GLOBALE
   ======================== */

document.addEventListener('DOMContentLoaded', async () => {

    if (document.getElementById('login-form')) {
        document.getElementById('login-form').onsubmit = login;
    }

    if (document.getElementById('logout-button')) {
        document.getElementById('logout-button').onclick = logout;
    }

    if (document.getElementById('task-form')) {
        document.getElementById('task-form').onsubmit = createTask;

        // Initialisation requise :
        // 1. profil (CSRF)
        // 2. tâches
        await loadProfile();
        loadTasks();

        document.getElementById('category-filter')?.addEventListener('change', loadTasks);
        document.getElementById('status-filter')?.addEventListener('change', loadTasks);
        document.getElementById('sort-filter')?.addEventListener('change', loadTasks);
    }

    if (window.location.pathname.endsWith('profil.php')) {
        await loadProfile();
    }
});