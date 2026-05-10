// js/app.js

/* ========================
   CSRF
   ======================== */

let CSRF_TOKEN = null;

/* ========================
   API CLIENT
   ======================== */

async function apiFetch(url, options = {}) {
    const method = options.method || 'GET';

    const headers = {
        'Content-Type': 'application/json',
        ...(options.headers || {})
    };

    // ✅ Injection du token CSRF pour les requêtes mutatives
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
   TÂCHES
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

function renderTasks(tasks) {
    const container = document.getElementById('task-list');
    container.innerHTML = '<h3>Liste des tâches</h3>';

    if (!tasks.length) {
        container.innerHTML += '<p>Aucune tâche.</p>';
        return;
    }

    tasks.forEach(task => {
        const div = document.createElement('div');
        div.className = 'task-card';
        div.innerHTML = `
            <div class="task-header">${task.name} (${task.status})</div>
            <div class="task-details" hidden>
                <p>${task.description || ''}</p>
                <button class="delete-task" data-id="${task.id}">Supprimer</button>
            </div>
        `;

        div.querySelector('.task-header').onclick = () => {
            div.querySelector('.task-details').toggleAttribute('hidden');
        };

        div.querySelector('.delete-task').onclick = () => deleteTask(task.id);
        container.appendChild(div);
    });
}

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
   PROFIL UTILISATEUR
   ======================== */

async function loadProfile() {
    const user = await apiFetch('/api/profile');

    // ✅ Récupération du token CSRF depuis l’API
    CSRF_TOKEN = user.data.csrf;

    document.getElementById('profile-username').textContent = user.data.username;
    document.getElementById('profile-email').textContent = user.data.email;
}

/* ========================
   INIT
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
        await loadProfile();   // ✅ charge aussi le CSRF
        loadTasks();

        document.getElementById('category-filter')?.addEventListener('change', loadTasks);
        document.getElementById('status-filter')?.addEventListener('change', loadTasks);
        document.getElementById('sort-filter')?.addEventListener('change', loadTasks);
    }

    if (window.location.pathname.endsWith('profil.php')) {
        await loadProfile();
    }
});