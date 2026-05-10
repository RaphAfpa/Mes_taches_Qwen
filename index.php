<?php
$pageTitle = "Mes tâches - Accueil";
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
    Page d’accueil de l’application.
    - Présentation
    - Connexion
    - Inscription
    L’UX est gérée par js/app.js
-->
<main class="container">
    <h2>Bienvenue sur Mes tâches</h2>
    <p>Organisez votre vie et vos projets facilement.</p>

    <section id="auth-forms">

        <!-- Formulaire de connexion -->
        <form id="login-form" style="display: none;">
            <h3>Connexion</h3>
            <p class="form-error" aria-live="polite"></p>

            <label for="username">Nom d’utilisateur :</label>
            <input type="text" id="username" name="username"
                   autocomplete="username" required>

            <label for="password">Mot de passe :</label>
            <input type="password" id="password" name="password"
                   autocomplete="current-password" required>

            <button type="submit" class="green">Se connecter</button>
        </form>

        <!-- Formulaire d’inscription -->
        <form id="register-form" style="display: none;">
            <h3>Créer un compte</h3>
            <p class="form-error" aria-live="polite"></p>

            <label for="reg-username">Nom d’utilisateur :</label>
            <input type="text" id="reg-username" name="username"
                   autocomplete="username" required>

            <label for="reg-email">Email :</label>
            <input type="email" id="reg-email" name="email"
                   autocomplete="email" required>

            <label for="reg-password">Mot de passe :</label>
            <input type="password" id="reg-password" name="password"
                   autocomplete="new-password" required>

            <button type="submit" class="green">Créer le compte</button>
        </form>

    </section>
</main>

<?php
include 'includes/footer_commun.php';
