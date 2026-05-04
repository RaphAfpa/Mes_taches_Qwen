<?php
// Définit le titre de la page qui sera utilisé dans header_commun.php
$pageTitle = "Mes tâches - Accueil";

// Inclut le fichier d'en-tête commun à toutes les pages.
include 'includes/header_commun.php';
?>

<div id="header-wrapper">
    <header class="container">
        <a href="index.php" id="logo-link">
            <img src="images/MesTaches.svg" alt="Logo Mes Tâches" id="header-logo">
        </a>
        <div id="auth-buttons">
            <button class="blue-gray" id="login-button">Se connecter</button>
            <button class="green" id="register-button">Créer un compte</button>
        </div>
    </header>
</div>

    <!--
        Contenu principal de la page d'accueil.
        Rôle : Afficher un message de bienvenue et fournir les formulaires pour se connecter ou créer un compte.
        L'interaction (affichage/masquage) des formulaires est gérée par le fichier JS `auth.js`.
    -->
    <main class="container">
        <h2>Bienvenue sur Mes tâches</h2>
        <p>Organisez votre vie et vos projets facilement.</p>
        
        <!--
            Section contenant les formulaires de connexion et d'inscription.
            Rôle : Regrouper les éléments d'authentification pour une gestion simplifiée par JavaScript.
            Les formulaires sont initialement masqués via `style="display: none;"` et affichés par des clics sur les boutons dans l'en-tête.
        -->
        <section id="auth-forms">
            <!-- 
                Formulaire de connexion.
                Rôle : Permettre à un utilisateur existant de se connecter.
                La soumission est gérée par `auth.js` qui envoie les données à l'API.
            -->
            <form id="login-form" style="display: none;">
                <h3>Connexion</h3>
                <label for="username">Nom d’utilisateur:</label>
                <input type="text" id="username" name="username" required>
                <label for="password">Mot de passe:</label>
                <input type="password" id="password" name="password" required>
                <button type="submit" class="green">Se connecter</button>
            </form>

            <!-- 
                Formulaire d'inscription.
                Rôle : Permettre à un nouvel utilisateur de créer un compte.
                La soumission est gérée par `auth.js` qui envoie les données à l'API.
            -->
            <form id="register-form" style="display: none;">
                <h3>Créer un compte</h3>
                <label for="reg-username">Nom d’utilisateur:</label>
                <input type="text" id="reg-username" name="username" required>
                <label for="reg-email">Email:</label>
                <input type="email" id="reg-email" name="email" required>
                <label for="reg-password">Mot de passe:</label>
                <input type="password" id="reg-password" name="password" required>
                <button type="submit" class="green">Créer le compte</button>
            </form>
        </section>
    </main>

<?php
// Inclut le fichier de pied de page commun.
include 'includes/footer_commun.php'; 
?>