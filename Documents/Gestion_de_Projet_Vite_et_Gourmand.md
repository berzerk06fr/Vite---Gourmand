# Gestion de Projet — Vite & Gourmand

<div style="margin-bottom: 28px;">

**Projet :** Application Web de Restauration Traiteur Événementielle  
**Certification :** Titre Professionnel Développeur Web et Web Mobile (DWWM) — Niveau 5  
**Auteur :** Angelo Goncalves  
**Dépôt GitHub :** https://github.com/berzerk06fr/Vite---Gourmand  
**Environnement de Production :** https://vite-et-gourmand-api.alwaysdata.net  

</div>

<hr style="margin: 36px 0;" />

<section style="margin-bottom: 42px;">

## 1. Méthodologie et Organisation Générale

Pour mener à bien le développement de l'application *Vite & Gourmand*, j'ai adopté une approche inspirée des méthodologies agiles (type Scrum/Kanban), adaptée à un format de projet individuel. L'objectif était de structurer mon travail en cycles itératifs pour valider chaque brique fonctionnelle et technique étape par étape, tout en gardant une vision claire des livrables de la certification DWWM.

### Outils de suivi et de pilotage
- **Trello (Tableau Kanban)** : J'ai centralisé la gestion de mes tâches sous forme de colonnes (*À faire*, *En cours*, *À tester*, *Terminé*). Chaque fonctionnalité a été découpée en *User Stories* ou tâches unitaires pour éviter l'effet tunnel et suivre l'avancement global en temps réel.

</section>

<hr style="margin: 36px 0;" />

<section style="margin-bottom: 42px;">

## 2. Planification et Découpage en Étapes Clés

J'ai structuré le projet en grandes phases chronologiques, de la conception initiale jusqu'au déploiement et à la centralisation sur le dépôt :

1. **Phase de Maquettage et Cadrage (Étape 1)** :
   - Rédaction du cahier des charges et de l'expression des besoins.
   - Création des wireframes et des maquettes graphiques de l'interface utilisateur.
2. **Phase de Base de Données Relationnelle (Étape 2)** :
   - Modélisation conceptuelle et logique (MCD, MLD).
   - Création de la base MySQL / MariaDB (vite-et-gourmand-api_db) et rédaction des scripts SQL de structure et d'insertion.
3. **Phase de Développement du Back-end (Étape 3)** :
   - Initialisation de l'API REST avec Symfony 5.4 LTS et PHP 8.4.
   - Mise en place de l'authentification sécurisée par jetons JWT (LexikJWTAuthenticationBundle).
   - Implémentation des entités, des contrôleurs, des règles métiers (tarification dynamique, gestion du matériel) et de la sécurité RBAC.
4. **Phase de Développement du Front-end (Étape 4)** :
   - Initialisation de l'application Angular 21.2 en composants autonomes (*Standalone Components*).
   - Intégration de l'interface responsive avec Bootstrap 5.3.8.
   - Connexion à l'API via des services Angular, gestion des états et sécurisation des routes par des *Guards*.
5. **Phase Décisionnelle NoSQL (Étape 5)** :
   - Configuration et conteneurisation de la base MongoDB via Docker Compose pour alimenter les tableaux de bord analytiques et le suivi des ventes.
6. **Phase de Déploiement en Ligne (Étape 6)** :
   - Compilation des artefacts de production (build Angular et optimisation Symfony).
   - Déploiement sur le serveur PaaS Alwaysdata, configuration du domaine et activation du certificat SSL/HTTPS.
7. **Phase de Versioning et Finalisation sur GitHub (Étape 7)** :
   - Centralisation de l'ensemble du code source, structuration du workflow Git Flow (main, develop, feature/) et validation progressive des modifications.

</section>

<hr style="margin: 36px 0;" />

<section style="margin-bottom: 42px;">

## 3. Versioning et Gestion du Dépôt

Pour garantir la traçabilité de mon code et sécuriser les différentes versions de l'application, j'ai mis en place un workflow **Git Flow** hébergé sur GitHub :

- **Stratégie de branches** :
  - **main** : Ma branche principale de production, stable et connectée au serveur de déploiement Alwaysdata.
  - **develop** : Ma branche d'intégration principale, utilisée pour assembler et tester l'ensemble des modules avant la mise en production.
  - **feature/** : Des branches de travail dédiées à chaque grande fonctionnalité (back-end, front-end, système de commande, modules analytiques).
- **Gestion des commits** : J'ai rédigé mes messages de commits au fil de l'eau pour décrire précisément chaque action réalisée (comme la mise en place de l'API, la sécurisation des tokens JWT, la gestion des rôles ou les ajustements de design), ce qui m'a permis de garder un historique propre et compréhensible de l'évolution du projet.

</div>
</section>
