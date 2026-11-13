# Documentation Technique — Vite & Gourmand

<div style="margin-bottom: 28px;">

**Projet :** Application Web de Restauration Traiteur Événementielle  
**Certification :** Titre Professionnel Développeur Web et Web Mobile (DWWM) — Niveau 5  
**Auteur :** Angelo Goncalves  
**Dépôt GitHub :** https://github.com/berzerk06fr/Vite---Gourmand  
**Environnement de Production :** https://vite-et-gourmand-api.alwaysdata.net  

</div>

<hr style="margin: 36px 0;" />

<section style="margin-bottom: 42px;">

## 1. Présentation et Architecture Générale

<div style="margin-left: 24px;">

### 1.1 Contexte et objectifs techniques
Vite & Gourmand est une application web que j'ai développée pour digitaliser l'activité d'un traiteur événementiel basé à Bordeaux. Pour concevoir l'architecture technique du projet, je me suis fixé trois priorités :
- Proposer une navigation fluide et réactive (SPA) permettant aux clients de parcourir facilement les menus saisonniers et de passer commande sans rechargement de page.
- Garantir la fiabilité des données pour tout ce qui touche aux comptes utilisateurs, au calcul des tarifs et au suivi précis des commandes.
- Faciliter le suivi de l'activité pour l'administrateur grâce à un espace dédié lui permettant de consulter rapidement son chiffre d'affaires et ses ventes par menu.

<div style="margin-top: 24px;"></div>

### 1.2 Architecture 3-tiers découplée et persistance hybride
J'ai structuré l'application autour d'une architecture en trois couches bien distinctes : l'interface utilisateur (front-end), le serveur qui gère la logique métier via une API REST (back-end), et deux bases de données complémentaires (une relationnelle et une NoSQL) :

<pre style="display: inline-block; padding: 14px 18px; border-radius: 6px; margin: 12px 0;">
+-------------------------------------------------------------------+
|                        CLIENT (Navigateur)                        |
|          Application Single Page (SPA) — Angular 21.2             |
|     Standalone Components • Signals • Guards • HTTP Interceptor   |
+---------------------------------+---------------------------------+
                                  |
                         Requêtes HTTPS / JSON
                        (Authentification JWT)
                                  |
                                  v
+-------------------------------------------------------------------+
|                     SERVEUR APPLICATIF (API)                      |
|               API REST — Symfony 5.4 LTS / PHP 8.4                |
|     Contrôleurs REST • Sécurité RBAC • LexikJWT • Doctrine ORM    |
+----------------+---------------------------------+----------------+
                 |                                 |
        Doctrine ORM (PDO)                 mongodb/mongodb (^2.5)
                 |                                 |
                 v                                 v
+---------------------------------+ +-------------------------------+
|    BASE RELATIONNELLE (SQL)     | |     BASE DÉCISIONNELLE        |
|          MySQL 8 / MariaDB      | |          MongoDB 6.0          |
|  Utilisateurs, Menus, Plats,    | |  Agrégations CA, Volumes de   |
|  Commandes, Avis, Horaires      | |  commandes par menu/période   |
+---------------------------------+ +-------------------------------+
</pre>

</div>
</section>

<hr style="margin: 36px 0;" />

<section style="margin-bottom: 42px;">

## 2. Environnement de Développement et Justifications Technologiques

<div style="margin-left: 24px;">

### 2.1 Pile technique et justifications des choix

| Couche | Technologie retenue | Justification technique |
| :--- | :--- | :--- |
| **Front-end** | **Angular 21.2 (TypeScript 5.9, RxJS 7.8)** | J'ai choisi Angular pour sa structure modulaire et le typage strict de TypeScript qui m'a permis d'éviter pas mal d'erreurs dès le développement. Il facilite aussi la gestion dynamique des filtres de menus et la sécurisation des pages grâce aux Guards. |
| **Style & UI** | **Bootstrap 5.3.8 & Bootstrap Icons 1.13** | Permet de créer rapidement une interface responsive (adaptée mobile et ordinateur), accessible (respect des contrastes RGAA >= 4.5:1) et de réaliser mes graphiques directement en CSS/Bootstrap sans alourdir le projet avec d'autres bibliothèques. |
| **Back-end** | **Symfony 5.4 LTS (PHP 8.4)** | Framework PHP robuste en version LTS, idéal pour bâtir une API REST sécurisée. Ses bundles natifs et communautaires m'ont permis de gérer facilement l'authentification JWT (LexikJWT), le CORS (Nelmio), l'envoi d'e-mails (Mailer) et la base de données (Doctrine ORM). |
| **BDD Relationnelle** | **MySQL / MariaDB** | J'ai choisi MySQL pour stocker les données principales (comptes, menus, commandes) car sa structure relationnelle garantit leur cohérence (propriétés **ACID**), sécurise les liens entre les tables et évite toute erreur lors du suivi des commandes. |
| **BDD NoSQL** | **MongoDB 6.0 (mongodb/mongodb ^2.5)** | J'ai utilisé MongoDB pour stocker les données statistiques sous forme de documents JSON : cela permet d'afficher rapidement le chiffre d'affaires par menu et par période côté administrateur sans surcharger MySQL. |
| **Tests & Qualité** | **Vitest 4.0 (jsdom) & Prettier 3.8** | J'utilise Vitest pour valider rapidement le bon fonctionnement de mon code front-end et Prettier pour m'assurer que le formatage du code reste propre et homogène. |
| **Conteneurisation** | **Docker & Docker Compose** | J'utilise Docker et Docker Compose pour lancer l'environnement NoSQL (MongoDB et Mongo Express) en un clin d'œil sur ma machine : cela m'évite les problèmes d'installation et garantit un environnement identique en local. |
| **Hébergement** | **Alwaysdata (PaaS)** | J'ai choisi Alwaysdata pour mettre l'application en ligne car il gère parfaitement PHP 8.4, MySQL, l'hébergement du front-end Angular et la sécurisation HTTPS. |

<div style="margin-top: 24px;"></div>

### 2.2 Gestion de versions et Workflow Git
Pour gérer mon code source, j'utilise un workflow Git Flow basé sur des branches claires et des commits conventionnels (feat:, fix:, docs:, security:, chore:) :
- **main** : Ma branche de production, stable et connectée à l'environnement Alwaysdata.
- **develop** : Ma branche d'intégration pour regrouper et tester les fonctionnalités avant la mise en ligne.
- **feature/** : Des branches temporaires par fonctionnalité (ex. feature/backend-setup, feature/frontend-setup, feature/commande, feature/statistiques).

</div>
</section>

<hr style="margin: 36px 0;" />

<section style="margin-bottom: 42px;">

## 3. Conception de la Base de Données

<div style="margin-left: 24px;">

### 3.1 Modèle Conceptuel et Logique (MySQL / MariaDB)
J'ai structuré la base relationnelle (vite-et-gourmand-api_db) autour de **5 entités principales** gérées via Doctrine ORM (src/Entity/) et reliées par des contraintes d'intégrité référentielle :
- **Utilisateur** (utilisateur) : Je centralise ici la gestion des comptes (clients, employés et administrateur) avec les champs id, email **(UNIQUE)**, password haché en bcrypt, roles au format JSON, nom, prénom, téléphone, adresse et est_actif.
- **Menu** (menu) : Il correspond au catalogue des prestations traiteur (id, titre, description, thème, régime, nb_personnes_min, prix_par_personne, stock_disponible, conditions_commande, image_url).
- **Plat** (plat) : Il définit les compositions culinaires (id, nom, description, catégorie [entrée/plat/dessert] et allergènes).
- **Table de jointure menu_plat** : Une association **Many-to-Many** que j'utilise pour lier chaque menu à ses différents plats et inversement.
- **Commande** (commande) : Elle enregistre les transactions clients avec toutes les informations nécessaires (id, clés étrangères utilisateur_id et menu_id, nb_personnes, date_prestation, heure_livraison, adresse_livraison, distance_km, sous_total_menus, frais_livraison, remise_appliquee, prix_total, statut, pret_materiel et created_at).
- **Avis** (avis) : Il recueille les retours clients soumis à modération (id, commande_id **FK UNIQUE**, utilisateur_id FK, note de 1 à 5, commentaire, est_valide et created_at).

*(Les diagrammes MCD et MLD détaillés sont disponibles dans le répertoire Documents/diagrammes/.)*

<div style="margin-top: 24px;"></div>

### 3.2 Modèle Documentaire Décisionnel (MongoDB 6.0)
Afin de ne pas surcharger ma base MySQL avec des calculs d'agrégation lourds, j'enregistre les ventes consolidées sous forme de documents JSON dans MongoDB : cela permet d'alimenter le tableau de bord analytique de l'administrateur (chiffre d'affaires et volumes par menu et par période) de manière beaucoup plus réactive.

<pre style="display: inline-block; padding: 14px 18px; border-radius: 6px; margin: 12px 0;">
{
  "_id": "ObjectId('6734a1f9e4b01a2b3c4d5e6f')",
  "commandeId": 42,
  "menuId": 3,
  "menuTitre": "Menu Gastronomique Automne",
  "datePrestation": "2026-11-15",
  "nbPersonnes": 12,
  "chiffreAffaires": 456.00,
  "statut": "terminee"
}
</pre>

</div>
</section>

<hr style="margin: 36px 0;" />

<section style="margin-bottom: 42px;">

## 4. Architecture Applicative, Règles Métiers et API REST

<div style="margin-left: 24px;">

### 4.1 Organisation des répertoires

<pre style="display: inline-block; padding: 14px 18px; border-radius: 6px; margin: 12px 0;">
Vite---Gourmand/
|-- Documents/                  # Livrables ECF (Documentation technique, diagrammes, dossier ECF)
|-- database/                   # Scripts SQL de structure et jeux d'essai
|-- vite-et-gourmand-back/      # API REST Symfony 5.4 (Controller, Entity, Repository, Service)
|-- vite-et-gourmand-frontend/  # Application Angular 21.2 (components, services, guards, models)
|-- docker-compose.yml          # Orchestration locale MongoDB + Mongo Express
+-- README.md                   # Guide d'installation et identifiants de démonstration
</pre>

<div style="margin-top: 24px;"></div>

### 4.2 Contrôleurs REST et Matrice des Rôles (RBAC)

| Contrôleur Symfony | Périmètre fonctionnel | Rôles autorisés |
| :--- | :--- | :--- |
| **SecurityController / AuthController** | Je gère ici la connexion JWT (/api/login_check), l'inscription des clients, la consultation du profil et la réinitialisation des mots de passe. | Public / Authentifié |
| **MenuController** | Permet la consultation publique avec filtres dynamiques et prend en charge le CRUD complet des menus, plats et allergènes. | Public (GET) / ROLE_EMPLOYE, ROLE_ADMIN (CUD) |
| **CommandeController** | Gère le tunnel de commande client, le calcul des tarifs, l'annulation avant acceptation et le suivi des étapes de préparation jusqu'à la livraison. | ROLE_USER (mes commandes) / ROLE_EMPLOYE, ROLE_ADMIN (toutes) |
| **AvisController** | Permet le dépôt d'avis après une prestation terminée, leur modération (validation/refus) et l'affichage public des retours validés. | Public (GET) / ROLE_USER (POST) / ROLE_EMPLOYE (PATCH) |
| **AdminEmployerController** | Réservé à la création et à la désactivation des comptes employés. | ROLE_ADMIN |
| **AdminDashboardController & StatistiqueController** | Assurent la synchronisation et la consultation des agrégations NoSQL MongoDB (chiffre d'affaires global, par menu et par période). | ROLE_ADMIN |

<div style="margin-top: 24px;"></div>

### 4.3 Règles de gestion implémentées
- **Tarification dynamique des commandes** :
  - J'applique une remise automatique de 10 % sur le sous-total des menus dès que le nombre de personnes atteint ou dépasse nb_personnes_min + 5.
  - Les frais de livraison sont fixés à 5,00 € dans Bordeaux intra-muros, auxquels s'ajoute une majoration de 0,59 € / km parcouru hors de Bordeaux.
- **Cycle de vie et matériel** : Lorsqu'un prêt de matériel est demandé (pret_materiel = true), la commande transite obligatoirement par l'état attente_retour_materiel avant d'être clôturée en terminee, ce qui déclenche automatiquement l'envoi d'un e-mail de notification au client via Symfony Mailer.

</div>
</section>

<hr style="margin: 36px 0;" />

<section style="margin-bottom: 42px;">

## 5. Sécurité Applicative (Conformité OWASP & RGPD)

<div style="margin-left: 24px;">

### 5.1 Mesures techniques de sécurisation

| Vulnérabilité / Exigence | Mesure technique implémentée |
| :--- | :--- |
| **Authentification & Sessions** | J'ai mis en place une architecture stateless basée sur des jetons **JWT** signés de manière asymétrique via LexikJWTAuthenticationBundle : ils transitent par l'en-tête HTTP Authorization: Bearer <token> et sont injectés automatiquement par un intercepteur Angular. |
| **Stockage des mots de passe** | Je sécurise les mots de passe grâce au hachage irréversible **bcrypt** (cost = 13) natif de Symfony Security, avec une politique de complexité stricte (10 caractères minimum, majuscules, minuscules, chiffres et caractères spéciaux). |
| **Contrôle d'accès (Broken Access Control)** | Je sécurise l'application avec un double verrouillage : des **Guards Angular** côté front-end et une vérification rigoureuse des rôles (ROLE_USER, ROLE_EMPLOYE, ROLE_ADMIN) associée à un contrôle de propriété dans chaque contrôleur API. |
| **Injections SQL & NoSQL** | J'utilise exclusivement **Doctrine ORM** (avec des requêtes préparées et paramétrées) pour MySQL et je caste strictement les paramètres pour les agrégations MongoDB afin de bloquer toute injection. |
| **Failles XSS (Cross-Site Scripting)** | Je m'appuie sur l'échappement automatique des templates Angular, une validation rigoureuse des données entrantes dans l'API Symfony et des en-têtes HTTP sécurisés. |
| **Configuration CORS & Secrets** | Restriction des origines autorisées via **NelmioCorsBundle**. Exclusion stricte des fichiers .env.local et clés cryptographiques JWT (*.pem) du dépôt Git via .gitignore. |
| **Conformité RGPD** | J'intègre le consentement explicite à l'inscription, la minimisation des données collectées, un espace client pour consulter ou modifier ses informations, ainsi que la gestion du droit à l'effacement ou de la désactivation. |

</div>
</section>

<hr style="margin: 36px 0;" />

<section style="margin-bottom: 42px;">

## 6. Procédure de Déploiement en Production (Alwaysdata)

<div style="margin-left: 24px;">

### 6.1 Préparation des artefacts
1. **Côté front-end (Angular) :** Je prépare les fichiers pour la mise en ligne avec la commande de build de production (ng build --configuration production), ce qui produit les bundles minifiés dans dist/.
2. **Côté back-end (Symfony) :** Je nettoie les paquets inutiles en production (composer install --no-dev --optimize-autoloader), je crée mes clés de chiffrement JWT et je purge le cache.

<div style="margin-top: 24px;"></div>

### 6.2 Configuration serveur et bases de données
1. **Côté MySQL (Alwaysdata) :** J'initialise la base **vite-et-gourmand-api_db**, je lance mes migrations Doctrine en automatique, et j'ajoute mes comptes de test.
2. **Côté MongoDB :** Je renseigne la chaîne de connexion **MONGODB_URL** pour la partie décisionnelle.
3. **Fichier .env.local :** Je sécurise les variables de production (APP_ENV=prod, clés secrètes, accès aux bases, passphrase JWT et configuration e-mail).
4. **Sécurité SSL :** Je mets en place le certificat **Let's Encrypt** sur Alwaysdata pour garantir une navigation entièrement chiffrée en HTTPS.

</div>
</section>
