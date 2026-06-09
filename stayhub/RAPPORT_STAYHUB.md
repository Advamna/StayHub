# RAPPORT DE PROJET
# Plateforme de Location de Logements — **StayHub**

---

**Établissement :** [Votre École / Université]  
**Formation :** [Votre Filière]  
**Année Académique :** 2025 – 2026  
**Auteur(s) :** [Votre Nom]  
**Encadrant :** [Nom de l'Encadrant]  
**Date de soutenance :** [Date]  

---

## TABLE DES MATIÈRES

1. Introduction Générale
2. Présentation du Projet StayHub
3. Environnement et Technologies Utilisées
4. Architecture Globale du Système
5. Modèle Conceptuel de Données (MCD)
6. Modèle Logique de Données (MLD)
7. Base de Données — Schéma Physique Détaillé
8. Description des Interfaces Utilisateur
   - 8.1 Page d'Accueil (index.php)
   - 8.2 Page de Détail d'une Annonce (listing.php)
   - 8.3 Page d'Inscription / Connexion (Modales)
   - 8.4 Page Devenir Hôte (become-host.php)
   - 8.5 Tableau de Bord Hôte (host-dashboard.php)
   - 8.6 Mes Séjours — Réservations (my-rentals.php)
   - 8.7 Paiement Sécurisé (payment.php)
   - 8.8 Reçu de Paiement (receipt.php)
   - 8.9 Profil Utilisateur (profile.php)
   - 8.10 Panneau d'Administration — Tableau de Bord (admin/index.php)
   - 8.11 Gestion des Utilisateurs (admin/users.php)
   - 8.12 Gestion des Annonces (admin/listings.php)
   - 8.13 Gestion des Annonces Hôte — Mes Annonces (my-listings.php)
   - 8.14 Modification d'une Annonce (edit-listing.php)
9. Description des APIs et Logique Métier
10. Sécurité et Gestion des Sessions
11. Flux Fonctionnels (Diagrammes de Cas d'Utilisation)
12. Conclusion et Perspectives

---

# 1. INTRODUCTION GÉNÉRALE

## 1.1 Contexte

Dans un monde de plus en plus connecté, les plateformes de location de logements entre particuliers ont révolutionné le secteur du tourisme et de l'hébergement. Des acteurs majeurs comme Airbnb, Booking.com ou Vrbo ont démontré qu'il est possible de créer une économie collaborative où les propriétaires peuvent monétiser leur espace inutilisé, tandis que les voyageurs trouvent des alternatives authentiques et souvent moins coûteuses à l'hôtellerie traditionnelle.

Dans ce contexte, le projet **StayHub** naît d'une volonté de concevoir et développer une plateforme web complète de mise en relation entre hôtes (propriétaires de logements) et voyageurs (locataires). Ce projet s'inscrit dans le cadre d'une formation en développement web et vise à mettre en pratique l'ensemble des compétences acquises : conception de bases de données relationnelles, développement back-end en PHP, gestion des sessions et de la sécurité, ainsi que la création d'interfaces utilisateur modernes et responsives.

## 1.2 Problématique

La problématique centrale de ce projet est la suivante : **Comment concevoir et développer une plateforme web fonctionnelle permettant la mise en relation entre hôtes et voyageurs, intégrant un système de réservation complet avec gestion des paiements, des profils utilisateurs et une administration centralisée ?**

Pour répondre à cette problématique, plusieurs sous-questions ont guidé la conception :

- Comment structurer une base de données relationnelle capable de gérer les utilisateurs, les annonces, les réservations et les paiements de manière cohérente ?
- Comment garantir la sécurité des données et des transactions sur la plateforme ?
- Comment offrir une expérience utilisateur fluide et intuitive, inspirée des meilleures pratiques du marché ?
- Comment permettre à un administrateur de superviser et modérer l'ensemble de la plateforme ?

## 1.3 Objectifs du Projet

Les objectifs principaux du projet StayHub sont les suivants :

**Objectifs fonctionnels :**
- Permettre aux utilisateurs de s'inscrire, se connecter et gérer leur profil.
- Permettre aux hôtes de publier des annonces de logements avec photos, description, équipements et tarification.
- Permettre aux voyageurs de parcourir les annonces disponibles, de les filtrer par destination et de réserver en ligne.
- Intégrer un système de paiement simulé avec génération automatique d'un reçu officiel.
- Fournir un panneau d'administration complet pour la gestion des utilisateurs et des annonces.

**Objectifs techniques :**
- Développer une application web en PHP avec une architecture claire et maintenable.
- Concevoir un schéma de base de données normalisé (SQL Server / MySQL).
- Implémenter un système de sessions PHP sécurisé.
- Assurer la compatibilité avec les environnements XAMPP.

## 1.4 Organisation du Rapport

Ce rapport est structuré de manière à suivre la progression naturelle du développement d'un projet informatique : de la conceptualisation à la réalisation. Après une présentation générale du projet et de son environnement technique, nous détaillons la modélisation des données (MCD et MLD), puis nous décrivons exhaustivement chaque interface de la plateforme, les fonctionnalités associées et la logique métier implémentée.

---

# 2. PRÉSENTATION DU PROJET STAYHUB

## 2.1 Description Générale

**StayHub** est une plateforme web de location de logements entre particuliers, développée en PHP et hébergée localement via XAMPP. Elle permet à tout utilisateur enregistré de devenir hôte (propriétaire) et de publier des logements à louer, ou de voyager en réservant des logements proposés par d'autres hôtes.

La plateforme fonctionne selon un modèle **peer-to-peer (P2P)** : il n'y a pas d'intermédiaire physique entre l'hôte et le voyageur. StayHub joue le rôle de facilitateur numérique, assurant la mise en relation, la gestion des réservations et la sécurisation des transactions.

## 2.2 Fonctionnalités Principales

| Fonctionnalité | Description | Rôle concerné |
|---|---|---|
| Inscription / Connexion | Création de compte et authentification | Tous les utilisateurs |
| Parcourir les annonces | Affichage et recherche de logements disponibles | Visiteurs & Utilisateurs |
| Consulter une annonce | Voir les détails, photos, équipements et tarifs | Visiteurs & Utilisateurs |
| Réserver un logement | Formulaire de réservation avec dates et informations | Utilisateurs connectés |
| Payer une réservation | Paiement simulé par carte bancaire | Utilisateurs connectés |
| Consulter ses séjours | Historique et gestion des réservations | Utilisateurs connectés |
| Annuler une réservation | Annulation de réservation en attente | Utilisateurs connectés |
| Voir le reçu | Reçu de paiement imprimable | Utilisateurs connectés |
| Gérer son profil | Modifier nom et photo de profil | Utilisateurs connectés |
| Devenir hôte | Processus d'inscription en tant qu'hôte | Utilisateurs connectés |
| Publier une annonce | Formulaire de création d'annonce avec photos | Hôtes |
| Gérer ses annonces | Consulter, modifier et supprimer ses propres annonces publiées | Hôtes |
| Voir l'historique des locations | Consulter les locataires, durées et revenus par annonce | Hôtes |
| Administration | Gestion globale de la plateforme | Administrateurs |
| Gérer les utilisateurs | Bannir, débannir, supprimer des comptes | Administrateurs |
| Gérer les annonces | Signaler, valider, supprimer des annonces | Administrateurs |

## 2.3 Acteurs de la Plateforme

La plateforme distingue quatre types d'acteurs :

1. **Le Visiteur (non connecté)** : Peut parcourir les annonces et les consulter, mais ne peut pas réserver. Pour toute action de réservation, il est redirigé vers le formulaire de connexion.

2. **L'Utilisateur (connecté)** : Dispose d'un compte actif. Peut réserver des logements, gérer ses séjours, effectuer des paiements et consulter ses reçus. Peut choisir de devenir hôte.

3. **L'Hôte** : Utilisateur ayant accepté les conditions d'hébergement de la plateforme. En plus des fonctionnalités utilisateur, il peut créer et publier des annonces de logements.

4. **L'Administrateur** : Dispose d'un accès complet à l'interface d'administration. Peut gérer l'ensemble des utilisateurs et des annonces, bannir des comptes, signaler des annonces problématiques.

---

# 3. ENVIRONNEMENT ET TECHNOLOGIES UTILISÉES

## 3.1 Stack Technologique

### Côté Serveur (Back-End)

| Technologie | Version / Description |
|---|---|
| **PHP** | Langage principal de développement back-end |
| **SQL Server Express** | Système de gestion de base de données relationnelle |
| **sqlsrv** | Driver PHP natif pour SQL Server (Microsoft) |
| **XAMPP** | Serveur local Apache pour le développement |
| **Sessions PHP** | Gestion de l'authentification et des états utilisateurs |

### Côté Client (Front-End)

| Technologie | Description |
|---|---|
| **HTML5** | Structure des pages web |
| **CSS3** | Mise en forme et styles visuels (Vanilla CSS) |
| **JavaScript (ES6+)** | Interactions dynamiques côté client |
| **Font Awesome 6** | Bibliothèque d'icônes vectorielles |
| **Google Fonts (Inter)** | Police typographique moderne |
| **Unsplash API** | Images de logements (URLs externes) |

### Outils de Développement

| Outil | Usage |
|---|---|
| **Visual Studio Code** | Éditeur de code principal |
| **SSMS (SQL Server Management Studio)** | Gestion et visualisation de la base de données |
| **Git** | Contrôle de version |
| **Navigateur web (Chrome/Firefox)** | Tests et débogage |

## 3.2 Configuration de la Connexion Base de Données

La connexion à la base de données est centralisée dans le fichier `config.php` :

```php
$serverName = "DESKTOP-9LJFEUO\\SQLEXPRESS";
$connectionInfo = array(
    "Database"              => "stayhub",
    "TrustServerCertificate"=> true,
    "CharacterSet"          => "UTF-8"
);
$conn = sqlsrv_connect($serverName, $connectionInfo);
```

Ce fichier est inclus au début de chaque page PHP via `require_once 'config.php'`, garantissant une connexion unique et cohérente à travers toute l'application.

## 3.3 Structure des Répertoires

```
stayhub/
│
├── index.php              ← Page d'accueil (liste des annonces)
├── listing.php            ← Détail d'une annonce
├── become-host.php        ← Devenir hôte
├── host-dashboard.php     ← Tableau de bord hôte (créer une annonce)
├── my-listings.php        ← Gestion des annonces de l'hôte (voir, modifier, supprimer)
├── edit-listing.php       ← Formulaire de modification d'une annonce existante
├── my-rentals.php         ← Mes séjours / réservations
├── payment.php            ← Page de paiement
├── receipt.php            ← Reçu de paiement
├── profile.php            ← Profil utilisateur
├── update-profile.php     ← Traitement mise à jour profil
├── config.php             ← Configuration base de données
├── style.css              ← Feuille de styles globale
├── script.js              ← JavaScript global
├── database.sql           ← Schéma SQL de la base de données
├── StayHubIcon.png        ← Favicon de la plateforme
│
├── api/                   ← Endpoints API (traitement des formulaires)
│   ├── login.php          ← Authentification
│   ├── signup.php         ← Inscription
│   ├── logout.php         ← Déconnexion
│   ├── add-listing.php    ← Ajout d'annonce (formulaire)
│   ├── process-booking.php ← Traitement réservation
│   ├── process-payment.php ← Traitement paiement
│   ├── cancel-booking.php ← Annulation réservation
│   └── make-reservation.php ← Création réservation directe
│
├── admin/                 ← Espace d'administration
│   ├── index.php          ← Dashboard admin
│   ├── users.php          ← Gestion des utilisateurs
│   ├── listings.php       ← Gestion des annonces
│   ├── sidebar.php        ← Barre latérale admin (composant)
│   ├── admin-style.php    ← Styles CSS admin
│   ├── guard.php          ← Garde de sécurité admin
│   └── actions/           ← Actions admin
│       ├── ban-user.php
│       ├── unban-user.php
│       ├── delete-user.php
│       ├── flag-listing.php
│       ├── unflag-listing.php
│       └── delete-listing.php
│
├── img/                   ← Images locales
│   └── default-avatar.png
│
└── includes/              ← Composants réutilisables
    └── process-add-listing.php
```

---

# 4. ARCHITECTURE GLOBALE DU SYSTÈME

## 4.1 Architecture MVC Simplifié

Bien que le projet ne suive pas strictement le patron de conception MVC (Modèle-Vue-Contrôleur), son architecture s'en inspire naturellement :

- **Modèle** : Les fichiers de configuration (`config.php`) et les requêtes SQL intégrées dans les pages PHP constituent la couche de données.
- **Vue** : Les fichiers PHP contenant le code HTML constituent les vues, responsables de l'affichage.
- **Contrôleur** : Les fichiers du dossier `api/` et `admin/actions/` jouent le rôle de contrôleurs, traitant les requêtes POST/GET et redirigeant les utilisateurs.

## 4.2 Flux de Navigation Général

```
Visiteur
   │
   ├──► index.php (Accueil)
   │       │
   │       ├──► listing.php?id=X (Détail Annonce)
   │       │       │
   │       │       ├── [Non connecté] → Modal Login/Signup
   │       │       └── [Connecté] → Modal Réservation
   │       │               │
   │       │               └──► api/process-booking.php
   │       │                       │
   │       │                       └──► my-rentals.php
   │       │
   │       └──► [Menu utilisateur]
   │               ├── my-rentals.php → payment.php → receipt.php
   │               ├── profile.php
   │               ├── become-host.php → host-dashboard.php
   │               └── admin/index.php [admin seulement]
   │
   └── admin/
           ├── index.php (Dashboard)
           ├── users.php (Gestion Utilisateurs)
           └── listings.php (Gestion Annonces)
```

## 4.3 Gestion des Sessions

La gestion des sessions est au cœur de la sécurité de StayHub. Chaque page protégée commence par `session_start()` suivi d'une vérification de l'identifiant de session :

```php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
```

Les variables de session utilisées sont :

| Variable | Type | Description |
|---|---|---|
| `$_SESSION['user_id']` | int | Identifiant de l'utilisateur connecté |
| `$_SESSION['user_name']` | string | Nom de l'utilisateur |
| `$_SESSION['is_host']` | bool | L'utilisateur est-il hôte ? |
| `$_SESSION['is_admin']` | bool | L'utilisateur est-il administrateur ? |

---

# RAPPORT STAYHUB — PARTIE 2 : MODÉLISATION DES DONNÉES

---

# 5. MODÈLE CONCEPTUEL DE DONNÉES (MCD)

## 5.1 Introduction au MCD

Le Modèle Conceptuel de Données (MCD) représente la structure logique des informations manipulées par le système, indépendamment de toute considération technique ou de tout langage de base de données. Il décrit les **entités** (objets du monde réel), leurs **attributs** (propriétés) et les **associations** (relations) entre ces entités.

Le MCD de StayHub a été élaboré après une analyse approfondie des besoins fonctionnels de la plateforme et reflète fidèlement la structure de la base de données implémentée.

## 5.2 Entités Identifiées

L'analyse du système StayHub a permis d'identifier les entités suivantes :

### Entité : UTILISATEUR (users)

Cette entité représente toute personne ayant créé un compte sur la plateforme. Un utilisateur peut avoir plusieurs rôles : simple voyageur, hôte, ou administrateur.

| Attribut | Type | Description |
|---|---|---|
| **id** (PK) | Entier | Identifiant unique auto-incrémenté |
| name | Chaîne (100) | Nom complet de l'utilisateur |
| email | Chaîne (100) | Adresse email (unique) |
| phone | Chaîne (20) | Numéro de téléphone |
| password | Chaîne (255) | Mot de passe hashé (bcrypt) |
| is_host | Booléen | Indique si l'utilisateur est hôte |
| is_admin | Booléen | Indique si l'utilisateur est administrateur |
| is_banned | Booléen | Indique si le compte est banni |
| ban_reason | Chaîne (255) | Raison du bannissement (si applicable) |
| avatar | LONGBLOB | Photo de profil stockée en binaire |
| created_at | DateTime | Date et heure de création du compte |

### Entité : ANNONCE (listings)

Cette entité représente un logement publié par un hôte sur la plateforme. Chaque annonce appartient à un seul hôte et peut avoir plusieurs réservations, images et équipements associés.

| Attribut | Type | Description |
|---|---|---|
| **id** (PK) | Entier | Identifiant unique auto-incrémenté |
| user_id (FK) | Entier | Référence vers l'hôte (utilisateur) |
| title | Chaîne (200) | Titre de l'annonce |
| description | Texte | Description détaillée du logement |
| location | Chaîne (100) | Localisation géographique |
| price | Décimal (10,2) | Prix par nuit en MAD |
| bedrooms | Entier | Nombre de chambres |
| bathrooms | Entier | Nombre de salles de bain |
| guests | Entier | Capacité maximale d'accueil |
| voyageur_count | Entier | Nombre de voyageurs autorisés |
| bed_count | Entier | Nombre de lits disponibles |
| rating | Décimal (2,1) | Note moyenne de l'annonce |
| reviews | Entier | Nombre d'avis reçus |
| is_flagged | Booléen | Signalement par l'administrateur |
| flag_reason | Texte | Raison du signalement |
| created_at | DateTime | Date de publication |

### Entité : IMAGE (images)

Cette entité stocke les URLs des photos associées à chaque annonce. Une annonce peut avoir plusieurs images, dont une principale (is_primary = 1).

| Attribut | Type | Description |
|---|---|---|
| **id** (PK) | Entier | Identifiant unique auto-incrémenté |
| listing_id (FK) | Entier | Référence vers l'annonce |
| image_url | Texte | URL de l'image (Unsplash ou locale) |
| is_primary | Booléen | Indique si c'est l'image principale |

### Entité : ÉQUIPEMENT (amenities)

Cette entité liste les équipements disponibles dans chaque logement (WiFi, Piscine, Climatisation, etc.).

| Attribut | Type | Description |
|---|---|---|
| **id** (PK) | Entier | Identifiant unique auto-incrémenté |
| listing_id (FK) | Entier | Référence vers l'annonce |
| name | Chaîne (50) | Nom de l'équipement |

### Entité : RÉSERVATION (reservations)

Cette entité représente une demande de réservation effectuée par un utilisateur pour un logement donné. Elle contient toutes les informations relatives au séjour.

| Attribut | Type | Description |
|---|---|---|
| **id** (PK) | Entier | Identifiant unique auto-incrémenté |
| listing_id (FK) | Entier | Référence vers l'annonce |
| user_id (FK) | Entier | Référence vers l'utilisateur réservant |
| guest_name | Chaîne (100) | Nom du voyageur |
| guest_email | Chaîne (100) | Email du voyageur |
| guest_phone | Chaîne (20) | Téléphone du voyageur |
| check_in | Date | Date d'arrivée |
| check_out | Date | Date de départ |
| guests | Entier | Nombre de voyageurs |
| total_price | Décimal (10,2) | Montant total en MAD |
| status | ENUM | État : 'pending', 'confirmed', 'cancelled' |
| created_at | DateTime | Date de création de la réservation |

### Entité : PAIEMENT (payments)

Cette entité enregistre les informations de paiement associées à chaque réservation confirmée.

| Attribut | Type | Description |
|---|---|---|
| **id** (PK) | Entier | Identifiant unique auto-incrémenté |
| reservation_id (FK) | Entier | Référence vers la réservation |
| amount | Décimal (10,2) | Montant payé |
| payment_method | Chaîne (50) | Méthode de paiement (ex : carte) |
| payment_status | Chaîne (50) | État du paiement (completed) |
| created_at | DateTime | Date et heure du paiement |

### Entité : FACTURE (invoices)

Cette entité génère une facture officielle pour chaque paiement effectué, avec un numéro de facture unique.

| Attribut | Type | Description |
|---|---|---|
| **id** (PK) | Entier | Identifiant unique auto-incrémenté |
| payment_id (FK) | Entier | Référence vers le paiement |
| invoice_number | Chaîne (100) | Numéro de facture unique |
| tax_amount | Décimal (10,2) | Montant des taxes |
| total_amount | Décimal (10,2) | Montant total TTC |
| issued_at | DateTime | Date d'émission |

## 5.3 Représentation Textuelle du MCD

```
╔══════════════════╗         ╔══════════════════╗
║   UTILISATEUR    ║         ║     ANNONCE      ║
╠══════════════════╣         ╠══════════════════╣
║ #id              ║         ║ #id              ║
║ name             ║  1    n ║ title            ║
║ email            ║────────>║ description      ║
║ phone            ║ publie  ║ location         ║
║ password         ║         ║ price            ║
║ is_host          ║         ║ voyageur_count   ║
║ is_admin         ║         ║ bed_count        ║
║ is_banned        ║         ║ is_flagged       ║
║ ban_reason       ║         ║ created_at       ║
║ avatar           ║         ╚══════════════════╝
║ created_at       ║                 │
╚══════════════════╝                 │ 1
         │                           │
         │ 1                    n    │ possède    n    ╔══════════════╗
         │                           ├────────────────>║    IMAGE     ║
    n    │ effectue                  │                  ╠══════════════╣
         ▼                           │                  ║ #id          ║
╔══════════════════╗                 │                  ║ listing_id   ║
║  RÉSERVATION     ║                 │                  ║ image_url    ║
╠══════════════════╣                 │                  ║ is_primary   ║
║ #id              ║                 │                  ╚══════════════╝
║ listing_id       ║<────────────────┘
║ user_id          ║            n    ╔══════════════╗
║ guest_name       ║ 1               ║  ÉQUIPEMENT  ║
║ guest_email      ║ possède         ╠══════════════╣
║ guest_phone      ║────────────────>║ #id          ║
║ check_in         ║                 ║ listing_id   ║
║ check_out        ║                 ║ name         ║
║ guests           ║                 ╚══════════════╝
║ total_price      ║
║ status           ║
║ created_at       ║
╚══════════════════╝
         │
         │ 1
         │ génère
         │
         ▼
╔══════════════════╗        ╔══════════════════╗
║    PAIEMENT      ║ 1    1 ║    FACTURE       ║
╠══════════════════╣───────>╠══════════════════╣
║ #id              ║génère  ║ #id              ║
║ reservation_id   ║        ║ payment_id       ║
║ amount           ║        ║ invoice_number   ║
║ payment_method   ║        ║ tax_amount       ║
║ payment_status   ║        ║ total_amount     ║
║ created_at       ║        ║ issued_at        ║
╚══════════════════╝        ╚══════════════════╝
```

## 5.4 Associations et Cardinalités

| Association | Entité 1 | Cardinalité | Entité 2 | Description |
|---|---|---|---|---|
| **publie** | UTILISATEUR | 1,N | ANNONCE | Un utilisateur (hôte) peut publier plusieurs annonces ; une annonce appartient à un seul hôte |
| **effectue** | UTILISATEUR | 0,N | RÉSERVATION | Un utilisateur peut effectuer plusieurs réservations ; une réservation appartient à un utilisateur |
| **concerne** | ANNONCE | 1,N | RÉSERVATION | Une annonce peut avoir plusieurs réservations ; une réservation concerne une seule annonce |
| **possède (images)** | ANNONCE | 1,N | IMAGE | Une annonce possède une ou plusieurs images |
| **possède (équipements)** | ANNONCE | 0,N | ÉQUIPEMENT | Une annonce peut avoir plusieurs équipements |
| **génère (paiement)** | RÉSERVATION | 0,1 | PAIEMENT | Une réservation confirmée génère un paiement |
| **génère (facture)** | PAIEMENT | 1,1 | FACTURE | Un paiement génère une facture unique |

---

# 6. MODÈLE LOGIQUE DE DONNÉES (MLD)

## 6.1 Introduction au MLD

Le Modèle Logique de Données (MLD) est la traduction du MCD en un schéma de tables relationnelles, conforme aux règles de normalisation des bases de données. À ce stade, les entités deviennent des tables, les attributs deviennent des colonnes, et les associations sont matérialisées par des clés étrangères (Foreign Keys).

La transformation respecte les règles suivantes :
- Chaque entité devient une table.
- Chaque attribut devient une colonne avec son type de données précis.
- Les associations de type (1,N) se traduisent par une clé étrangère dans la table du côté "N".
- Les associations de type (N,N) se traduisent par une table de jointure.

## 6.2 Schéma MLD Complet

### Table : users

```
users (
    id              INT          NOT NULL AUTO_INCREMENT,
    name            VARCHAR(100) NOT NULL,
    email           VARCHAR(100) NOT NULL UNIQUE,
    phone           VARCHAR(20),
    password        VARCHAR(255) NOT NULL,
    is_host         TINYINT(1)   DEFAULT 0,
    is_admin        TINYINT(1)   DEFAULT 0,
    is_banned       TINYINT(1)   DEFAULT 0,
    ban_reason      VARCHAR(255),
    avatar          LONGBLOB,
    created_at      DATETIME     DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
)
```

### Table : listings

```
listings (
    id              INT           NOT NULL AUTO_INCREMENT,
    user_id         INT           NOT NULL,
    title           VARCHAR(200)  NOT NULL,
    description     TEXT,
    location        VARCHAR(100)  NOT NULL,
    price           DECIMAL(10,2) NOT NULL,
    bedrooms        INT           DEFAULT 1,
    bathrooms       INT           DEFAULT 1,
    guests          INT           DEFAULT 1,
    voyageur_count  INT           DEFAULT 1,
    bed_count       INT           DEFAULT 1,
    rating          DECIMAL(2,1)  DEFAULT 0,
    reviews         INT           DEFAULT 0,
    is_flagged      TINYINT(1)    DEFAULT 0,
    flag_reason     TEXT,
    created_at      DATETIME      DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)
```

### Table : images

```
images (
    id              INT  NOT NULL AUTO_INCREMENT,
    listing_id      INT  NOT NULL,
    image_url       TEXT NOT NULL,
    is_primary      TINYINT(1) DEFAULT 0,
    PRIMARY KEY (id),
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
)
```

### Table : amenities

```
amenities (
    id              INT         NOT NULL AUTO_INCREMENT,
    listing_id      INT         NOT NULL,
    name            VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
)
```

### Table : reservations

```
reservations (
    id              INT           NOT NULL AUTO_INCREMENT,
    listing_id      INT           NOT NULL,
    user_id         INT,
    guest_name      VARCHAR(100)  NOT NULL,
    guest_email     VARCHAR(100)  NOT NULL,
    guest_phone     VARCHAR(20)   NOT NULL,
    check_in        DATE          NOT NULL,
    check_out       DATE          NOT NULL,
    guests          INT           NOT NULL DEFAULT 1,
    total_price     DECIMAL(10,2) NOT NULL,
    status          ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
    created_at      DATETIME      DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE SET NULL
)
```

### Table : payments

```
payments (
    id              INT           NOT NULL AUTO_INCREMENT,
    reservation_id  INT           NOT NULL,
    amount          DECIMAL(10,2) NOT NULL,
    payment_method  VARCHAR(50)   NOT NULL,
    payment_status  VARCHAR(50)   DEFAULT 'completed',
    created_at      DATETIME      DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE
)
```

### Table : invoices

```
invoices (
    id              INT           NOT NULL AUTO_INCREMENT,
    payment_id      INT           NOT NULL,
    invoice_number  VARCHAR(100)  NOT NULL UNIQUE,
    tax_amount      DECIMAL(10,2) DEFAULT 0,
    total_amount    DECIMAL(10,2) NOT NULL,
    issued_at       DATETIME      DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE
)
```

## 6.3 Diagramme de Relations entre Tables

```
┌─────────────┐       ┌─────────────────┐       ┌────────────────┐
│   users     │       │    listings     │       │    images      │
├─────────────┤       ├─────────────────┤       ├────────────────┤
│ PK: id      │──1─N─>│ PK: id          │──1─N─>│ PK: id         │
│ name        │       │ FK: user_id     │       │ FK: listing_id │
│ email       │       │ title           │       │ image_url      │
│ phone       │       │ description     │       │ is_primary     │
│ password    │       │ location        │       └────────────────┘
│ is_host     │       │ price           │
│ is_admin    │       │ voyageur_count  │       ┌────────────────┐
│ is_banned   │       │ bed_count       │──1─N─>│   amenities    │
│ ban_reason  │       │ is_flagged      │       ├────────────────┤
│ avatar      │       │ created_at      │       │ PK: id         │
│ created_at  │       └─────────────────┘       │ FK: listing_id │
└─────────────┘               │                 │ name           │
       │                      │ 1─N             └────────────────┘
       │ 1─N                  ▼
       │            ┌──────────────────┐
       └──────────> │   reservations   │
                    ├──────────────────┤
                    │ PK: id           │
                    │ FK: listing_id   │
                    │ FK: user_id      │
                    │ guest_name       │
                    │ guest_email      │
                    │ guest_phone      │
                    │ check_in         │
                    │ check_out        │
                    │ guests           │
                    │ total_price      │
                    │ status           │
                    │ created_at       │
                    └──────────────────┘
                              │
                              │ 1─1
                              ▼
                    ┌──────────────────┐       ┌────────────────┐
                    │    payments      │──1─1─>│   invoices     │
                    ├──────────────────┤       ├────────────────┤
                    │ PK: id           │       │ PK: id         │
                    │ FK: reservation_id│       │ FK: payment_id │
                    │ amount           │       │ invoice_number │
                    │ payment_method   │       │ tax_amount     │
                    │ payment_status   │       │ total_amount   │
                    │ created_at       │       │ issued_at      │
                    └──────────────────┘       └────────────────┘
```

---

# 7. BASE DE DONNÉES — SCHÉMA PHYSIQUE DÉTAILLÉ

## 7.1 Création de la Base de Données

Le fichier `database.sql` fourni avec le projet contient l'intégralité du schéma SQL pour créer et initialiser la base de données StayHub :

```sql
-- Création de la base de données
CREATE DATABASE IF NOT EXISTS stayhub
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE stayhub;

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(100) UNIQUE NOT NULL,
    phone      VARCHAR(20),
    password   VARCHAR(255) NOT NULL,
    is_host    TINYINT(1) DEFAULT 0,
    is_admin   TINYINT(1) DEFAULT 0,
    is_banned  TINYINT(1) DEFAULT 0,
    ban_reason VARCHAR(255),
    avatar     LONGBLOB,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table des annonces
CREATE TABLE IF NOT EXISTS listings (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    title          VARCHAR(200) NOT NULL,
    description    TEXT,
    location       VARCHAR(100) NOT NULL,
    price          DECIMAL(10,2) NOT NULL,
    bedrooms       INT DEFAULT 1,
    bathrooms      INT DEFAULT 1,
    guests         INT DEFAULT 1,
    voyageur_count INT DEFAULT 1,
    bed_count      INT DEFAULT 1,
    rating         DECIMAL(2,1) DEFAULT 0,
    reviews        INT DEFAULT 0,
    is_flagged     TINYINT(1) DEFAULT 0,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des images
CREATE TABLE IF NOT EXISTS images (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    image_url  TEXT NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
);

-- Table des équipements
CREATE TABLE IF NOT EXISTS amenities (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    name       VARCHAR(50) NOT NULL,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
);

-- Table des réservations
CREATE TABLE IF NOT EXISTS reservations (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    listing_id  INT NOT NULL,
    user_id     INT,
    guest_name  VARCHAR(100) NOT NULL,
    guest_email VARCHAR(100) NOT NULL,
    guest_phone VARCHAR(20) NOT NULL,
    check_in    DATE NOT NULL,
    check_out   DATE NOT NULL,
    guests      INT NOT NULL DEFAULT 1,
    total_price DECIMAL(10,2) NOT NULL,
    status      ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Table des paiements
CREATE TABLE IF NOT EXISTS payments (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    amount         DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_status VARCHAR(50) DEFAULT 'completed',
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE
);

-- Table des factures
CREATE TABLE IF NOT EXISTS invoices (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    payment_id     INT NOT NULL,
    invoice_number VARCHAR(100) UNIQUE NOT NULL,
    tax_amount     DECIMAL(10,2) DEFAULT 0,
    total_amount   DECIMAL(10,2) NOT NULL,
    issued_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE
);
```

## 7.2 Données Initiales (Seed Data)

Le fichier SQL inclut également des données de démonstration pour permettre de tester l'application :

```sql
-- Utilisateurs de démo (hôtes)
INSERT INTO users (name, email, phone, password, is_host) VALUES
('Ahmed Bennani', 'ahmed@example.com', '+212612345678',
 '$2y$...hash...', 1),
('Fatima Zahra', 'fatima@example.com', '+212623456789',
 '$2y$...hash...', 1);

-- Annonces de démo
INSERT INTO listings (user_id, title, description, location, price,
                      voyageur_count, bed_count) VALUES
(1, 'Appartement Moderne à Casablanca',
 'Vue sur mer. Appartement de luxe avec accès direct à la plage.',
 'Casablanca, Morocco', 450, 4, 2),
(2, 'Villa avec Piscine à Marrakech',
 'Magnifique villa avec piscine privée au cœur de la palmeraie.',
 'Marrakech, Morocco', 1200, 8, 4);

-- Images de démo (Unsplash)
INSERT INTO images (listing_id, image_url, is_primary) VALUES
(1, 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800', 1),
(2, 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=800', 1);

-- Équipements de démo
INSERT INTO amenities (listing_id, name) VALUES
(1, 'WiFi'), (1, 'Climatisation'),
(2, 'Piscine'), (2, 'WiFi');
```

## 7.3 Contraintes d'Intégrité

La base de données StayHub implémente plusieurs contraintes d'intégrité référentielle importantes :

### Contrainte ON DELETE CASCADE

Cette contrainte garantit la cohérence des données lors de la suppression :
- Si un **utilisateur** est supprimé → toutes ses **annonces** sont supprimées → toutes les **réservations**, **images** et **équipements** liés sont également supprimés en cascade.
- Si une **annonce** est supprimée → toutes ses **réservations**, **images** et **équipements** sont supprimés.
- Si une **réservation** est supprimée → le **paiement** associé et la **facture** sont supprimés.

### Contrainte ON DELETE SET NULL

Pour les réservations liées à un utilisateur :
- Si un **utilisateur** est supprimé → le champ `user_id` dans ses **réservations** est mis à NULL (les réservations sont conservées pour des raisons d'archivage comptable).

### Contrainte UNIQUE

- Le champ `email` dans la table `users` est unique → empêche les doublons de comptes.
- Le champ `invoice_number` dans la table `invoices` est unique → garantit l'unicité des numéros de facture.

## 7.4 Index et Performance

La structure de la base de données favorise les performances grâce à :
- Les clés primaires `AUTO_INCREMENT` qui créent automatiquement des index clustered.
- Les clés étrangères qui créent des index sur les colonnes de jointure les plus fréquentes (`listing_id`, `user_id`, `reservation_id`).
- Les requêtes utilisant des clauses WHERE sur des colonnes indexées pour filtrer efficacement les annonces disponibles.

---

# RAPPORT STAYHUB — PARTIE 3 : INTERFACES UTILISATEUR

---

# 8. DESCRIPTION DES INTERFACES UTILISATEUR

Cette section décrit en détail chacune des interfaces de la plateforme StayHub, leur rôle fonctionnel, les éléments visuels présents et les actions disponibles via les boutons et formulaires.

---

## 8.1 Page d'Accueil — `index.php`

### Description Générale

La page d'accueil est la première page que voit tout visiteur arrivant sur la plateforme. Elle constitue le cœur de l'expérience de navigation : elle liste l'ensemble des logements disponibles à la réservation et propose une interface de recherche.

Techniquement, cette page exécute une requête SQL complexe pour ne récupérer que les annonces **non actuellement réservées** (logements dont la date de check-out de toute réservation active est déjà passée) :

```sql
SELECT l.id, u.name AS Host, l.title, l.location, l.price, i.image_url AS MainPhoto
FROM listings l
JOIN users u ON l.user_id = u.id
LEFT JOIN images i ON l.id = i.listing_id AND i.is_primary = 1
WHERE l.id NOT IN (
    SELECT listing_id FROM reservations
    WHERE check_out > CAST(GETDATE() AS DATE)
    AND status != 'cancelled'
)
```

### Éléments de l'Interface

#### En-tête (Header)

L'en-tête est une barre de navigation fixe en haut de page, composée de trois sections principales :

1. **Logo StayHub** (gauche) : Texte cliquable « StayHub » en rouge (#ff385c). Au clic, redirige vers `index.php` (rechargement de la page d'accueil).

2. **Barre de recherche** (centre) : Un champ de texte stylisé avec un bouton de recherche circulaire rouge. Permet de filtrer les annonces par titre ou localisation.

3. **Menu utilisateur** (droite) : Un bouton « pill » avec une icône hamburger (≡) et une icône utilisateur. Au clic, un menu déroulant s'ouvre avec des options contextuelles.

#### Menu Déroulant (Dropdown)

Le contenu du menu déroulant est dynamique selon l'état de connexion :

**Si l'utilisateur N'est PAS connecté :**
| Bouton | Action |
|---|---|
| **Log in** | Ouvre la modale de connexion |
| **Sign up** | Ouvre la modale d'inscription |

**Si l'utilisateur EST connecté :**
| Bouton | Action |
|---|---|
| **Admin Panel** | Redirige vers `admin/index.php` *(visible uniquement pour les admins)* |
| **My Stays** | Redirige vers `my-rentals.php` |
| **Profile** | Redirige vers `profile.php` |
| **Add your listing** | Redirige vers `host-dashboard.php` *(visible uniquement pour les hôtes)* |
| **Become a Host** | Redirige vers `become-host.php` *(visible uniquement pour les non-hôtes)* |
| **Log out** | Redirige vers `api/logout.php` (déconnexion et redirection) |

#### Grille des Annonces

La partie principale de la page affiche les annonces sous forme d'une grille responsive. Chaque carte d'annonce contient :
- **Photo principale** du logement
- **Localisation** de l'hébergement
- **Note** (étoile + valeur fixe 4.9 dans la version actuelle)
- **Nom de l'hôte**
- **Prix par nuit** en MAD (Dirhams marocains)

Au clic sur une carte, l'utilisateur est redirigé vers `listing.php?id={id_annonce}`.

#### Modales d'Authentification

Deux fenêtres modales sont intégrées à la page (masquées par défaut) :
- **Modal Connexion** (`#loginModal`) : Inclut le formulaire de connexion via `api/login.php`
- **Modal Inscription** (`#signupModal`) : Inclut le formulaire d'inscription via `api/signup.php`

Ces modales sont activées via les fonctions JavaScript `openLogin()` et `openSignup()`, et peuvent être fermées en cliquant sur le fond sombre ou sur le bouton ×.

### Fonctionnalité de Recherche

Le formulaire de recherche soumet en méthode GET avec le paramètre `search`. La requête SQL est alors modifiée dynamiquement :

```php
if (!empty($search)) {
    $sql .= " AND (l.title LIKE ? OR l.location LIKE ?)";
    $params = ["%$search%", "%$search%"];
}
```

---

## 8.2 Page de Détail d'une Annonce — `listing.php`

### Description Générale

Accessible via `listing.php?id={identifiant}`, cette page affiche tous les détails d'un logement spécifique. C'est la page clé pour convaincre un voyageur de réserver.

### Éléments de l'Interface

#### Navigation

- **Logo StayHub** (lien rouge) → redirige vers `index.php`
- **← Return Home** → retour à la page d'accueil

#### Zone Principale

**Titre et localisation :**
- Le titre de l'annonce en grand (h1)
- La localisation en gris sous le titre

**Galerie d'images :**
- Un grand bloc d'image (450px de hauteur) avec l'image principale comme arrière-plan CSS. Si aucune image n'est trouvée en base, une image Unsplash par défaut est utilisée.

**Section Informations (disposition 2 colonnes) :**

*Colonne gauche (détails)* :
- Titre « Entire home hosted by {Nom de l'hôte} »
- Nombre de voyageurs et de lits
- Description complète du logement
- Section équipements : affichage des amenities sous forme de tags colorés

*Colonne droite (carte de réservation)* :
- Prix par nuit affiché en grand (ex : **450 MAD / nuit**)
- **Bouton « Reserve »** *(si connecté)* → ouvre la modale de réservation
- **Bouton « Log in to Reserve »** *(si non connecté)* → ouvre la modale de connexion

#### Messages de Feedback

La page affiche des messages contextuels via les paramètres GET :

| Paramètre GET | Message affiché |
|---|---|
| `?success=booked` | ✅ Réservation effectuée avec succès |
| `?error=already_booked` | ❌ Ces dates sont déjà prises |
| `?error=not_logged_in` | ❌ Vous devez être connecté |

#### Modale de Réservation (`#resModal`)

La modale de réservation est accessible uniquement aux utilisateurs connectés. Elle contient un formulaire envoyé en POST vers `api/process-booking.php` :

| Champ | Type | Description |
|---|---|---|
| `listing_id` | Caché | ID de l'annonce (automatique) |
| `guest_name` | Texte | Nom complet du voyageur |
| `guest_email` | Email | Email du voyageur |
| `guest_phone` | Texte | Numéro de téléphone |
| `check_in` | Date | Date d'arrivée (min = aujourd'hui) |
| `check_out` | Date | Date de départ |

**Boutons de la modale :**
| Bouton | Action |
|---|---|
| **× (fermer)** | Ferme la modale sans action |
| **Confirm Booking** | Soumet le formulaire vers `api/process-booking.php` |

Un script JavaScript vérifie que la date de check-out est toujours postérieure au check-in.

---

## 8.3 Modales d'Authentification — `api/login.php` & `api/signup.php`

### Modale de Connexion

Le formulaire de connexion est inclus via `include 'api/login.php'` dans les pages `index.php` et `listing.php`. Il contient :

| Champ | Type | Description |
|---|---|---|
| Email | Email | Adresse email du compte |
| Mot de passe | Password | Mot de passe (masqué) |

**Bouton :**
| Bouton | Action |
|---|---|
| **Se connecter / Log in** | Vérifie les identifiants, crée la session, redirige |

La logique de connexion :
1. Récupère l'utilisateur par email.
2. Vérifie le mot de passe avec `password_verify()` (bcrypt).
3. Vérifie que le compte n'est pas banni (`is_banned = 0`).
4. Si valide, crée les variables de session : `user_id`, `user_name`, `is_host`, `is_admin`.
5. Redirige vers la page précédente ou l'accueil.

### Modale d'Inscription

Le formulaire d'inscription contient :

| Champ | Type | Description |
|---|---|---|
| Nom complet | Texte | Nom affiché sur le profil |
| Email | Email | Identifiant de connexion (unique) |
| Téléphone | Texte | Numéro de téléphone |
| Mot de passe | Password | Hashé avec `password_hash()` (bcrypt) |

**Bouton :**
| Bouton | Action |
|---|---|
| **S'inscrire / Sign up** | Crée le compte, connecte l'utilisateur, redirige |

---

## 8.4 Page Devenir Hôte — `become-host.php`

### Description Générale

Cette page guide un utilisateur connecté à travers le processus pour devenir hôte sur la plateforme. Elle est accessible uniquement aux utilisateurs connectés qui ne sont pas encore hôtes. Si l'utilisateur est déjà hôte, il est automatiquement redirigé vers `host-dashboard.php`.

### Éléments de l'Interface

#### Navigation

- **Logo StayHub** → `index.php`
- **← Back to home** → `index.php`

#### Section Hero (Bannière)

Bandeau rouge dégradé avec :
- Grande icône maison 🏠
- Titre : « Become a StayHub Host »
- Sous-titre descriptif

#### Grille des Avantages (4 cartes)

Quatre cartes mettant en avant les bénéfices de devenir hôte :
1. 💰 **Earn money** — Définissez vos propres prix et gagnez un revenu
2. 🛡️ **Host protection** — Protection des logements par la plateforme
3. 📅 **You're in control** — Vous décidez des disponibilités
4. 🌍 **Global reach** — Millions de voyageurs sur la plateforme

#### Bouton d'Action Principal

| Bouton | Action |
|---|---|
| **Get started →** | Ouvre la modale d'accord hôte (règles à accepter) |

#### Modale d'Accord Hôte (`#rulesOverlay`)

Cette modale présente les conditions que l'utilisateur doit accepter avant de devenir hôte :

**Règles affichées :**
1. ✅ Annonces exactes et honnêtes
2. 🧹 Propriété propre et sécurisée
3. 💬 Communication dans les 24h
4. 🚫 Aucune discrimination
5. 💳 Respect des réservations et paiements
6. 📜 Conformité aux lois locales

⚠️ Une boîte d'avertissement rappelle les risques de suspension.

**Case à cocher :**
L'utilisateur doit cocher « J'ai lu et j'accepte toutes les règles StayHub » pour activer le bouton de confirmation.

**Boutons de la modale :**
| Bouton | État | Action |
|---|---|---|
| **Decline** | Toujours actif | Ferme la modale, réinitialise la case |
| **I agree — Become a Host** | Désactivé par défaut | Activé si case cochée → soumet le formulaire POST vers `become-host.php` |

Lors de la soumission, la base de données est mise à jour :
```sql
UPDATE users SET is_host = 1 WHERE id = ?
```
Et la session est mise à jour : `$_SESSION['is_host'] = 1`.
L'utilisateur est ensuite redirigé vers `host-dashboard.php`.

---

## 8.5 Tableau de Bord Hôte — `host-dashboard.php`

### Description Générale

Accessible uniquement aux hôtes authentifiés (`is_host = 1`), cette page permet la création et la publication de nouvelles annonces de logements. Si l'utilisateur n'est pas hôte, il est redirigé vers `become-host.php`.

### Éléments de l'Interface

#### En-tête

- **Logo StayHub** → `index.php`
- **Switch to Traveling** → `index.php` (retour à la vue voyageur)

#### En-tête du Formulaire

- Titre : « Create a New Listing »
- Sous-titre avec nom d'utilisateur : « Welcome back, {Nom} ! Let's get your home ready for guests. »

#### Formulaire de Création d'Annonce

Le formulaire est soumis en POST avec `enctype="multipart/form-data"` vers `api/add-listing.php`.

**Champs du formulaire :**

| Champ | Type | Obligatoire | Description |
|---|---|---|---|
| `title` | Texte | ✅ | Titre de l'annonce (ex : Cozy Beachfront Villa) |
| `location` | Texte | ✅ | Localisation (ex : Casablanca, Morocco) |
| `price` | Nombre | ✅ | Prix par nuit en MAD |
| `voyageur_count` | Nombre | ✅ | Nombre maximum de voyageurs |
| `bed_count` | Nombre | ✅ | Nombre de lits |
| `description` | Textarea | ❌ | Description libre du logement |
| `amenities[]` | Checkboxes | ❌ | Équipements disponibles |
| `property_images[]` | Fichier | ✅ | Photos du logement (multiple) |

**Section Équipements :**

Grille de cases à cocher pour sélectionner les équipements :
- 📶 WiFi
- 🍴 Kitchen (Cuisine)
- ❄️ Air Conditioning (Climatisation)
- 🚗 Free Parking (Parking gratuit)
- 🏊 Pool (Piscine)

**Zone d'Upload de Photos :**

Une zone en pointillés cliquable déclenche la sélection de fichiers. Les images sélectionnées sont prévisualisées immédiatement via `FileReader` JavaScript avant l'envoi.

**Bouton de soumission :**
| Bouton | Action |
|---|---|
| **Publish My Listing** | Soumet le formulaire vers `api/add-listing.php` |

#### Modal de Succès

Après publication réussie, un overlay de succès s'affiche avec :
- Icône ✅ verte
- Message « Listing Published! »
- **Bouton « Go to Home Page »** → `index.php`
- **Bouton « Add Another »** → `host-dashboard.php` (réinitialise le formulaire)

### Logique de Traitement (`api/add-listing.php`)

1. Vérification de la session et du statut hôte.
2. Insertion de l'annonce dans la table `listings`.
3. Upload des images vers le dossier `uploads/` et insertion des URLs dans `images`.
4. Insertion des équipements cochés dans `amenities`.
5. Redirection vers `host-dashboard.php?success=1`.

---

## 8.6 Mes Séjours — `my-rentals.php`

### Description Générale

Cette page est accessible uniquement aux utilisateurs connectés. Elle affiche l'historique complet des réservations de l'utilisateur connecté, avec la possibilité de payer, annuler ou consulter les reçus.

La requête SQL récupère les données des tables `reservations`, `listings` et `images` pour chaque réservation :

```sql
SELECT r.*, l.title, l.location, l.price, i.image_url
FROM reservations r
JOIN listings l ON r.listing_id = l.id
LEFT JOIN images i ON l.id = i.listing_id AND i.is_primary = 1
WHERE r.user_id = ?
ORDER BY r.created_at DESC
```

### Éléments de l'Interface

#### Navigation

- **Logo StayHub** → `index.php`
- **← Back to listings** → `index.php`

#### En-tête de Page

- Titre : « My Stays »
- Sous-titre : « All your reservations in one place »

#### Messages de Feedback (alertes)

| Paramètre GET | Message |
|---|---|
| `?success=cancelled` | ✅ Réservation annulée avec succès |
| `?success=paid` | ✅ Paiement réussi, réservation confirmée |
| `?error=already_cancelled` | ❌ Cette réservation est déjà annulée |
| `?error=notfound` | ❌ Réservation introuvable |
| `?error=db` | ❌ Erreur base de données |

#### État Vide

Si l'utilisateur n'a aucune réservation :
- Icône 🏡
- Message « No stays yet — Time to plan your next adventure! »
- **Bouton « Explore listings »** → `index.php`

#### Cartes de Réservation

Chaque réservation s'affiche sous forme d'une carte horizontale contenant :

**Partie gauche :**
- Photo du logement (180px × hauteur auto)

**Partie centrale :**
- Titre de l'annonce
- Localisation (icône pin + nom de ville)
- Dates : Check-in → Check-out (ex : 15 Mai 2025 → 20 Mai 2025)

**Partie droite :**
- Montant total en MAD
- Badge de statut coloré :
  - 🟢 **Confirmed** (vert) : réservation payée et confirmée
  - 🟡 **Pending** (jaune) : en attente de paiement
  - 🔴 **Cancelled** (rouge) : annulée
- Boutons contextuels selon le statut :

| Statut | Boutons disponibles |
|---|---|
| **Pending** | 💳 **Pay Now** → `payment.php?id={id}` ET ❌ **Cancel booking** |
| **Confirmed** | 🧾 **View Receipt** → `receipt.php?id={id}` |
| **Cancelled** | *(aucun bouton)* |

#### Modale de Confirmation d'Annulation (`#cancelModalOverlay`)

Lorsque l'utilisateur clique sur « Cancel booking », une modale de confirmation s'affiche :
- Icône ⚠️
- Message « Are you sure you want to cancel your stay at "{titre}" ? This cannot be undone. »
- **Bouton « Keep it »** → ferme la modale
- **Bouton « Yes, cancel »** → soumet le formulaire POST vers `api/cancel-booking.php`

---

## 8.7 Page de Paiement Sécurisé — `payment.php`

### Description Générale

Accessible via `payment.php?id={reservation_id}`, cette page propose un formulaire de paiement simulé pour les réservations en statut `pending`. Si la réservation n'existe pas, n'appartient pas à l'utilisateur connecté, ou n'est pas en attente, l'utilisateur est redirigé vers `my-rentals.php`.

### Éléments de l'Interface

La page est disposée en deux colonnes (grille 1:1) :

#### Colonne Gauche — Formulaire de Paiement

Titre : « Payment Details »  
Sous-titre : 🔒 « All transactions are secure and encrypted. (Mock Payment) »

Formulaire soumis en POST vers `api/process-payment.php` :

| Champ | Type | Description |
|---|---|---|
| `reservation_id` | Caché | ID de la réservation |
| `card_name` | Texte | Nom sur la carte bancaire |
| `card_number` | Texte | Numéro de carte (16 chiffres, max 19 chars) |
| `card_exp` | Texte | Date d'expiration (format MM/YY) |
| `card_cvv` | Texte | Code de sécurité (3 chiffres) |

**Bouton :**
| Bouton | Action |
|---|---|
| **Pay {montant} MAD** | Soumet le formulaire, traite le paiement simulé |

#### Colonne Droite — Résumé de la Réservation

Affiche un récapitulatif de la réservation :
- Photo du logement (200px de hauteur)
- Titre et localisation de l'annonce
- Dates de check-in et check-out
- **Décomposition du prix** :
  - Prix par nuit × nombre de nuits
  - Frais de ménage (150 MAD fixe)
  - Frais de service StayHub (10% du total)
  - **Total en MAD**

### Logique de Traitement (`api/process-payment.php`)

1. Vérification de la session.
2. Vérification que la réservation appartient à l'utilisateur et est en statut `pending`.
3. Mise à jour du statut : `UPDATE reservations SET status = 'confirmed' WHERE id = ?`
4. Redirection vers `receipt.php?id={reservation_id}`.

> **Note :** Le paiement est entièrement simulé. Aucune transaction bancaire réelle n'est effectuée. Le système accepte toute saisie de carte et confirme automatiquement la réservation.

---

## 8.8 Reçu de Paiement — `receipt.php`

### Description Générale

Accessible via `receipt.php?id={reservation_id}`, cette page génère un reçu de paiement officiel et imprimable. Elle est accessible uniquement pour les réservations en statut `confirmed`. Si la réservation n'est pas confirmée ou n'appartient pas à l'utilisateur, il est redirigé vers `my-rentals.php`.

La requête SQL joint les tables `reservations`, `listings` et `users` (hôte) :

```sql
SELECT r.*, l.title, l.location,
       host.name AS host_name,
       host.email AS host_email,
       host.phone AS host_phone
FROM reservations r
JOIN listings l ON r.listing_id = l.id
JOIN users host ON l.user_id = host.id
WHERE r.id = ? AND r.user_id = ? AND r.status = 'confirmed'
```

### Éléments de l'Interface

#### Bouton d'Impression

| Bouton | Action |
|---|---|
| **Imprimer le reçu** | Déclenche `window.print()` (masqué à l'impression via `@media print`) |

#### Sections du Reçu

**En-tête :**
- Titre : 🧾 REÇU DE PAIEMENT
- Numéro de reçu (format : REC-2025-XXXXXX)
- Date de paiement

**Alerte de confirmation :**
✅ PAIEMENT CONFIRMÉ — Ce document atteste du paiement effectué et doit être conservé comme justificatif.

**Grille 2 colonnes — Parties impliquées :**

| Bénéficiaire (Hôte) | Payeur (Voyageur) |
|---|---|
| Nom/Raison sociale | Nom complet |
| Adresse | Adresse |
| Téléphone | Téléphone |
| Email | Email |

**Tableau — Détails du Paiement :**
- Numéro de facture (format : FACT-2025-XXXXXX)
- Date de la facture
- Montant de la facture
- Objet du paiement (cases à cocher — Paiement total coché)

**Montant Payé :**
- Montant en chiffres
- Montant en lettres (champ à remplir)

**Mode de Paiement :**
Cases à cocher (carte bancaire cochée par défaut) :
- Espèces
- Virement bancaire
- ✅ Carte bancaire
- Chèque
- PayPal / Virement en ligne
- Western Union / MoneyGram

**Situation du Compte :**
Tableau récapitulatif montrant le montant total, les paiements antérieurs (0 MAD), ce paiement, et le solde restant (0 MAD).

**Informations sur la Réservation :**
- Propriété, adresse, date d'arrivée, date de départ, nombre de nuits.

**Section Caution :**
Cases à cocher pour indiquer si une caution a été versée (Non coché par défaut).

**Note importante :**
- Ce reçu atteste du paiement
- Doit être conservé comme justificatif
- La caution sera restituée sous 7 jours après le départ

**Section Certification :**
Texte légal : « Je soussigné(e) {nom de l'hôte} certifie avoir reçu la somme de {montant} MAD de la part de {nom du voyageur}... »

**Zones de Signature :**
- Propriétaire/Bénéficiaire (Signature et cachet)
- Client/Payeur (Signature pour reçu)

**Pied de Page :**
- Contact : adamnaime@gmail.com | +212684821930
- « StayHub — Plateforme de location sécurisée »

---

## 8.9 Profil Utilisateur — `profile.php`

### Description Générale

Accessible aux utilisateurs connectés, cette page affiche les informations du profil et permet leur modification (nom et photo de profil).

La requête SQL récupère les données de l'utilisateur :
```sql
SELECT name, email, avatar FROM users WHERE id = ?
```

### Éléments de l'Interface

#### Navigation

- **Logo StayHub** (centre) → `index.php`
- **← Return Home** (droite) → `index.php`

#### Carte de Profil

**Photo de profil :**
- Affichage circulaire (150×150px)
- Si une photo est enregistrée en base (LONGBLOB), elle est convertie en base64 et affichée directement
- Sinon, l'image par défaut `img/default-avatar.png` est utilisée

**Bouton « + »** (rouge, circulaire, superposé sur la photo) → Ouvre la modale de modification du profil.

**Informations affichées :**
- Nom de l'utilisateur (h2)
- Email de l'utilisateur

**Bouton :**
| Bouton | Action |
|---|---|
| **Se déconnecter** | Redirige vers `logout.php` (destruction de la session) |

#### Modale de Modification du Profil (`#profileModalOverlay`)

La modale contient un formulaire soumis en POST avec `enctype="multipart/form-data"` vers `update-profile.php` :

| Champ | Type | Description |
|---|---|---|
| `name` | Texte | Nouveau nom (pré-rempli avec le nom actuel) |
| `avatar` | Fichier | Nouvelle photo de profil (optionnel) |

**Aperçu en temps réel :** Lorsqu'une nouvelle photo est sélectionnée, elle s'affiche immédiatement sur la photo de profil principale via `URL.createObjectURL()`.

**Indicateur de chargement :** Lors de la soumission, le bouton se grise, le texte change en « Téléchargement... » et un spinner CSS s'affiche.

**Boutons :**
| Bouton | Action |
|---|---|
| **× (fermer)** | Ferme la modale |
| **Enregistrer les modifications** | Soumet le formulaire vers `update-profile.php` |

---

# RAPPORT STAYHUB — PARTIE 4 : ADMIN, API ET CONCLUSION

---

## 8.10 Panneau d'Administration — Tableau de Bord — `admin/index.php`

### Description Générale

Le panneau d'administration est accessible uniquement aux utilisateurs ayant `is_admin = 1` dans la base de données. La protection est assurée par le fichier `admin/guard.php` inclus au début de chaque page admin :

```php
// guard.php
session_start();
require_once '../config.php';
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: ../index.php');
    exit();
}
```

Le tableau de bord affiche une vue synthétique de l'ensemble de la plateforme, avec des statistiques clés et les dernières annonces publiées.

### Éléments de l'Interface

#### Barre Latérale (Sidebar — `admin/sidebar.php`)

La barre latérale est un composant réutilisable partagé entre toutes les pages admin. Elle contient :

- **Logo StayHub** (rouge)
- **Liens de navigation :**
  - 📊 Dashboard → `admin/index.php`
  - 👥 Users → `admin/users.php`
  - 🏠 Listings → `admin/listings.php`
  - ← Back to Site → `../index.php`

#### Zone Principale — Statistiques (Stats Cards)

Cinq cartes de statistiques affichées en grille, chacune avec une icône colorée et un chiffre dynamique :

| Carte | Icône | Couleur | Données |
|---|---|---|---|
| **Total Users** | 👥 | Rouge | COUNT(*) FROM users WHERE is_admin = 0 |
| **Total Listings** | 🏠 | Bleu | COUNT(*) FROM listings |
| **Flagged Listings** | 🚩 | Jaune | COUNT(*) FROM listings WHERE is_flagged = 1 |
| **Banned Accounts** | 🚫 | Rouge foncé | COUNT(*) FROM users WHERE is_banned = 1 |
| **Total Bookings** | 📅 | Vert | COUNT(*) FROM reservations |

#### Tableau des Dernières Annonces

Un tableau affichant les **5 dernières annonces publiées** (TOP 5 ORDER BY created_at DESC) avec les colonnes :
- Title (lien cliquable vers la page de l'annonce)
- Host (nom de l'hôte)
- Location (localisation)
- Price (prix en MAD)
- Status (badge Flagged/Active)
- Actions : **Bouton « Manage »** → redirige vers `admin/listings.php`

---

## 8.11 Gestion des Utilisateurs — `admin/users.php`

### Description Générale

Cette page liste l'ensemble des utilisateurs de la plateforme (à l'exception des administrateurs) et offre des outils de modération avancés.

### Éléments de l'Interface

#### En-tête de Page

- Titre : « Users »
- Sous-titre : « Manage, ban, or remove user accounts »
- Badge compteur : affiche le nombre total d'utilisateurs filtrés

#### Barre de Filtres

Formulaire GET contenant :

| Élément | Type | Description |
|---|---|---|
| Champ texte | Texte | Recherche par nom ou email |
| Menu déroulant | Select | Filtre : All users / Hosts only / Banned only |
| **Filter** | Bouton | Applique les filtres |
| **Clear** | Lien | Efface les filtres (visible si filtre actif) |

#### Tableau des Utilisateurs

Colonnes du tableau :

| Colonne | Description |
|---|---|
| **User** | Avatar circulaire + Nom + ID |
| **Email** | Adresse email |
| **Role** | Badge « Host » (bleu) ou « Guest » (gris) |
| **Listings** | Nombre d'annonces publiées |
| **Bookings** | Nombre de réservations effectuées |
| **Status** | Badge « Active » (vert) ou « Banned » (rouge) |
| **Ban reason** | Raison du bannissement si applicable |
| **Actions** | Boutons d'action |

#### Boutons d'Action par Ligne

| Bouton | État | Action |
|---|---|---|
| **Ban** (rouge) | Visible si utilisateur actif | Ouvre la modale de bannissement |
| **Unban** (vert) | Visible si utilisateur banni | Redirige vers `actions/unban-user.php?id={id}` |
| **Listings** (outline) | Visible si l'utilisateur a des annonces | Redirige vers `listings.php?search={email}` |
| **🗑️ Delete** (rouge) | Toujours visible | Ouvre la modale de suppression |

#### Modale de Bannissement (`#banOverlay`)

La modale de bannissement s'ouvre au clic sur « Ban » et contient :
- Titre : 🚫 Ban Account
- Message contextuel avec le nom de l'utilisateur
- **Champ textarea** : Raison du bannissement (obligatoire)
- **Bouton Cancel** → ferme la modale
- **Bouton Ban Account** → soumet le formulaire POST vers `actions/ban-user.php`

Logique (`actions/ban-user.php`) :
```sql
UPDATE users SET is_banned = 1, ban_reason = ? WHERE id = ?
```

#### Modale de Suppression (`#deleteUserOverlay`)

La modale de suppression contient :
- Titre : 🗑️ Delete Account
- Avertissement : suppression irréversible de toutes les données
- **Bouton Cancel** → ferme la modale
- **Bouton Delete Forever** → soumet POST vers `actions/delete-user.php`

Logique (`actions/delete-user.php`) :
```sql
DELETE FROM users WHERE id = ?
```
La contrainte ON DELETE CASCADE supprime automatiquement toutes les annonces, réservations et données associées.

#### Mise en Évidence (Highlight)

Si la page est accédée depuis `listings.php` avec `?highlight={user_id}`, la ligne de cet utilisateur est surlignée en rouge et la page défile automatiquement jusqu'à elle (smooth scroll).

---

## 8.12 Gestion des Annonces — `admin/listings.php`

### Description Générale

Cette page liste l'ensemble des annonces publiées et permet à l'administrateur de les signaler (flag), de lever le signalement, ou de les supprimer définitivement.

### Éléments de l'Interface

#### En-tête de Page

- Titre : « Listings »
- Sous-titre : « Review, flag, or remove property listings »
- Badge compteur

#### Barre de Filtres

| Élément | Type | Description |
|---|---|---|
| Champ texte | Texte | Recherche par titre, localisation ou nom d'hôte |
| Menu déroulant | Select | Filtre : All listings / Flagged only / Active only |
| **Filter** | Bouton | Applique les filtres |
| **Clear** | Lien | Efface les filtres |

#### Tableau des Annonces

Colonnes :

| Colonne | Description |
|---|---|
| **Property** | Miniature image + titre (lien) + voyageurs/lits |
| **Host** | Nom + email de l'hôte + badge « Host banned » si banni |
| **Location** | Localisation |
| **Price** | Prix en MAD |
| **Status** | Badge Active (vert) ou Flagged (rouge) |
| **Flag reason** | Raison du signalement si applicable |
| **Actions** | Boutons d'action |

#### Boutons d'Action par Ligne

| Bouton | État | Action |
|---|---|---|
| **Flag** (jaune) | Visible si annonce active | Ouvre la modale de signalement |
| **Unflag** (vert) | Visible si annonce signalée | Redirige vers `actions/unflag-listing.php?id={id}` |
| **Host** (outline) | Toujours visible | Redirige vers `users.php?highlight={user_id}` |
| **🗑️ Delete** (rouge) | Toujours visible | Ouvre la modale de suppression |

#### Modale de Signalement (`#flagOverlay`)

- Titre : ⚠️ Flag Listing
- Champ textarea : raison du signalement
- **Bouton Cancel**
- **Bouton Flag Listing**

Logique (`actions/flag-listing.php`) :
```sql
UPDATE listings SET is_flagged = 1, flag_reason = ? WHERE id = ?
```

#### Modale de Suppression (`#deleteOverlay`)

- Confirmation de suppression permanente
- **Bouton Cancel**
- **Bouton Delete Forever**

Logique (`actions/delete-listing.php`) :
```sql
DELETE FROM listings WHERE id = ?
```

---

## 8.13 Gestion des Annonces Hôte — `my-listings.php`

### Description Générale

Cette page est accessible **uniquement aux utilisateurs ayant le statut d'hôte** (`is_host = 1`). Elle constitue le tableau de bord de gestion des propriétés de l'hôte : il peut y consulter l'ensemble de ses annonces publiées, visualiser en temps réel le nombre de réservations et les revenus générés par chaque propriété, accéder à l'historique complet des locataires, et effectuer les opérations de modification ou de suppression de ses annonces.

Si un utilisateur non hôte tente d'accéder directement à cette URL, il est automatiquement redirigé vers `become-host.php`.

La vérification de sécurité en début de fichier est la suivante :

```php
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_host'])) {
    header('Location: become-host.php');
    exit;
}
```

La requête SQL principale agrège les données des tables `listings`, `images` et `reservations` pour fournir une vue synthétique de chaque propriété :

```sql
SELECT l.*,
       i.image_url AS main_photo,
       COUNT(r.id) AS total_bookings,
       COALESCE(SUM(CASE WHEN r.status != 'cancelled'
                    THEN r.total_price ELSE 0 END), 0) AS total_revenue
FROM listings l
LEFT JOIN images i ON l.id = i.listing_id AND i.is_primary = 1
LEFT JOIN reservations r ON l.id = r.listing_id
WHERE l.user_id = ?
GROUP BY l.id, l.title, l.location, l.price, ...
ORDER BY l.created_at DESC
```

### Accès depuis le Menu Déroulant

L'accès à cette page est intégré directement dans le **menu déroulant de la barre de navigation** de `index.php`. Ce menu affiche deux nouvelles entrées **visibles uniquement pour les hôtes** :

| Entrée du Menu | Icône | Action |
|---|---|---|
| **My Listings** | 🏠 | Redirige vers `my-listings.php` |
| **Add a Listing** | ➕ | Redirige vers `host-dashboard.php` |

Pour les utilisateurs non hôtes, ces entrées sont remplacées par l'unique lien **Become a Host**.

> 📸 **[Insérer ici une capture d'écran du menu déroulant ouvert, montrant les entrées « My Listings » et « Add a Listing »]**

### Éléments de l'Interface

#### Navigation

- **Logo StayHub** (rouge) → `index.php`
- **← Back to Home** → `index.php`
- **Bouton « Add New Listing »** (rouge, en haut à droite) → `host-dashboard.php`

#### Barre de Statistiques Globales

En haut de la page, une barre de trois cartes de statistiques présente une vue d'ensemble des performances de l'hôte :

| Carte | Icône | Couleur | Donnée affichée |
|---|---|---|---|
| **Total Properties** | 🏠 | Rose/Rouge | Nombre total d'annonces publiées par l'hôte |
| **Total Bookings** | 📅 | Bleu | Nombre total de réservations (toutes annonces confondues) |
| **Total Revenue** | 💰 | Vert | Revenu total en MAD (hors réservations annulées) |

Ces valeurs sont calculées dynamiquement côté PHP à partir des données agrégées par la requête SQL.

> 📸 **[Insérer ici une capture d'écran de la barre de statistiques]**

#### Messages de Feedback (Alertes)

| Paramètre GET | Message affiché |
|---|---|
| `?deleted=1` | ✅ Listing deleted successfully. |
| `?updated=1` | ✅ Listing updated successfully. |

#### État Vide

Si l'hôte n'a encore publié aucune annonce :
- Icône 🏠
- Message « No listings yet »
- **Bouton « Add Your First Listing »** → `host-dashboard.php`

#### Blocs d'Annonce (Listing Blocks)

Chaque annonce publiée par l'hôte est représentée par un bloc structuré en deux parties : un **en-tête d'annonce** et un **accordéon d'historique des locations**.

##### En-tête du Bloc

L'en-tête est composé de trois zones disposées horizontalement :

**Zone gauche — Photo :**
- Image principale de l'annonce (200px de largeur, hauteur automatique)
- Si aucune image n'est disponible, une image par défaut est affichée

**Zone centrale — Informations :**
- Titre de l'annonce (en gras, 19px)
- Localisation (icône pin + ville/pays)
- Méta-données en ligne :
  - 🏷️ Prix par nuit en MAD
  - 👥 Nombre maximum de voyageurs
  - 🛏️ Nombre de lits
  - 📅 Nombre total de réservations

**Zone droite — Revenus & Actions :**
- **Revenu total** généré par cette annonce (en vert, excluant les réservations annulées)
- **Bouton « Edit »** (bleu) → redirige vers `edit-listing.php?id={id}`
- **Bouton « Delete »** (rouge) → ouvre une modale de confirmation de suppression

> 📸 **[Insérer ici une capture d'écran d'un bloc annonce complet avec ses boutons]**

##### Modale de Confirmation de Suppression

Lorsque l'hôte clique sur le bouton **« Delete »**, une modale de confirmation s'affiche avant toute action irréversible :

- Icône 🗑️
- Titre : « Delete this listing? »
- Message : « Are you sure you want to delete "{titre}" ? All bookings and photos will be permanently removed. »
- **Bouton « Keep it »** → ferme la modale sans action
- **Bouton « Yes, delete »** (rouge) → redirige vers `api/delete-listing.php?id={id}` pour suppression définitive

La logique de suppression (`api/delete-listing.php`) vérifie obligatoirement que l'annonce appartient bien à l'hôte connecté avant d'exécuter la requête SQL :

```sql
DELETE FROM listings WHERE id = ? AND user_id = ?
```

Grâce à la contrainte `ON DELETE CASCADE` définie dans le schéma, la suppression d'une annonce entraîne automatiquement la suppression de toutes ses images, équipements et réservations associées. Après suppression réussie, l'hôte est redirigé vers `my-listings.php?deleted=1`.

#### Accordéon — Historique des Locations

Chaque bloc d'annonce dispose d'un **accordéon repliable** affichant l'historique complet des réservations liées à cette propriété. L'accordéon est fermé par défaut et s'ouvre au clic sur le bouton toggle.

**Bouton Toggle :**

| Élément | Description |
|---|---|
| 👥 Icône | Icône utilisateurs |
| **« Rental History »** | Libellé du bouton |
| Badge rouge | Nombre de réservations associées à cette annonce |
| ∨ Chevron | S'inverse (↑) quand l'accordéon est ouvert |

**Tableau de l'Historique des Locations :**

Lorsque l'accordéon est développé, un tableau détaillé s'affiche avec les colonnes suivantes :

| Colonne | Description |
|---|---|
| **Guest** | Nom complet du locataire, son adresse email et son numéro de téléphone |
| **Check-in** | Date d'arrivée (format : JJ MMM AAAA) |
| **Check-out** | Date de départ (format : JJ MMM AAAA) |
| **Duration** | Nombre de nuits (calculé via `DATEDIFF`) affiché dans un badge gris arrondi |
| **Guests** | Nombre de voyageurs pour ce séjour |
| **Total Price** | Montant total en MAD payé pour ce séjour |
| **Status** | Badge de statut coloré de la réservation |

**Badges de Statut :**

| Statut | Couleur | Icône |
|---|---|---|
| **Confirmed** | Vert | ✅ fa-check-circle |
| **Pending** | Jaune/Ambre | 🕐 fa-clock |
| **Cancelled** | Rouge | ❌ fa-times-circle |

Si aucune réservation n'a encore été effectuée pour cette propriété, un message « No bookings for this property yet. » est affiché à la place du tableau.

> 📸 **[Insérer ici une capture d'écran de l'accordéon ouvert avec le tableau de l'historique des locations]**

La requête SQL qui alimente cet historique est exécutée pour chaque annonce lors du rendu de la page :

```sql
SELECT r.id, r.guest_name, r.guest_email, r.guest_phone,
       r.check_in, r.check_out, r.guests, r.total_price, r.status,
       DATEDIFF(day, r.check_in, r.check_out) AS nights
FROM reservations r
WHERE r.listing_id = ?
ORDER BY r.check_in DESC
```

---

## 8.14 Modification d'une Annonce — `edit-listing.php`

### Description Générale

Accessible via `edit-listing.php?id={identifiant_annonce}`, cette page permet à un hôte de modifier les informations d'une de ses annonces existantes. Elle comporte un formulaire pré-rempli avec toutes les données actuelles de l'annonce, récupérées depuis la base de données.

**Deux conditions de sécurité** sont vérifiées à l'ouverture de la page :
1. L'utilisateur doit être connecté **et** avoir le statut d'hôte (`is_host = 1`).
2. L'annonce dont l'`id` est passé en paramètre doit **appartenir** à l'hôte connecté (vérification via `user_id = ?`).

Si l'une ou l'autre condition n'est pas satisfaite, l'utilisateur est redirigé vers `my-listings.php`.

```php
$sql = "SELECT * FROM listings WHERE id = ? AND user_id = ?";
$stmt = sqlsrv_query($conn, $sql, [$listing_id, $user_id]);
if (!$data) {
    header('Location: my-listings.php');
    exit;
}
```

### Éléments de l'Interface

#### Navigation

- **Logo StayHub** (rouge) → `index.php`
- **← Back to My Listings** → `my-listings.php`

#### En-tête de Page

- Titre : « Edit Listing »
- Sous-titre : « Update the details of **{titre de l'annonce}** »

#### Formulaire de Modification

Le formulaire est soumis en POST avec `enctype="multipart/form-data"` vers `api/update-listing.php`. Il contient un champ caché `listing_id` transmettant l'identifiant de l'annonce à modifier.

**Section 1 — Informations de Base :**

| Champ | Type | Pré-rempli | Description |
|---|---|---|---|
| `title` | Texte | ✅ | Titre de l'annonce |
| `location` | Texte | ✅ | Localisation du logement |
| `price` | Nombre | ✅ | Prix par nuit en MAD |
| `voyageur_count` | Nombre | ✅ | Nombre maximum de voyageurs |
| `bed_count` | Nombre | ✅ | Nombre de lits |
| `description` | Textarea | ✅ | Description complète du logement |

**Section 2 — Équipements :**

La grille des équipements reprend les mêmes options que lors de la création. Les équipements actuellement associés à l'annonce sont **pré-cochés** automatiquement, grâce à la requête préalable sur la table `amenities` :

```sql
SELECT name FROM amenities WHERE listing_id = ?
```

Les équipements disponibles sont : WiFi, Kitchen, Air Conditioning, Parking, Pool, TV, Washer, Gym.

**Section 3 — Photos :**

- Si une image principale est enregistrée en base de données, elle est affichée sous forme d'**aperçu de la photo actuelle** (120×90px, bords arrondis).
- Un message indique : « Upload new photos below to replace existing ones. »
- Une zone d'upload en pointillés permet de sélectionner de nouvelles photos (optionnel). Les images sélectionnées sont prévisualisées via `FileReader` JavaScript avant l'envoi.
- Si de nouvelles photos sont envoyées, les anciennes sont supprimées de la base et remplacées.

> 📸 **[Insérer ici une capture d'écran du formulaire de modification pré-rempli]**

**Boutons de Pied de Formulaire :**

| Bouton | Action |
|---|---|
| **Cancel** | Retourne vers `my-listings.php` sans effectuer de modification |
| **Save Changes** (rouge) | Soumet le formulaire vers `api/update-listing.php` |

### Logique de Traitement — `api/update-listing.php`

Le fichier `api/update-listing.php` traite la soumission du formulaire en plusieurs étapes sécurisées :

1. **Vérification de session** : L'utilisateur doit être connecté et hôte.
2. **Vérification de propriété** : L'annonce doit appartenir à l'hôte connecté.
3. **Mise à jour de l'annonce** :
```sql
UPDATE listings
SET title = ?, location = ?, price = ?, voyageur_count = ?, bed_count = ?, description = ?
WHERE id = ? AND user_id = ?
```
4. **Mise à jour des équipements** : Suppression puis ré-insertion des équipements cochés :
```sql
DELETE FROM amenities WHERE listing_id = ?
INSERT INTO amenities (listing_id, name) VALUES (?, ?)
```
5. **Remplacement des photos (optionnel)** : Si de nouveaux fichiers sont uploadés, les anciennes images sont supprimées de la base et les nouvelles sont sauvegardées dans le dossier `uploads/` avec un nom de fichier unique (`listing_{id}_{timestamp}_{index}.{ext}`). Seuls les formats `jpg`, `jpeg`, `png` et `webp` sont acceptés.
6. **Redirection finale** vers `my-listings.php?updated=1` avec un message de succès.

---

# 9. DESCRIPTION DES APIs ET LOGIQUE MÉTIER

## 9.1 Vue d'Ensemble des Endpoints

| Fichier API | Méthode | Description |
|---|---|---|
| `api/login.php` | POST | Authentification utilisateur |
| `api/signup.php` | POST | Inscription nouvel utilisateur |
| `api/logout.php` | GET | Déconnexion (destruction de session) |
| `api/add-listing.php` | POST | Création d'une nouvelle annonce |
| `api/update-listing.php` | POST | Modification d'une annonce existante (hôte) |
| `api/delete-listing.php` | GET | Suppression d'une annonce par l'hôte (avec vérification de propriété) |
| `api/process-booking.php` | POST | Traitement d'une réservation |
| `api/process-payment.php` | POST | Confirmation du paiement |
| `api/cancel-booking.php` | POST | Annulation d'une réservation |
| `admin/actions/ban-user.php` | POST | Bannissement d'un utilisateur |
| `admin/actions/unban-user.php` | GET | Réactivation d'un compte banni |
| `admin/actions/delete-user.php` | POST | Suppression définitive d'un compte |
| `admin/actions/flag-listing.php` | POST | Signalement d'une annonce |
| `admin/actions/unflag-listing.php` | GET | Levée du signalement |
| `admin/actions/delete-listing.php` | POST | Suppression d'une annonce |

## 9.2 Flux de Réservation Complet

Le flux de réservation est le processus central de la plateforme. Voici sa description étape par étape :

```
Étape 1 : Découverte
  Utilisateur browse index.php
  → Clique sur une annonce → listing.php?id=X

Étape 2 : Authentification
  [Si non connecté] → Modal login/signup → session créée

Étape 3 : Réservation
  Clique "Reserve" → Modal de réservation s'ouvre
  Remplit : nom, email, téléphone, check-in, check-out
  Soumet → POST vers api/process-booking.php

Étape 4 : Validation (api/process-booking.php)
  ├── Vérifie que l'utilisateur est connecté
  ├── Vérifie la disponibilité des dates (pas de conflit)
  ├── Calcule le prix total (prix/nuit × nombre de jours)
  └── INSERT INTO reservations (status = 'pending')
  → Redirige vers my-rentals.php

Étape 5 : Paiement
  Sur my-rentals.php → Bouton "Pay Now" → payment.php?id=X
  Saisit les informations de carte (simulé)
  Soumet → POST vers api/process-payment.php

Étape 6 : Confirmation (api/process-payment.php)
  ├── Vérifie la réservation (pending + appartient à l'user)
  └── UPDATE reservations SET status = 'confirmed'
  → Redirige vers receipt.php?id=X

Étape 7 : Reçu
  Affichage du reçu officiel
  Bouton "Imprimer le reçu" → window.print()
```

## 9.3 Vérification de Disponibilité

Avant d'insérer une réservation, le système vérifie l'absence de conflits de dates :

```sql
SELECT COUNT(*) as count FROM reservations
WHERE listing_id = ?
AND (CAST(? AS DATE) < check_out AND CAST(? AS DATE) > check_in)
AND status != 'cancelled'
```

Cette requête implémente la **logique d'intersection d'intervalles** : deux périodes [A, B] et [C, D] se chevauchent si et seulement si A < D ET C < B. Si le compteur retourne > 0, l'erreur `already_booked` est retournée.

## 9.4 Calcul du Prix Total

```php
$date1 = new DateTime($check_in);
$date2 = new DateTime($check_out);
$interval = $date1->diff($date2);
$days = $interval->days > 0 ? $interval->days : 1;
$total_price = $daily_price * $days;
```

Sur la page de paiement, une décomposition détaillée est affichée :
- **Base** = `total_price - cleaning_fee - service_fee`
- **Frais de ménage** = 150 MAD (fixe)
- **Frais de service** = 10% du montant total
- **Total** = prix de base + frais de ménage + frais de service

---

# 10. SÉCURITÉ ET GESTION DES SESSIONS

## 10.1 Authentification

La plateforme StayHub utilise plusieurs mécanismes de sécurité pour protéger les comptes utilisateurs :

### Hashage des Mots de Passe

Les mots de passe sont hashés avec `password_hash()` utilisant l'algorithme **bcrypt** (PASSWORD_DEFAULT en PHP 7+) :

```php
// Lors de l'inscription
$hashed = password_hash($_POST['password'], PASSWORD_DEFAULT);

// Lors de la connexion
if (password_verify($_POST['password'], $stored_hash)) {
    // Connexion réussie
}
```

Bcrypt intègre automatiquement un "sel" (salt) aléatoire et est intentionnellement lent, rendant les attaques par force brute extrêmement coûteuses.

### Protection contre les Comptes Bannis

Lors de la connexion, le système vérifie le statut du compte :

```php
if ($user['is_banned']) {
    $error = "Votre compte a été suspendu : " . $user['ban_reason'];
    // Ne crée pas de session
}
```

## 10.2 Prévention des Injections SQL

Toutes les requêtes SQL utilisent des **requêtes préparées** avec le driver `sqlsrv`, protégeant contre les injections SQL :

```php
// Méthode sécurisée avec paramètres liés
$sql = "SELECT * FROM users WHERE email = ?";
$stmt = sqlsrv_query($conn, $sql, array($email));
```

Les valeurs utilisateur ne sont jamais concaténées directement dans les requêtes SQL.

## 10.3 Protection XSS

Toutes les données affichées provenant de la base de données sont filtrées avec `htmlspecialchars()` :

```php
echo htmlspecialchars($user['name']);
echo htmlspecialchars($listing['title']);
```

Cette fonction convertit les caractères spéciaux HTML (`<`, `>`, `&`, `"`, `'`) en entités HTML, empêchant l'exécution de code JavaScript malveillant injecté.

## 10.4 Contrôle d'Accès par Rôle (RBAC)

Chaque page protégée vérifie le rôle de l'utilisateur avant d'afficher son contenu :

| Page | Vérification |
|---|---|
| `my-rentals.php` | `isset($_SESSION['user_id'])` |
| `payment.php` | `isset($_SESSION['user_id'])` |
| `receipt.php` | `isset($_SESSION['user_id'])` |
| `profile.php` | `isset($_SESSION['user_id'])` |
| `host-dashboard.php` | `is_host = 1` |
| `become-host.php` | `isset($_SESSION['user_id'])` |
| `admin/*.php` | `is_admin = 1` |

---

# 11. FLUX FONCTIONNELS

## 11.1 Cas d'Utilisation — Visiteur

```
Visiteur
  ├── Parcourir les annonces (index.php)
  ├── Rechercher par destination
  ├── Consulter le détail d'une annonce (listing.php)
  └── Se connecter / S'inscrire (modales)
```

## 11.2 Cas d'Utilisation — Utilisateur Connecté

```
Utilisateur
  ├── Tout ce que peut faire un Visiteur
  ├── Réserver un logement
  ├── Payer une réservation (payment.php)
  ├── Annuler une réservation (api/cancel-booking.php)
  ├── Consulter son reçu (receipt.php)
  ├── Gérer son profil (profile.php)
  └── Devenir hôte (become-host.php)
```

## 11.3 Cas d'Utilisation — Hôte

```
Hôte
  ├── Tout ce que peut faire un Utilisateur
  └── Créer et publier des annonces (host-dashboard.php)
```

## 11.4 Cas d'Utilisation — Administrateur

```
Administrateur
  ├── Tout ce que peut faire un Utilisateur
  ├── Accéder au tableau de bord admin (admin/index.php)
  ├── Voir les statistiques globales
  ├── Gérer les utilisateurs (users.php)
  │   ├── Bannir un compte
  │   ├── Réactiver un compte banni
  │   └── Supprimer définitivement un compte
  └── Gérer les annonces (listings.php)
      ├── Signaler une annonce
      ├── Lever un signalement
      └── Supprimer une annonce
```

## 11.5 Diagramme de Séquence — Processus de Réservation

```
Utilisateur    index.php    listing.php    api/process-booking.php    my-rentals.php
    │               │              │                   │                     │
    │──browse──────>│              │                   │                     │
    │<──affiche─────│              │                   │                     │
    │──clique card─>│              │                   │                     │
    │               │──redirect───>│                   │                     │
    │<──────────────────affiche────│                   │                     │
    │──clique Reserve──────────────│                   │                     │
    │<───────────────ouvre modal───│                   │                     │
    │──remplit formulaire──────────│                   │                     │
    │──soumet POST─────────────────────────────────────>│                     │
    │                              │     vérifie dates │                     │
    │                              │     calcule prix  │                     │
    │                              │     INSERT résev. │                     │
    │<──────────────────────────────────────redirect────────────────────────>│
    │                              │                   │     affiche séjours │
```

---

# 12. CONCLUSION ET PERSPECTIVES

## 12.1 Bilan du Projet

Le projet StayHub représente une implémentation complète et fonctionnelle d'une plateforme de location de logements entre particuliers. Au terme de ce développement, les objectifs fixés en introduction ont été atteints :

**Sur le plan fonctionnel :**
- ✅ Système d'inscription et d'authentification sécurisé (bcrypt, sessions PHP)
- ✅ Parcours complet de réservation : recherche → sélection → réservation → paiement → reçu
- ✅ Gestion du cycle de vie des réservations (pending → confirmed / cancelled)
- ✅ Processus structuré pour devenir hôte avec acceptation des conditions
- ✅ Tableau de bord hôte pour la création et publication d'annonces
- ✅ Panneau d'administration complet (gestion des utilisateurs et des annonces)
- ✅ Génération de reçus de paiement officiels et imprimables

**Sur le plan technique :**
- ✅ Base de données relationnelle normalisée (6 tables, contraintes d'intégrité)
- ✅ Architecture PHP claire avec séparation des responsabilités
- ✅ Requêtes préparées pour la prévention des injections SQL
- ✅ Protection XSS via htmlspecialchars()
- ✅ Contrôle d'accès basé sur les rôles (visiteur / utilisateur / hôte / admin)
- ✅ Interface responsive avec CSS moderne et animations

**Sur le plan UX/UI :**
- ✅ Design inspiré des plateformes leaders (Airbnb, Booking.com)
- ✅ Couleur principale rouge (#ff385c) — charte graphique cohérente
- ✅ Police Inter (Google Fonts) — typographie moderne
- ✅ Animations CSS (modales, cartes au survol, transitions)
- ✅ Messages de feedback contextuels (succès, erreurs)

## 12.2 Difficultés Rencontrées

Au cours du développement, plusieurs défis techniques ont été rencontrés et résolus :

1. **Compatibilité SQL Server / MySQL** : Le projet utilise SQL Server Express (via le driver `sqlsrv`) plutôt que MySQL standard. Certaines fonctions SQL diffèrent (ex : `GETDATE()` au lieu de `NOW()`, `TOP N` au lieu de `LIMIT N`), nécessitant une adaptation des requêtes.

2. **Stockage des images** : Les avatars utilisateurs sont stockés en base de données sous forme de LONGBLOB, puis encodés en base64 pour l'affichage HTML. Cette approche évite les problèmes de gestion des fichiers mais peut impacter les performances pour des fichiers volumineux.

3. **Vérification de disponibilité** : L'implémentation correcte de la vérification de chevauchement de dates (logique d'intersection d'intervalles) a nécessité une analyse mathématique rigoureuse pour éviter les fausses disponibilités.

4. **Gestion des modales imbriquées** : La coexistence de plusieurs modales sur la même page (connexion, inscription, réservation) a nécessité une gestion soignée des z-index CSS et des événements JavaScript (stopPropagation).

## 12.3 Perspectives d'Amélioration

La version actuelle de StayHub constitue une base solide qui pourrait être enrichie par les fonctionnalités suivantes :

**Fonctionnalités utilisateur :**
- Système d'avis et de notes par les voyageurs après séjour
- Messagerie interne entre hôtes et voyageurs
- Système de favoris pour sauvegarder des annonces
- Filtres avancés (prix min/max, équipements, type de logement)
- Carte interactive (Google Maps / Leaflet) pour visualiser les annonces
- Notifications email (confirmation de réservation, rappel de séjour)

**Fonctionnalités hôte :**
- Tableau de bord hôte complet (liste des réservations entrantes, revenus)
- Gestion du calendrier de disponibilité
- Modification et suppression des annonces existantes
- Statistiques de performance des annonces

**Sécurité et performance :**
- Intégration d'un vrai système de paiement (Stripe, PayPal)
- Protection CSRF sur tous les formulaires POST
- Rate limiting pour les tentatives de connexion
- Pagination des listes (annonces, utilisateurs, réservations)
- Cache des images et optimisation des requêtes

**Administration :**
- Tableau de bord avec graphiques statistiques (Chart.js)
- Export des données en CSV/Excel
- Système de signalement par les utilisateurs
- Journal d'activité (logs des actions admin)

## 12.4 Conclusion Générale

StayHub illustre la mise en pratique des compétences fondamentales du développement web full-stack : conception de base de données relationnelle, développement back-end sécurisé en PHP, création d'interfaces utilisateur modernes et mise en place d'une architecture applicative cohérente.

Ce projet démontre qu'il est possible de développer, avec des technologies accessibles et open-source, une plateforme fonctionnelle inspirée des meilleures pratiques de l'industrie. La séparation claire entre la logique métier (fichiers API), la présentation (pages PHP/HTML) et les données (SQL Server) pose les fondations d'une application maintenable et extensible.

Le développement de StayHub a été une expérience formatrice permettant de confronter les concepts théoriques (modélisation des données, patrons de conception, sécurité web) à la réalité pratique du développement d'une application complète.

---

## ANNEXES

### Annexe A — Fichier de Configuration Base de Données

```php
// config.php
sqlsrv_configure("WarningsReturnAsErrors", 0);

$serverName = "DESKTOP-9LJFEUO\\SQLEXPRESS";
$connectionInfo = array(
    "Database"               => "stayhub",
    "TrustServerCertificate" => true,
    "CharacterSet"           => "UTF-8"
);

$conn = sqlsrv_connect($serverName, $connectionInfo);

if ($conn === false) {
    echo "Connection Failure:<br>";
    die(print_r(sqlsrv_errors(), true));
}
```

### Annexe B — Récapitulatif des Tables de la Base de Données

| Table | Colonnes | Clé Primaire | Clés Étrangères |
|---|---|---|---|
| `users` | 11 | id | — |
| `listings` | 16 | id | user_id → users(id) |
| `images` | 4 | id | listing_id → listings(id) |
| `amenities` | 3 | id | listing_id → listings(id) |
| `reservations` | 12 | id | listing_id → listings(id), user_id → users(id) |
| `payments` | 6 | id | reservation_id → reservations(id) |
| `invoices` | 6 | id | payment_id → payments(id) |

### Annexe C — Récapitulatif des Interfaces

| Interface | Fichier | Rôle requis | Authentification |
|---|---|---|---|
| Accueil | index.php | Tous | Non requise |
| Détail annonce | listing.php | Tous | Non requise (réservation requiert auth) |
| Connexion | api/login.php (modale) | Tous | Non requise |
| Inscription | api/signup.php (modale) | Tous | Non requise |
| Devenir hôte | become-host.php | Utilisateur | Requise |
| Tableau de bord hôte | host-dashboard.php | Hôte | Requise |
| Mes séjours | my-rentals.php | Utilisateur | Requise |
| Paiement | payment.php | Utilisateur | Requise |
| Reçu | receipt.php | Utilisateur | Requise |
| Profil | profile.php | Utilisateur | Requise |
| Admin — Dashboard | admin/index.php | Administrateur | Requise |
| Admin — Utilisateurs | admin/users.php | Administrateur | Requise |
| Admin — Annonces | admin/listings.php | Administrateur | Requise |

### Annexe D — Dictionnaire des Données

| Terme | Définition |
|---|---|
| **Hôte** | Utilisateur ayant accepté les conditions d'hébergement et pouvant publier des annonces |
| **Annonce** (Listing) | Offre de location d'un logement publiée par un hôte |
| **Réservation** | Demande de séjour effectuée par un voyageur pour une annonce donnée |
| **Statut Pending** | Réservation en attente de paiement |
| **Statut Confirmed** | Réservation payée et confirmée |
| **Statut Cancelled** | Réservation annulée par le voyageur |
| **Flag** | Signalement d'une annonce par l'administrateur pour violation des règles |
| **Ban** | Suspension du compte d'un utilisateur par l'administrateur |
| **MAD** | Dirham marocain — devise utilisée sur la plateforme |
| **Check-in** | Date d'arrivée dans le logement |
| **Check-out** | Date de départ du logement |
| **MCD** | Modèle Conceptuel de Données |
| **MLD** | Modèle Logique de Données |
| **CRUD** | Create, Read, Update, Delete — opérations de base sur les données |
| **Bcrypt** | Algorithme de hashage des mots de passe |
| **Session** | Mécanisme PHP de conservation de l'état utilisateur entre les pages |
| **Requête préparée** | Requête SQL avec paramètres liés, protégeant contre les injections SQL |
| **XSS** | Cross-Site Scripting — type d'attaque par injection de code JavaScript |
| **RBAC** | Role-Based Access Control — contrôle d'accès basé sur les rôles |

---

*Rapport généré le 11 Mai 2026 — StayHub v1.0*  
*Plateforme de location de logements entre particuliers — Développée avec PHP & SQL Server*

