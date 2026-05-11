<!--
            Fin du conteneur principal de l’application.

            Rôle :
            - Ferme la structure englobante définie dans header_commun.php
            - Garantit une hiérarchie DOM cohérente (important pour l’accessibilité)
        -->
    </div> <!-- /.contenu -->

    <!--
        Inclusion du script JavaScript principal de l’application.

        Pourquoi ici (fin du <body>) :

        ✅ Performance :
           - Le HTML est entièrement chargé avant l’exécution du JS
           - Pas de blocage du rendu

        ✅ Robustesse :
           - Tous les éléments DOM sont disponibles
           - Pas besoin de 'defer' ou de hacks

        ✅ Accessibilité :
           - Les lecteurs d’écran parcourent d’abord le contenu
           - Le comportement interactif vient ensuite

        ✅ Maintenabilité :
           - Un seul point d’entrée JS
           - Pas de duplication de scripts
     -->
    <script src="js/app.js"></script>

    <!--
        Fin du document HTML.

        Bonnes pratiques respectées :
        - </body> juste après les scripts
        - </html> immédiatement après

        À noter :
        - Aucun script inline (prévisibilité)
     -->
</body>
</html>