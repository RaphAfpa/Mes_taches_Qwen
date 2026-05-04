<?php $pageTitle = "Profil Utilisateur"; include 'includes/header_commun.php'; ?>
    <!--
        En-tête de la page de profil.
        Rôle : Afficher le titre de la page.
    -->
    <header>
        <h1>Profil Utilisateur</h1>
    </header>

    <!--
        Contenu principal de la page de profil.
        Rôle : Afficher les informations de l'utilisateur et les options de gestion du compte.
    -->
    <main class="container">
        <!--
            Section affichant les informations de base de l'utilisateur.
            Rôle : Présenter le pseudo et l'email de l'utilisateur.
            Ces informations sont remplies dynamiquement par JavaScript.
        -->
        <section class="user-info">
            <p><strong>Pseudo:</strong> <span id="profile-username">NomUtilisateur</span></p>
            <p><strong>Email:</strong> <span id="profile-email">utilisateur@example.com</span></p>
        </section>

        <!--
            Section des actions liées au profil.
            Rôle : Fournir des boutons pour modifier le mot de passe et supprimer le compte.
        -->
        <section class="profile-actions">
            <button class="blue-gray" id="change-password-button">Modifier mot de passe</button>
            <button class="red" id="delete-account-button">Supprimer compte</button>
        </section>

        <!--
            Section du formulaire de changement de mot de passe.
            Rôle : Permettre à l'utilisateur de mettre à jour son mot de passe.
            Ce formulaire est initialement caché et est affiché/masqué par JavaScript.
        -->
        <section id="password-change-form" style="display: none;">
            <h3>Modifier le mot de passe</h3>
            <form id="form-changer-mdp">
                <label for="current-password">Mot de passe actuel:</label>
                <input type="password" id="current-password" name="current_password" required>

                <label for="new-password">Nouveau mot de passe:</label>
                <input type="password" id="new-password" name="new_password" required>

                <label for="confirm-new-password">Confirmer nouveau mot de passe:</label>
                <input type="password" id="confirm-new-password" name="confirm_new_password" required>

                <button type="submit" class="green">Enregistrer</button>
                <button type="button" class="blue-gray" id="cancel-password-change">Annuler</button>
            </form>
        </section>
    </main>
    <?php include 'includes/footer_commun.php'; ?>