<?php $pageTitle = "Mes tâches - Créer"; include 'includes/header_commun.php'; ?>
    <header>
        <!--
            Titre principal de la page de création de tâche.
            Rôle : Indiquer clairement le but de la page.
        -->
        <h1>Créer une tâche</h1>
        <!--
            Navigation principale de l'en-tête.
            Rôle : Contenir les éléments de navigation, comme le bouton de retour.
        -->
        <nav>
            <!-- Bouton pour retourner à la page des tâches -->
            <button class="blue-gray" id="back-to-tasks-button">Retour</button>
        </nav>
    </header>

    <!--
        Contenu principal de la page.
        Rôle : Contenir le formulaire de création d'une nouvelle tâche.
    -->
    <main class="container">
        <h3>Créer une nouvelle tâche</h3>
        <!--
            Formulaire de création de tâche.
            Rôle : Collecter les informations nécessaires pour une nouvelle tâche.
            Les données de ce formulaire sont traitées par JavaScript (js/app.js).
        -->
        <form id="task-form">
            <label for="task-name">Nom de la tâche:</label>
            <input type="text" id="task-name" name="name" required>

            <label for="task-category">Catégorie:</label>
            <select id="task-category" name="category">
                <option value="">Sélectionner une catégorie</option>
                <option value="Travail">Travail</option>
                <option value="Bricolage">Bricolage</option>
                <option value="Loisirs">Loisirs</option>
            </select>

            <label for="task-start-date">Date de début:</label>
            <input type="date" id="task-start-date" name="start_date">

            <label for="task-due-date">Date d'échéance:</label>
            <input type="date" id="task-due-date" name="due_date">

            <button type="submit" class="green">Ajouter la tâche</button>
            <button type="button" class="blue-gray" id="cancel-task-form">Annuler</button>
        </form>
    </main>
    <?php include 'includes/footer_commun.php'; ?>