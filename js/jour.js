/**
 * @file jour.js
 * @description Gère la logique de la page jour.html pour la saisie des données quotidiennes.
 */

/**
 * @var {URLSearchParams} params
 * @description Récupère les paramètres de l'URL.
 * @role Extraire la date de l'URL pour savoir quel jour est affiché.
 * @interest L'utilisation de `URLSearchParams` est une manière moderne et robuste de traiter les paramètres GET dans une URL, rendant le code plus lisible et maintenable.
 */
const params = new URLSearchParams(window.location.search);

/**
 * @var {string} date
 * @description La date du jour actuel, extraite de l'URL.
 */
const date = params.get('date');

/**
 * @var {string} utilisateur
 * @description L'utilisateur actuellement connecté.
 * @role Identifier l'utilisateur pour charger et sauvegarder ses données personnelles.
 * @interest L'utilisation de `localStorage` pour stocker l'utilisateur actif permet de maintenir la session utilisateur entre les pages sans avoir besoin de requêtes serveur constantes.
 */
const utilisateur = localStorage.getItem('utilisateurActif') || 'invité';

/**
 * @var {object} donnees
 * @description Objet contenant toutes les données de l'utilisateur, chargé depuis le localStorage.
 * @role Stocker localement les données de l'application pour un accès rapide et une persistance entre les sessions.
 * @interest Le `localStorage` est idéal pour stocker des données non sensibles côté client. L'opérateur `|| {}` assure que si aucune donnée n'existe, on part d'un objet vide pour éviter les erreurs.
 */
let donnees = JSON.parse(localStorage.getItem(utilisateur)) || {};
if (!donnees[date]) {
    donnees[date] = { traitements: [], crises: [], douleurs: [], soin: false };
}

/**
 * @function sauvegarderTout
 * @description Récupère toutes les données des formulaires de la page, les sauvegarde dans l'objet `donnees` puis dans le localStorage.
 * @role Action principale du bouton "Enregistrer tout". Assure la persistance de toutes les informations saisies par l'utilisateur.
 * @interest Cette fonction centralise la sauvegarde, rendant la gestion des données cohérente. Elle utilise `querySelectorAll` et `map` pour collecter efficacement les données depuis le DOM, une pratique moderne et performante.
 */
function sauvegarderTout() {
    // Sauvegarde des traitements
    const traitementsSaisis = Array.from(document.querySelectorAll('#liste-traitements .item-saisie')).map(div => ({
        nom: div.querySelector('select').value,
        quantite: parseInt(div.querySelector('input[type="number"]').value) || 0
    })).filter(t => t.nom);
    donnees[date].traitements = traitementsSaisis;

    // Sauvegarde des crises
    const crisesSaisies = Array.from(document.querySelectorAll('#liste-crises .item-saisie')).map(div => ({
        heure: div.querySelector('input[type="time"]').value,
        intensite: parseInt(div.querySelector('input[type="number"]').value) || 1
    })).filter(c => c.heure);
    donnees[date].crises = crisesSaisies;

    // Sauvegarde des douleurs
    const douleursSaisies = Array.from(document.querySelectorAll('#liste-douleurs .item-saisie')).map(div => ({
        heure: div.querySelector('input[type="time"]').value
    })).filter(d => d.heure);
    donnees[date].douleurs = douleursSaisies;
    
    // Sauvegarde du soin
    donnees[date].soin = document.getElementById('soin-effectue').checked;

    localStorage.setItem(utilisateur, JSON.stringify(donnees));
    alert('Toutes les données ont été enregistrées !');
}

/**
 * @function chargerDonneesDansFormulaires
 * @description Vide les listes et les remplit avec des formulaires pré-remplis à partir des données sauvegardées.
 * @role Afficher les données existantes au chargement de la page et permettre leur modification. Sert aussi à annuler les modifications en rechargeant l'état initial.
 * @interest Au lieu de simplement afficher du texte, cette fonction génère des champs de formulaire. Cela permet à l'utilisateur de voir et de modifier directement les données existantes, améliorant l'ergonomie.
 */
function chargerDonneesDansFormulaires() {
    // Vider les listes existantes
    document.getElementById('liste-traitements').innerHTML = '';
    document.getElementById('liste-crises').innerHTML = '';
    document.getElementById('liste-douleurs').innerHTML = '';

    // Charger les traitements
    if (donnees[date].traitements.length > 0) {
        donnees[date].traitements.forEach(traitement => ajouterTraitement(traitement));
    } 
    
    // Charger les crises
    if (donnees[date].crises.length > 0) {
        donnees[date].crises.forEach(crise => ajouterCrise(crise));
    }

    // Charger les douleurs
    if (donnees[date].douleurs.length > 0) {
        donnees[date].douleurs.forEach(douleur => ajouterDouleur(douleur));
    }

    // Charger l'état du soin
    document.getElementById('soin-effectue').checked = donnees[date].soin;
}


/**
 * @function ajouterTraitement
 * @param {object} [traitement] - Un objet traitement existant pour pré-remplir le formulaire.
 * @description Ajoute une ligne de formulaire pour un traitement, vide ou pré-remplie.
 * @role Permettre à l'utilisateur d'ajouter de nouveaux traitements ou de voir/modifier ceux qui existent déjà.
 * @interest La fonction est maintenant plus flexible : elle peut créer un formulaire vide ou le remplir avec des données existantes, ce qui la rend réutilisable pour le chargement initial et l'ajout manuel.
 */
function ajouterTraitement(traitement) {
    const donneesUtilisateur = JSON.parse(localStorage.getItem(utilisateur)) || {};
    const traitementsHabituels = donneesUtilisateur.traitementsHabituels || [];

    const div = document.createElement('div');
    div.className = 'item-saisie';

    const select = document.createElement('select');
    const optionDefaut = document.createElement('option');
    optionDefaut.value = '';
    optionDefaut.textContent = 'Choisir un traitement...';
    select.appendChild(optionDefaut);

    traitementsHabituels.forEach(t => {
        const option = document.createElement('option');
        option.value = t;
        option.textContent = t;
        select.appendChild(option);
    });

    const quantite = document.createElement('input');
    quantite.type = 'number';
    quantite.min = 0;
    quantite.placeholder = 'Quant.';

    // Pré-remplissage si un traitement est fourni
    if (traitement) {
        select.value = traitement.nom;
        quantite.value = traitement.quantite;
    }

    div.appendChild(select);
    div.appendChild(quantite);
    document.getElementById('liste-traitements').appendChild(div);
}

/**
 * @function ajouterCrise
 * @param {object} [crise] - Un objet crise existant pour pré-remplir le formulaire.
 * @description Ajoute une ligne de formulaire pour une crise, vide ou pré-remplie.
 * @role Permettre l'ajout ou la modification de crises.
 * @interest Similaire à `ajouterTraitement`, cette fonction est réutilisable pour l'affichage initial et l'ajout de nouvelles données.
 */
function ajouterCrise(crise) {
    const div = document.createElement('div');
    div.className = 'item-saisie';

    const heure = document.createElement('input');
    heure.type = 'time';

    const intensite = document.createElement('input');
    intensite.type = 'number';
    intensite.min = 1;
    intensite.max = 5;
    intensite.placeholder = 'Intensité (1-5)';

    if (crise) {
        heure.value = crise.heure;
        intensite.value = crise.intensite;
    }

    div.appendChild(heure);
    div.appendChild(intensite);
    document.getElementById('liste-crises').appendChild(div);
}

/**
 * @function ajouterDouleur
 * @param {object} [douleur] - Un objet douleur existant pour pré-remplir le formulaire.
 * @description Ajoute une ligne de formulaire pour une douleur, vide ou pré-remplie.
 * @role Permettre l'ajout ou la modification de douleurs.
 * @interest La cohérence dans la conception des fonctions `ajouter...` rend le code plus facile à comprendre et à maintenir.
 */
function ajouterDouleur(douleur) {
    const div = document.createElement('div');
    div.className = 'item-saisie';

    const heure = document.createElement('input');
    heure.type = 'time';

    if (douleur) {
        heure.value = douleur.heure;
    }

    div.appendChild(heure);
    document.getElementById('liste-douleurs').appendChild(div);
}


/**
 * @event DOMContentLoaded
 * @description Point d'entrée du script.
 * @role Initialise la page en chargeant les données et en attachant les écouteurs d'événements aux boutons.
 * @interest `DOMContentLoaded` garantit que le DOM est prêt. L'ajout des écouteurs ici centralise la gestion des événements principaux de la page.
 */
document.addEventListener('DOMContentLoaded', () => {
    // Charger les données existantes dans les formulaires
    chargerDonneesDansFormulaires();

    // Attacher les écouteurs d'événements aux boutons globaux
    document.getElementById('btn-enregistrer-tout').addEventListener('click', sauvegarderTout);
    document.getElementById('btn-annuler-tout').addEventListener('click', chargerDonneesDansFormulaires);
});