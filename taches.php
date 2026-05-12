<?php
/**
 * Page : taches.php
 *
 * Rôle :
 * - Afficher la liste des tâches de l’utilisateur connecté
 * - Fournir les contrôles (filtres, tri, actions)
 * - Définir la structure HTML de l’accordéon (UX + accessibilité)
 *
 * Important :
 * - AUCUNE logique métier ici
 * - AUCUN calcul d’état
 * - Le JavaScript se contente de peupler et d’animer la structure existante
 */

$pageTitle = "Mes tâches";
include 'includes/header_commun.php';
?>

<!--
    En-tête spécifique à la page des tâches.
    Rôle UX :
    - navigation principale
    - accès au profil
    - déconnexion
-->
<div id="header-wrapper">
    <header class="container" role="banner">

        <!-- Logo cliquable : retour à la liste des tâches -->
        <a href="taches.php" id="logo-link">
            <img
                src="images/MesTaches.svg"
                alt="Mes Tâches – Retour à la liste"
                id="header-logo"
            >
        </a>

        <!--
            Zone utilisateur + menu.
            Structure simple, manipulée par JS (ou CSS) sans logique cachée.
        -->
        <div id="user-info-and-menu">
            <span
                id="user-pseudo"
                aria-live="polite"
            >
                Utilisateur
            </span>

            <!--
                Bouton de menu.
                Accessibilité :
                - button réel (clavier + lecteur d’écran)
                - aria-expanded sera géré par JS
            -->
            <button
                class="blue-gray"
                id="menu-button"
                aria-haspopup="true"
                aria-expanded="false"
                aria-controls="header-menu"
            >
                Menu
            </button>

            <!-- Navigation secondaire -->
            <nav
                id="header-menu"
                aria-label="Menu utilisateur"
            >
                <ul>
                    <li><a href="profil.php">Profil</a></li>
                    <li>
                        <!--
                            Déconnexion via JS.
                            Lien neutre pour éviter les redirections involontaires.
                        -->
                        <a href="#" id="logout-button">Déconnexion</a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>
</div>

<!--
    Contenu principal de la page.
    Rôle : toutes les fonctionnalités métier liées aux tâches.
-->
<main class="container" role="main">

    <div id="main-content-area">

        <!-- Barre de titre + action principale -->
        <div class="page-title-bar">
            <h2 id="tasks-page-title">Mes tâches</h2>

            <!--
                Action principale : création d’une tâche.

                Rôle :
                - envoyer l’utilisateur vers la page dédiée creer_tache.php ;
                - éviter d’intégrer le formulaire de création dans taches.php ;
                - préserver la responsabilité unique de cette page : afficher et filtrer les tâches.

                Remarque :
                - La création elle-même sera gérée par creer_tache.php + js/app.js + POST /api/tasks.
            -->
            <a
                href="creer_tache.php"
                class="green action-link"
                id="create-task-button"
            >
                Créer une tâche
            </a>
        </div>

        <!--
            Section des filtres et du tri.
            Rôle UX : permettre de réduire / organiser la liste de tâches.
            Accessibilité :
            - section sémantique
            - labels explicitement associés
        -->
<section
    class="filters"
    aria-labelledby="filters-title"
>
    <h3 id="filters-title">Filtres et tri</h3>

    <div class="filters-grid">

        <div class="filter-item">
            <label for="category-filter">Catégorie</label>
            <select id="category-filter">
                <option value="all">Toutes</option>
                <option value="Travail">Travail</option>
                <option value="Bricolage">Bricolage</option>
                <option value="Loisirs">Loisirs</option>
            </select>
        </div>

        <div class="filter-item">
            <label for="status-filter">Statut</label>
            <select id="status-filter">
                <option value="all">Tous</option>
                <option value="À planifier">À planifier</option>
                <option value="Prévue">Prévue</option>
                <option value="En cours">En cours</option>
                <option value="Dépassée">Dépassée</option>
                <option value="Terminée">Terminée</option>
            </select>
        </div>

        <div class="filter-item">
            <label for="sort-filter">Trier par</label>
            <select id="sort-filter">
                <option value="created_at DESC">Création (récent)</option>
                <option value="created_at ASC">Création (ancien)</option>
                <option value="due_date ASC">Échéance (croissante)</option>
                <option value="due_date DESC">Échéance (décroissante)</option>
            </select>
        </div>

    </div>
</section>
        <!--
            Section LISTE DES TÂCHES
            ⚠️ POINT CLÉ POUR L’ACCORDÉON
            --------------------------------
            - Chaque tâche sera un <article>
            - Le bouton d’en-tête contrôle l’ouverture/fermeture
            - aria-expanded / aria-controls seront gérés par JS
            - La structure NE DOIT PAS être modifiée côté JS
        -->
        <section
            id="task-list"
            class="task-list"
            aria-labelledby="task-list-title"
        >
            <h3 id="task-list-title">Liste des tâches</h3>

            <!--
                État vide par défaut.
                Rôle UX :
                - éviter un écran “blanc”
                - message clair pour l’utilisateur
                Sera remplacé dynamiquement par JS si des tâches existent.
            -->
            <p class="empty-state">
                Aucune tâche pour l’instant.
                Utilisez « Créer une tâche » pour commencer.
            </p>

            <!--
                Exemple STRUCTURE D’UNE TÂCHE (commentée)
                -----------------------------------------
                <article class="task-item">
                    <button
                        class="task-header"
                        aria-expanded="false"
                        aria-controls="task-details-1"
                        id="task-header-1"
                    >
                        Titre de la tâche
                    </button>

                    <div
                        class="task-details"
                        id="task-details-1"
                        role="region"
                        aria-labelledby="task-header-1"
                        hidden
                    >
                        <p>Description…</p>
                        <div class="task-actions">
                            <button>Modifier</button>
                            <button>Supprimer</button>
                        </div>
                    </div>
                </article>

                👉 Le JS :
                - clone / construit CETTE structure
                - gère uniquement l’attribut hidden et aria-expanded
            -->
        </section>

    </div>
</main>

<?php include 'includes/footer_commun.php'; ?>