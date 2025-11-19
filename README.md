# mi3cda-chat

## Présentation

mi3cda-chat est une application web de messagerie instantanée développée avec Symfony 6, AssetMapper et Bootstrap. Elle permet aux utilisateurs de s'inscrire, se connecter, démarrer des conversations privées et échanger des messages en temps réel grâce à Mercure.

---

## Fonctionnalités principales

- **Inscription et connexion sécurisées** (formulaires Bootstrap, protection CSRF)
- **Liste des utilisateurs** et démarrage de nouvelles conversations
- **Dashboard de conversations** : affichage des conversations existantes, navigation fluide
- **Interface de chat moderne** : bulles de messages stylées, alignement selon l'expéditeur, auto-scroll
- **Envoi de messages en temps réel** grâce à Mercure et Turbo Stream
- **Responsive design** : compatible mobile, tablette et desktop

---

## Structure des dossiers

- `src/` : Code PHP (contrôleurs, entités, formulaires, services)
- `templates/` : Templates Twig pour l'interface utilisateur
- `assets/` : Fichiers JS/CSS, configuration AssetMapper
- `public/` : Point d'entrée de l'application
- `config/` : Configuration Symfony et Mercure

---

## Démarrage rapide

1. **Installer les dépendances**
   ```bash
   composer install
   php bin/console importmap:install
   ```
2. **Configurer la base de données**
   - Modifier `.env` avec vos identifiants
   - Créer la base :
     ```bash
     php bin/console doctrine:database:create
     php bin/console doctrine:migrations:migrate
     ```
3. **Lancer le serveur Symfony**
   ```bash
   symfony serve
   ```
4. **Accéder à l'application**
   - Ouvrir [http://127.0.0.1:8000](http://localhost:8000)

---

## Utilisation

- **Inscription** : Créez un compte via le formulaire d'inscription
- **Connexion** : Accédez à votre espace personnel
- **Conversations** : Démarrez une nouvelle conversation ou continuez une existante
- **Chat** : Envoyez et recevez des messages en temps réel

---

## Technologies utilisées

- Symfony 6
- AssetMapper
- Bootstrap 5
- Mercure
- Twig
- Doctrine ORM

---

## Personnalisation

- Les styles sont modifiables dans `assets/styles/` et les templates dans `templates/`
- Les composants Twig permettent de personnaliser l'affichage des messages
- Les routes et contrôleurs sont dans `src/Controller/`

---

## Aide & Contribution

Pour toute question ou suggestion, ouvrez une issue ou contactez le mainteneur du projet.

---

## Licence

Ce projet est open-source, sous licence MIT.

