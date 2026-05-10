// =========================================================
// Helper fetch JSON (cookies inclus)
// =========================================================
async function fetchJSON(url, options = {}) {
    const response = await fetch(url, {
        credentials: 'include',
        ...options
    });
    return response.json();
}

// =========================================================
// Récupération utilisateur authentifié
// =========================================================
async function getAuthUser() {
    try {
        const res = await fetchJSON('/api/profile');
        return (res && res.success) ? res.user : null;
    } catch {
        return null;
    }
}

// =========================================================
// INDEX.PHP
// =========================================================
function initIndexPage() {
    const loginBtn = document.getElementById('login-button');
    const registerBtn = document.getElementById('register-button');
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    if (loginBtn && loginForm) {
        loginBtn.addEventListener('click', () => {
            loginForm.style.display = 'block';
            if (registerForm) registerForm.style.display = 'none';
        });
    }

    if (registerBtn && registerForm) {
        registerBtn.addEventListener('click', () => {
            registerForm.style.display = 'block';
            if (loginForm) loginForm.style.display = 'none';
        });
    }

    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const res = await fetchJSON('/api/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    username: loginForm.username.value,
                    password: loginForm.password.value
                })
            });

            if (res.success) {
                window.location.href = 'taches.php';
            }
        });
    }

    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const res = await fetchJSON('/api/register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    username: registerForm.username.value,
                    email: registerForm.email.value,
                    password: registerForm.password.value
                })
            });

            if (res.success) {
                window.location.href = 'index.php';
            }
        });
    }
}

// =========================================================
// TACHES.PHP
// =========================================================
function initTasksPage(user) {

    // Pseudo
    const pseudo = document.getElementById('user-pseudo');
    if (pseudo) pseudo.textContent = user.username;

    // Menu burger
    const menuButton = document.getElementById('menu-button');
    const headerMenu = document.getElementById('header-menu');

    if (menuButton && headerMenu) {
        menuButton.addEventListener('click', (e) => {
            e.stopPropagation();
            headerMenu.classList.toggle('menu-open');
        });

        document.addEventListener('click', (e) => {
            if (
                !headerMenu.contains(e.target) &&
                !menuButton.contains(e.target)
            ) {
                headerMenu.classList.remove('menu-open');
            }
        });
    }

    // Déconnexion (DÉFINITIVE)
    const logoutBtn = document.getElementById('logout-button');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();

            await fetchJSON('/api/logout');

            // Redirection SANS risque de boucle
            window.location.href = 'index.php';
        });
    }

    // Créer tâche
    const createTaskBtn = document.getElementById('create-task-button');
    if (createTaskBtn) {
        createTaskBtn.addEventListener('click', () => {
            alert('Créer une tâche (hook OK)');
        });
    }
}

// =========================================================
// BOOTSTRAP GLOBAL
// =========================================================
document.addEventListener('DOMContentLoaded', async () => {

    const page =
        window.location.pathname.split('/').pop() || 'index.php';

    const user = await getAuthUser();

    if (page === 'index.php') {
        // ✅ PAS de redirection automatique
        initIndexPage();
        return;
    }

    if (page === 'taches.php') {
        if (!user) {
            window.location.href = 'index.php';
            return;
        }
        initTasksPage(user);
    }
});