<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">

    <!--
        Meta viewport : indispensable pour le responsive design.
        Rôle : adapter l’affichage aux écrans mobiles et tablettes.
        Bonnes pratiques UX : évite le zoom forcé et les problèmes de mise à l’échelle.
    -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!--
        Titre de la page (variable).
        Rôle : renseigné par chaque page via $pageTitle.
        Accessibilité : essentiel pour les lecteurs d’écran et la navigation par onglets.
        SEO : utile, même sur une application interne.
    -->
    <title><?= htmlspecialchars($pageTitle ?? 'Mes tâches', ENT_QUOTES, 'UTF-8') ?></title>

    <!--
        Favicon de l’application.
        Rôle UX : repérage visuel rapide dans les onglets du navigateur.
        Format SVG : léger, net, scalable.
    -->
    /images/MesTaches.svg

    <!--
        Feuille de style principale.
        Rôle : centraliser tous les styles (layout, composants, accessibilité).
        Bonne pratique : un seul point d’entrée CSS pour une meilleure maintenance.
    -->
    /css/style.css
</head>

<body>
    <!--
        Conteneur principal de l’application.
        Rôle :
        - englober toute l’interface
        - permettre une mise en page cohérente (flex / grid)
        - servir de référence pour les styles globaux
    -->
    <div class="contenu">

        <!--
            Zone de messages applicatifs (feedback utilisateur).
            Rôle UX :
            - afficher les messages de succès / erreur / information
            - éviter les alert() bloquants
            Accessibilité :
            - role="status" : annonce polie des changements
            - aria-live="polite" : lu par les lecteurs d’écran sans interrompre
            Cette zone sera manipulée dynamiquement par le JavaScript.
        -->
        <div id="app-message" role="status" aria-live="polite"></div>

        <!--
            Début du contenu spécifique à chaque page.
            Important :
            - le header commun NE DOIT PAS contenir de logique métier
            - chaque page (taches.php, profil.php, etc.) fournit son propre contenu
        -->