<?php $pageTitle = "Mes tâches"; include 'includes/header_commun.php'; ?>
<div id="header-wrapper">
    <header class="container">
        <a href="taches.php" id="logo-link">
            <img src="images/MesTaches.svg" alt="Logo Mes Tâches" id="header-logo">
        </a>
        <div id="user-info-and-menu">
            <span id="user-pseudo">Pseudo Utilisateur</span>
            <button class="blue-gray" id="menu-button">Menu</button>
            <nav id="header-menu">
                <ul>
                    <li><a href="profil.php">Profil</a></li>
                    <li><a href="#" id="logout-button">Déconnexion</a></li>
                </ul>
            </nav>
        </div>
    </header>
</div>

<!--
    Contenu principal de la page des tâches.
    Rôle : Contenir les contrôles pour la gestion des tâches (création, filtres, liste).
-->
<main class="container">
    <div id="main-content-area">
        <div class="page-title-bar">
            <h2 id="tasks-page-title">Mes tâches</h2>
            <!-- Bouton pour déclencher la création d'une nouvelle tâche -->
            <button class="green" id="create-task-button">Créer une tâche</button>
        </div>

        <!--
            Section des filtres et du tri des tâches.
            Rôle : Permettre à l'utilisateur de filtrer les tâches par catégorie et statut,
            ainsi que de les trier par différentes options.
        -->
        <section class="filters">
            <h3>Filtres</h3>
            <div class="grid filter-grid">
                <div>
                    <label for="category-filter">Catégorie:</label>
                    <select id="category-filter">
                        <option value="all">Toutes</option>
                        <option value="Travail">Travail</option>
                        <option value="Bricolage">Bricolage</option>
                        <option value="Loisirs">Loisirs</option>
                    </select>
                </div>
                <div>
                    <label for="status-filter">Statut:</label>
                    <select id="status-filter">
                        <option value="all">Tous</option>
                        <option value="Prévue">Prévue</option>
                        <option value="En cours">En cours</option>
                        <option value="Dépassée">Dépassée</option>
                        <option value="Terminée">Terminée</option>
                    </select>
                </div>
                <div>
                    <label for="sort-filter">Trier par:</label>
                    <select id="sort-filter">
                        <option value="due_date ASC">Échéance (croissant)</option>
                        <option value="due_date DESC">Échéance (décroissant)</option>
                        <option value="created_at DESC">Création (décroissant)</option>
                        <option value="created_at ASC">Création (croissant)</option>
                    </select>
                </div>
                <div class="filter-checkbox-container">
                    <label for="hide-completed-filter">
                        <input type="checkbox" id="hide-completed-filter" name="hide_completed" checked>
                        Masquer terminées
                    </label>
                </div>
            </div>
        </section>

        <!--
            Section de la liste des tâches.
            Rôle : Afficher les tâches de l'utilisateur sous forme d'accordéon.
            Le contenu de cette section est généré dynamiquement par JavaScript.
        -->
        <section class="task-list" id="task-list">
            <h3>Liste des tâches</h3>
            <!-- Un exemple de carte de tâche est inclus pour la structure, mais le contenu réel est généré par JS -->
            <div class="task-card">
                <div class="task-header">
                    <span class="task-status" style="background-color: var(--color-planned);"></span>
                    <span class="task-name">Exemple de tâche (Prévue)</span>
                </div>
                <div class="task-details">
                    <p>Catégorie: Travail</p>
                    <p>Statut: Prévue</p>
                    <p>Date de début: 2025-08-11</p>
                    <p>Date d'échéance: 2025-08-11</p>
                    <p>Créée le: 2025-08-11</p>
                    <button class="blue-gray">Modifier</button>
                    <button class="green">Terminée</button>
                    <button class="red">Supprimer</button>
                </div>
            </div>
        </section>
    </div>
</main>
<?php include 'includes/footer_commun.php'; ?>