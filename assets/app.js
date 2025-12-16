import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

// Import Bootstrap CSS
import 'bootstrap/dist/css/bootstrap.min.css';

// Import Bootstrap JavaScript
import 'bootstrap';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

// Fonction pour convertir les timestamps en heure locale
function convertTimestampsToLocal() {
    const timestamps = document.querySelectorAll('.timestamp');

    timestamps.forEach(element => {
        const timestamp = element.getAttribute('data-timestamp');

        if (timestamp && timestamp !== 'now') {
            // Convertir le timestamp Unix en date JavaScript (multiplier par 1000 car JS utilise les millisecondes)
            const date = new Date(timestamp * 1000);

            // Formater la date en heure locale
            const options = {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            };

            const localTime = date.toLocaleDateString('fr-FR', options).replace(',', '');

            // Mettre à jour l'affichage
            const timeDisplay = element.querySelector('.time-display');
            if (timeDisplay) {
                timeDisplay.textContent = localTime;
            }
        }
    });
}

// Convertir les timestamps au chargement de la page
document.addEventListener('DOMContentLoaded', convertTimestampsToLocal);

// Convertir les timestamps pour les nouveaux messages (si utilisation de Turbo/Stimulus)
document.addEventListener('turbo:load', convertTimestampsToLocal);

// Exposer la fonction globalement pour pouvoir l'utiliser avec les nouveaux messages via Mercure
window.convertTimestampsToLocal = convertTimestampsToLocal;

