<?php
/**
 * Page : profil.php
 *
 * Rôle :
 * - Afficher les informations du compte utilisateur
 * - Permettre la modification du mot de passe
 * - Permettre la suppression du compte
 *
 * Principes UX / accessibilité :
 * - Informations lisibles sans JS
 * - Formulaires correctement balisés (label, aria)
 * - Actions explicites et confirmées
 * - Aucune logique métier côté PHP
 */

$pageTitle = "Profil utilisateur";
include 'includes/header_commun.php';
?>

<!--
    En-tête de la page Profil.
    Rôle :
    - Identifier clairement la page
    - Point de repère pour la navigation clavier et lecteurs d’écran
-->
<header class="container">
    <h1>Profil utilisateur</h1>
</header>

<main class="container" role="main">

    <!--
        Section : informations utilisateur
        Rôle UX :
        - Affichage simple et lisible
        - Données injectées dynamiquement par JS
        Accessibilité :
        - Texte standard, lisible par défaut
    -->
    <section class="user-info" aria-labelledby="profile-info-title">
        <h2 id="profile-info-title">Informations du compte</h2>

        <p>
            <strong>Pseudo :</strong>
            <span id="profile-username">—</span>
        </p>

        <p>
            <strong>Email :</strong>
            <span id="profile-email">—</span>
        </p>
    </section>

    <!--
        Section : actions utilisateur
        Rôle :
        - Actions sensibles regroupées et clairement identifiées
        UX :
        - Boutons explicites
        - Couleurs différenciées (information / danger)
    -->
    <section class="profile-actions" aria-labelledby="profile-actions-title">
        <h2 id="profile-actions-title">Actions sur le compte</h2>

        <button
            type="button"
            class="blue-gray"
            id="change-password-button"
            aria-controls="password-change-section"
            aria-expanded="false"
        >
            Modifier le mot de passe
        </button>

        <button
            type="button"
            class="red"
            id="delete-account-button"
        >
            Supprimer le compte
        </button>
    </section>

    <!--
        Section : formulaire de changement de mot de passe
        Important :
        - Caché par défaut
        - Révélé via JS
        Accessibilité :
        - aria-live pour retour utilisateur
        - labels associés
    -->
    <section
        id="password-change-section"
        hidden
        aria-labelledby="password-change-title"
    >
        <h2 id="password-change-title">Modifier le mot de passe</h2>

        <form
            id="password-change-form"
            novalidate
        >

            <div class="form-group">
                <label for="current-password">
                    Mot de passe actuel
                </label>
                <input
                    type="password"
                    id="current-password"
                    name="current_password"
                    required
                    autocomplete="current-password"
                >
            </div>

            <div class="form-group">
                <label for="new-password">
                    Nouveau mot de passe
                </label>
                <input
                    type="password"
                    id="new-password"
                    name="new_password"
                    required
                    autocomplete="new-password"
                >
            </div>

            <div class="form-group">
                <label for="confirm-new-password">
                    Confirmer le nouveau mot de passe
                </label>
                <input
                    type="password"
                    id="confirm-new-password"
                    name="confirm_new_password"
                    required
                    autocomplete="new-password"
                >
            </div>

            <!--
                Zone de message dédiée au formulaire
                Rôle :
                - erreurs de validation
                - confirmation succès
                aria-live="polite" pour annonces accessibles
            -->
            <div
                class="form-message"
                aria-live="polite"
            ></div>

            <div class="form-actions">
                <button type="submit" class="green">
                    Enregistrer
                </button>

                <button
                    type="button"
                    class="blue-gray"
                    id="cancel-password-change"
                >
                    Annuler
                </button>
            </div>

        </form>
    </section>

</main>

<?php include 'includes/footer_commun.php'; ?>