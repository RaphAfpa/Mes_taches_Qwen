<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">

    <!--
        Meta viewport : indispensable pour le responsive design.
        Rôle : adapter l’affichage aux écrans mobiles et tablettes.
        Bonnes pratiques UX :
        - évite le zoom forcé
        - garantit une mise à l’échelle correcte
    -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!--
        Titre de la page (variable).
        Rôle :
        - renseigné par chaque page via $pageTitle
        - affiché dans l’onglet du navigateur
        Accessibilité & UX :
        - essentiel pour les lecteurs d’écran
        - facilite la navigation par onglets
    -->
    <title><?= htmlspecialchars($pageTitle ?? 'Mes tâches', ENT_QUOTES, 'UTF-8') ?></title>

    <!--
        Favicon de l’application.
        Rôle UX :
        - repérage visuel rapide dans les onglets du navigateur
        - améliore la reconnaissance de l’application
        Important :
        - le favicon est une ressource chargée par le navigateur
        - il doit toujours être inclus via une balise <link>
    -->
    <link rel="icon" type="image/svg+xml" href="/images/MesTaches.svg">

    <!--
        Feuille de style principale.
        Rôle :
        - centraliser l’ensemble des styles de l’application
        - garantir une cohérence visuelle globale
        Bonne pratique :
        - un seul point d’entrée CSS pour faciliter la maintenance
    -->
    <link rel="stylesheet" href="/css/style.css">
</head>

<body>
    <!--
        Conteneur principal de l’application.
        Rôle :
        - englober toute l’interface
        - servir de référence pour les styles globaux
        - faciliter les mises en page (flex / grid)
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
            Cette zone est manipulée dynamiquement par le JavaScript.
        -->
        <div id="app-message" role="status" aria-live="polite"></div>

        <!--
            Début du contenu spécifique à chaque page.
            Important :
            - le header commun NE DOIT PAS contenir de logique métier
            - chaque page (index.php, taches.php, profil.php, etc.)
              fournit son propre contenu et son propre <header> visible
        -->
