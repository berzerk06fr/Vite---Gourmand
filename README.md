# Vite & Gourmand — Application Web Traiteur Événementiel

Application web full-stack réalisée dans le cadre de l'Évaluation en Cours de Formation (**ECF — Titre Professionnel Développeur Web et Web Mobile**).  
Elle permet de digitaliser l'activité du traiteur bordelais **Vite & Gourmand** (fondé par Julie et José) : consultation des menus filtrables, tunnel de commande avec règles métier, suivi des commandes en temps réel, espaces dédiés (Client, Employé, Administrateur) et module décisionnel NoSQL.

## Accès direct

[![Production Alwaysdata](https://img.shields.io/badge/Production-Alwaysdata-0d6efd?style=for-the-badge&logo=google-chrome&logoColor=white)](https://vite-et-gourmand-api.alwaysdata.net)
[![Tableau Trello](https://img.shields.io/badge/Kanban-Trello_ECF-0079BF?style=for-the-badge&logo=trello&logoColor=white)](https://trello.com/b/uiLGk9TD/ecf-vite-gourmand)

## Stack Technique

- **Front-end** : Angular (Standalone Components, Signals, Reactive Forms, Chart.js, TypeScript, Bootstrap 5 / Responsive & RGAA)
- **Back-end (API REST)** : PHP 8.x / Symfony 7 (Architecture MVC, Sécurité par rôles, Mailer)
- **Base de données relationnelle (SQL)** : MySQL / MariaDB (Utilisateurs, menus, plats, allergènes, commandes, avis, horaires)
- **Base de données NoSQL** : MongoDB 6.0 + Mongo Express (Statistiques décisionnelles : agrégation du CA et comparatif des volumes par menu/période, avec bascule automatique sur MySQL si la collection est vide)
- **Conteneurisation & Hébergement** : Docker / Docker Compose (local) & Alwaysdata (production)

## Architecture du Dépôt

```text
Vite---Gourmand/
├── database/                      # Scripts de bases de données
│   ├── schema.sql                 # Structure relationnelle MySQL (DDL)
│   ├── data.sql                   # Jeu de données de démonstration (DML)
│   └── seed.mjs                   # Script d'initialisation NoSQL (MongoDB)
├── Documents/                     # Livrables ECF (UML, Charte graphique RGAA, Maquettes, Documentation, Manuel d'utilisation)
├── vite-et-gourmand-back/         # API REST Symfony 7
│   ├── config/                    # Configuration des bundles, sécurité et CORS
│   ├── src/
│   │   ├── Controller/            # Endpoints API REST (Auth, Menus, Commandes, Avis, Admin, Stats)
│   │   ├── Entity/                # Modèles relationnels Doctrine (MySQL)
│   │   ├── Repository/            # Requêtes et accès aux données
│   │   └── Service/               # Logique métier (tarification, remises, livraison, NoSQL, Mailer)
│   └── .env                       # Variables d'environnement par défaut
├── vite-et-gourmand-frontend/     # Application Single Page Angular
│   └── src/app/
│       ├── core/                  # Services API, intercepteurs JWT et guards par rôle
│       ├── features/              # Pages et parcours métiers (Catalogue, Commande, Client, Employé, Admin)
│       └── shared/                # Composants réutilisables (Header, Footer dynamique, modales)
├── docker-compose.yml             # Conteneurs MongoDB 6.0 & Mongo Express
└── README.md                      # Documentation d'installation et d'utilisation
```

## Installation et Lancement en Local

### 1. Cloner le dépôt

```bash
git clone https://github.com/berzerk06fr/Vite---Gourmand.git
cd Vite---Gourmand
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
```

Renseigner `DATABASE_URL` (MySQL/MariaDB) et `MONGODB_URL=mongodb://127.0.0.1:27017` dans `.env.local`, puis initialiser la base :

```bash
# Création de la base de données MySQL
php bin/console doctrine:database:create

# Option 1 : Via les migrations Doctrine
php bin/console doctrine:migrations:migrate

# Option 2 : Import direct de la structure et du jeu de données de démonstration
mysql -u root -p vite_et_gourmand < ../database/schema.sql
mysql -u root -p vite_et_gourmand < ../database/data.sql

# Démarrer le serveur de développement Symfony
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
## Comptes de Démonstration (Environnement de Test)

| Rôle | Identifiant (Email) | Mot de passe | Périmètre d'accès |
| :--- | :--- | :--- | :--- |
| **Administrateur (José)** | `jose@vite-et-gourmand.fr` | `Admin123!` | Gestion des employés, statistiques décisionnelles NoSQL (CA & commandes), gestion complète |
| **Employé** | `employe@employe.fr` | `Employe123!` | Gestion des menus, plats, horaires, suivi des commandes et modération des avis |
| **Client** | `client@client.fr` | `Client123!` | Passage de commande, suivi en temps réel, modification/annulation et dépôt d'avis |