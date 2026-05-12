<?php
/**
 * Page : modif_tache.php
 *
 * Rôle :
 * - Afficher un formulaire dédié à la modification d’une tâche existante.
 * - Fournir une interface claire pour modifier les champs éditables :
 *   nom, catégorie, date de début, date d’échéance et description.
 *
 * Important :
 * - AUCUNE logique métier n’est exécutée dans cette page.
 * - AUCUNE mise à jour en base de données n’est faite ici.
 * - Le statut n’est PAS modifiable dans ce formulaire.
 * - La validation serveur reste assurée par TaskController.php.
 * - La page est initialisée par js/app.js :
 *   - lecture de l’ID dans l’URL ;
 *   - vérification de l’authentification via /api/profile ;
 *   - récupération de la tâche via GET /api/tasks?id=...
 *   - préremplissage du formulaire ;
 *   - envoi des modifications via PUT /api/tasks.
 */

$pageTitle = "Modifier une tâche";
include 'includes/header_commun.php';
?>

<!--
    En-tête spécifique à la page de modification de tâche.

    Rôle UX :
    - afficher le logo de l’application ;
    - fournir un retour clair vers la liste des tâches ;
    - conserver une cohérence visuelle avec les autres pages.
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
            Lien de retour.

            Rôle :
            - permettre à l’utilisateur de quitter la page de modification ;
            - ne déclencher aucun appel API ;
            - revenir simplement vers la liste des tâches.
        -->
        <a href="taches.php" class="button-link blue-gray lien-bouton bleu-gris">
            Retour
        </a>

    </header>
</div>

<!--
    Contenu principal de la page.

    Rôle :
    - contenir le formulaire de modification ;
    - fournir une structure sémantique claire ;
    - permettre à js/app.js de préremplir et valider les champs.
-->
<main class="container conteneur" role="main">

    <!--
        Titre principal.

        Rôle :
        - indiquer clairement l’objectif de la page ;
        - servir de repère aux utilisateurs et aux lecteurs d’écran.
    -->
    <h1>Modifier une tâche</h1>

    <!--
        Texte d’aide.

        Rôle UX :
        - préciser que seuls certains champs sont modifiables ;
        - rappeler que le statut n’est pas modifié directement ici.
    -->
    <p class="form-help aide-formulaire">
        Modifiez les informations de la tâche. Le statut n’est pas modifiable dans ce formulaire.
    </p>

    <!--
        Zone dédiée aux messages d’erreur.

        Rôle :
        - afficher les erreurs de chargement ou de validation côté client ;
        - rester accessible grâce à aria-live.
    -->
    <div
        id="edit-task-message"
        class="form-message message-formulaire"
        role="status"
        aria-live="polite"
    ></div>

    <!--
        Formulaire de modification de tâche.

        Important :
        - l’attribut novalidate permet à js/app.js de gérer une validation client personnalisée ;
        - la validation serveur reste obligatoire et prioritaire ;
        - les champs name correspondent aux données attendues par l’API PUT /api/tasks ;
        - l’ID de la tâche est stocké dans un champ caché après lecture de l’URL.
    -->
    <form id="edit-task-form" novalidate>

        <!--
            ID technique de la tâche.

            Rôle :
            - stocker l’ID récupéré depuis l’URL ;
            - permettre à js/app.js d’envoyer l’ID à PUT /api/tasks.

            Important :
            - ce champ caché ne constitue PAS une sécurité ;
            - l’API vérifie toujours que la tâche appartient à l’utilisateur connecté.
        -->
        <input
            type="hidden"
            id="edit-task-id"
            name="id"
        >

        <!--
            Nom de la tâche.

            Contraintes fonctionnelles :
            - obligatoire ;
            - minimum 3 caractères ;
            - maximum 50 caractères.
        -->
        <div class="form-group groupe-formulaire">
            <label for="edit-task-name">Nom de la tâche</label>
            <input
                type="text"
                id="edit-task-name"
                name="name"
                minlength="3"
                maxlength="50"
                required
                aria-describedby="edit-task-name-help"
            >
            <p id="edit-task-name-help" class="field-help aide-champ">
                3 à 50 caractères.
            </p>
        </div>

        <!--
            Catégorie.

            Contraintes fonctionnelles :
            - obligatoire ;
            - valeurs autorisées : Travail, Bricolage, Loisirs ;
            - l’option vide force un choix explicite.
        -->
        <div class="form-group groupe-formulaire">
            <label for="edit-task-category">Catégorie</label>
            <select
                id="edit-task-category"
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
            - cette date sert au recalcul automatique du statut si la tâche n’est pas Terminée.
        -->
        <div class="form-group groupe-formulaire">
            <label for="edit-task-start-date">Date de début</label>
            <input
                type="date"
                id="edit-task-start-date"
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
            <label for="edit-task-due-date">Date d’échéance</label>
            <input
                type="date"
                id="edit-task-due-date"
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
            <label for="edit-task-description">Description</label>
            <textarea
                id="edit-task-description"
                name="description"
                rows="3"
                maxlength="500"
                aria-describedby="edit-task-description-help"
            ></textarea>
            <p id="edit-task-description-help" class="field-help aide-champ">
                500 caractères maximum. Les liens restent du texte simple.
            </p>
        </div>

        <!--
            Actions du formulaire.

            Valider :
            - déclenche la validation côté client ;
            - envoie ensuite les données à PUT /api/tasks via js/app.js ;
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

            <a href="taches.php" class="button-link blue-gray lien-bouton bleu-gris">
                Annuler
            </a>
        </div>

    </form>

</main>

<?php include 'includes/footer_commun.php'; ?>