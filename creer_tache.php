<?php
/**
 * Page : creer_tache.php
 *
 * Rôle :
 * - Afficher un formulaire dédié à la création d’une tâche.
 * - Fournir une interface claire, maintenable et évolutive.
 * - Laisser JavaScript gérer l’interaction avec l’API.
 *
 * Important :
 * - AUCUNE logique métier n’est exécutée dans cette page.
 * - AUCUNE insertion en base de données n’est faite ici.
 * - La validation serveur reste assurée par TaskController.php.
 * - La page est initialisée par js/app.js, qui vérifie l’authentification via /api/profile.
 */

$pageTitle = "Créer une tâche";
include 'includes/header_commun.php';
?>

<!--
    En-tête spécifique à la page de création de tâche.

    Rôle UX :
    - afficher le logo de l’application ;
    - fournir un point de retour explicite vers la liste des tâches ;
    - conserver une cohérence visuelle avec taches.php.
-->
<div id="header-wrapper">
    <header class="container conteneur" role="banner">

        <!--
            Logo cliquable.
            Rôle :
            - permettre un retour rapide vers la liste des tâches ;
            - offrir un repère visuel stable dans l’application.
        -->
        <a href="taches.php" id="logo-link">
            <img
                src="images/MesTaches.svg"
                alt="Mes Tâches – Retour à la liste"
                id="header-logo"
            >
        </a>

        <!--
            Action secondaire.
            Rôle :
            - permettre à l’utilisateur de revenir à la liste sans créer de tâche ;
            - ne déclenche aucun appel API.
        -->
        <a href="taches.php" class="blue-gray button-link bleu-gris lien-bouton">
            Retour
        </a>

    </header>
</div>

<!--
    Contenu principal de la page.

    Rôle :
    - contenir le formulaire de création de tâche ;
    - fournir une structure sémantique claire ;
    - faciliter la validation côté client par JavaScript.
-->
<main class="container conteneur" role="main">

    <!--
        Titre principal de la page.

        Rôle :
        - indiquer clairement l’objectif de la page ;
        - servir de repère pour la navigation clavier et les lecteurs d’écran.
    -->
    <h1>Créer une tâche</h1>

    <!--
        Texte d’aide court.

        Rôle UX :
        - expliquer que le statut n’est pas saisi manuellement ;
        - rappeler que le statut sera calculé automatiquement côté serveur.
    -->
    <p class="form-help aide-formulaire">
        Renseignez les informations de la tâche. Le statut sera calculé automatiquement à partir des dates.
    </p>

    <!--
        Zone dédiée aux messages d’erreur du formulaire.

        Rôle :
        - afficher les erreurs de validation côté client ;
        - rester accessible grâce à aria-live.
    -->
    <div
        id="create-task-message"
        class="form-message message-formulaire"
        role="status"
        aria-live="polite"
    ></div>

    <!--
        Formulaire de création de tâche.

        Important :
        - l’attribut novalidate permet à js/app.js de gérer une validation client personnalisée ;
        - la validation serveur reste obligatoire et prioritaire ;
        - les champs name correspondent aux données attendues par l’API POST /api/tasks.
    -->
    <form id="create-task-form" novalidate>

        <!--
            Nom de la tâche.

            Contraintes fonctionnelles :
            - obligatoire ;
            - minimum 3 caractères ;
            - maximum 50 caractères.
        -->
        <div class="form-group groupe-formulaire">
            <label for="task-name">Nom de la tâche</label>
            <input
                type="text"
                id="task-name"
                name="name"
                minlength="3"
                maxlength="50"
                required
                aria-describedby="task-name-help"
            >
            <p id="task-name-help" class="field-help aide-champ">
                3 à 50 caractères.
            </p>
        </div>

        <!--
            Catégorie.

            Contraintes fonctionnelles :
            - obligatoire ;
            - valeurs autorisées : Travail, Bricolage, Loisirs ;
            - l’option vide force l’utilisateur à faire un choix explicite.
        -->
        <div class="form-group groupe-formulaire">
            <label for="task-category">Catégorie</label>
            <select
                id="task-category"
                name="category"
                required
            >
                <option value="">Sélectionner...</option>
                <option value="Travail">Travail</option>
                <option value="Bricolage">Bricolage</option>
                <option value="Loisirs">Loisirs</option>
            </select>
        </div>

        <!--
            Date de début.

            Contraintes fonctionnelles :
            - facultative ;
            - si le champ est vide, js/app.js enverra null à l’API ;
            - cette date sert au calcul du statut automatique.
        -->
        <div class="form-group groupe-formulaire">
            <label for="task-start-date">Date de début</label>
            <input
                type="date"
                id="task-start-date"
                name="start_date"
            >
        </div>

        <!--
            Date d’échéance.

            Contraintes fonctionnelles :
            - obligatoire ;
            - doit être une date valide ;
            - ne doit pas être antérieure à la date de début si celle-ci est renseignée.
        -->
        <div class="form-group groupe-formulaire">
            <label for="task-due-date">Date d’échéance</label>
            <input
                type="date"
                id="task-due-date"
                name="due_date"
                required
            >
        </div>

        <!--
            Description.

            Contraintes fonctionnelles :
            - facultative ;
            - texte simple uniquement ;
            - maximum 500 caractères ;
            - affichage minimum 3 lignes ;
            - hauteur maximale gérée par CSS ;
            - ascenseur si le contenu dépasse la hauteur maximale.
        -->
        <div class="form-group groupe-formulaire">
            <label for="task-description">Description</label>
            <textarea
                id="task-description"
                name="description"
                rows="3"
                maxlength="500"
                aria-describedby="task-description-help"
            ></textarea>
            <p id="task-description-help" class="field-help aide-champ">
                500 caractères maximum. Les liens restent du texte simple.
            </p>
        </div>

        <!--
            Actions du formulaire.

            Valider :
            - déclenche la validation côté client ;
            - envoie ensuite les données à POST /api/tasks via js/app.js ;
            - redirige vers taches.php après succès.

            Annuler :
            - revient immédiatement vers taches.php ;
            - ne déclenche aucun appel API ;
            - ne modifie aucune donnée.
        -->
        <div class="form-actions actions-formulaire">
            <button type="submit" class="green vert">
                Valider
            </button>

            <a href="taches.php" class="blue-gray button-link bleu-gris lien-bouton">
                Annuler
            </a>
        </div>

    </form>

</main>

<?php include 'includes/footer_commun.php'; ?>