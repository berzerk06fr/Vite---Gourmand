# Vite & Gourmand — Application Web Traiteur Événementiel

Application web full-stack réalisée dans le cadre de l'Évaluation en Cours de Formation (**ECF — Titre Professionnel Développeur Web et Web Mobile**).  
Elle permet de digitaliser l'activité du traiteur bordelais **Vite & Gourmand** (fondé par Julie et José) : consultation des menus filtrables, tunnel de commande avec règles métier, suivi des commandes en temps réel, espaces dédiés (Client, Employé, Administrateur) et module décisionnel NoSQL.

## Accès direct

[![Production Alwaysdata](https://img.shields.io/badge/Production-Alwaysdata-0d6efd?style=for-the-badge&logo=google-chrome&logoColor=white)](https://vite-et-gourmand-api.alwaysdata.net)
[![Tableau Trello](https://img.shields.io/badge/Kanban-Trello_ECF-0079BF?style=for-the-badge&logo=trello&logoColor=white)](https://trello.com/b/uiLGk9TD/ecf-vite-gourmand)

## Stack Technique

- **Front-end** : Angular (Standalone Components, Signals, Reactive Forms, TypeScript, CSS3 / Responsive & RGAA)
- **Back-end (API REST)** : PHP 8.x / Symfony 7 (Architecture MVC, Sécurité par rôles, Mailer)
- **Base de données relationnelle (SQL)** : MySQL / MariaDB (Utilisateurs, menus, plats, allergènes, commandes, avis, horaires)
- **Base de données NoSQL** : MongoDB 6.0 + Mongo Express (Statistiques décisionnelles : CA et volume de commandes par menu et par période)
- **Conteneurisation & Hébergement** : Docker / Docker Compose (local) & Alwaysdata (production)

## Architecture du Dépôt

```text
Vite et Gourmand/
├── database/                  # Scripts SQL (DDL/DML) et seed NoSQL (seed.mjs)
├── Documents/                 # Livrables ECF (UML, Charte graphique, Maquettes, Documentation)
├── vite-et-gourmand-back/     # API REST Symfony
├── vite-et-gourmand-frontend/ # Application client Angular
├── docker-compose.yml         # Conteneurs MongoDB 6.0 & Mongo Express
└── README.md                  # Documentation d'installation et d'utilisation
```

## Installation et Lancement en Local

### 1. Cloner le dépôt

```bash
git clone [https://github.com/berzerk06fr/Vite---Gourmand.git](https://github.com/berzerk06fr/Vite---Gourmand.git)
cd "Vite et Gourmand"
```

### 2. Démarrer MongoDB via Docker

```bash
docker-compose up -d
node database/seed.mjs
```

- **MongoDB** : `localhost:27017` (base `vite_et_gourmand_analytics`)
- **Interface Mongo Express** : http://localhost:8081

### 3. Configurer et lancer le Back-end (Symfony)

```bash
cd vite-et-gourmand-back
composer install
cp .env .env.local
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php -S 127.0.0.1:8000 -t public
```

### 4. Configurer et lancer le Front-end (Angular)

```bash
cd vite-et-gourmand-frontend
npm install
npm start
```

Application accessible sur http://localhost:4200.

## Workflow Git (Git Flow)

- `main` : version stable alignée avec la mise en production sur Alwaysdata.
- `develop` : branche d'intégration regroupant les fonctionnalités validées.
- `feature/*` : branches dédiées par fonctionnalité (`feature/sql-schema`, `feature/backend-setup`, `feature/frontend-setup`, `feature/order-flow`, `feature/stock-management`, `feature/client-orders`, `feature/client-reviews`, `feature/admin-dashboard-filters`, `feature/admin-order-status-mailer`, `feature/statistiques-nosql`).

## Fonctionnalités Principales & Règles Métier

- **Catalogue dynamique** : filtrage instantané sans rechargement par prix maximum, fourchette de prix, thème, régime alimentaire et nombre minimum de convives.
- **Calcul tarifaire automatisé** :
  - Remise de **10 %** appliquée automatiquement dès que le nombre de convives dépasse d'au moins **5 personnes** le minimum requis du menu.
  - Frais de livraison fixés à **5,00 €** sur Bordeaux, majorés de **0,59 €/km** pour toute livraison hors Bordeaux.
  - Décrémentation automatique du stock disponible lors de la validation de la commande et découplage clair entre sous-total menus, frais de livraison et total TTC.
- **Suivi de commande & Matériel** : workflow complet des statuts horodatés, annulation client autorisée tant que la commande n'est pas acceptée, alerte e-mail de restitution du matériel sous 10 jours (caution de 600 €).
- **Espaces Métiers** :
  - **Client** : historique des commandes, bon récapitulatif imprimable, modification/annulation et dépôt d'avis noté après prestation.
  - **Employé** : gestion CRUD des menus, plats, horaires, traitement des statuts de commandes et modération des avis.
  - **Administrateur (José)** : création/désactivation des comptes employés et tableau de bord décisionnel MongoDB (graphiques et chiffre d'affaires par menu/période).