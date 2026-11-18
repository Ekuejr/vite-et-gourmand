# Vite & Gourmand

Application web de vitrine et de commande de menus pour **Vite & Gourmand**, traiteur bordelais (Julie et José).
Projet réalisé dans le cadre de l'ECF du Titre Professionnel **Développeur Web et Web Mobile**.

- **Application en ligne** : `https://<votre-compte>.alwaysdata.net` *(à compléter après déploiement)*
- **Gestion de projet** : `https://trello.com/b/<id>` *(à compléter)*

## Sommaire

1. [Fonctionnalités](#fonctionnalités)
2. [Stack technique](#stack-technique)
3. [Architecture du projet](#architecture-du-projet)
4. [Installation en local](#installation-en-local)
5. [Comptes de démonstration](#comptes-de-démonstration)
6. [Tests](#tests)
7. [Sécurité](#sécurité)
8. [Déploiement](#déploiement)
9. [Workflow Git](#workflow-git)

## Fonctionnalités

| Profil | Fonctionnalités |
|---|---|
| Visiteur | Accueil (présentation, avis validés), vue globale des menus avec **filtres dynamiques sans rechargement**, détail d'un menu, contact, création de compte, mentions légales et CGV |
| Utilisateur | Connexion, mot de passe oublié, commande avec **prix calculé en direct** (minimum de personnes, remise de 10 %, livraison), suivi, modification et annulation avant acceptation, avis (1 à 5) sur une commande terminée, modification du profil |
| Employé | Gestion des commandes (filtre par statut et par client, statuts, annulation avec motif et mode de contact), menus, plats, allergènes, horaires, modération des avis |
| Administrateur | Tout ce que fait un employé, plus la création et la désactivation des comptes employés et des **statistiques par menu issues de MongoDB** (graphique, chiffre d'affaires filtrable par menu et par période) |

E-mails automatiques : bienvenue, réinitialisation du mot de passe, confirmation de commande, retour de matériel (pénalité de 600 €), commande terminée (invitation à laisser un avis), annulation, création d'un compte employé (sans le mot de passe), formulaire de contact.

## Stack technique

| Couche | Technologie | Pourquoi |
|---|---|---|
| Front | HTML5, CSS3, **Bootstrap 5** (auto-hébergé), JavaScript natif (`fetch`) | Responsive mobile-first, pas de framework lourd à télécharger (éco-conception), aucune requête vers un CDN tiers (RGPD) |
| Back | **PHP 8.2+**, architecture **MVC** maison, **PDO** | Chaque mécanisme (routeur, sécurité, accès aux données) est écrit et compris, sans « magie » de framework |
| BDD relationnelle | **MySQL 8 / MariaDB 10.6+** | Données structurées et liées (commandes, menus, utilisateurs) avec intégrité référentielle et transactions |
| BDD NoSQL | **MongoDB** (Atlas) | Statistiques dénormalisées, agrégées par menu et par période (pipeline `$match` / `$group`) |
| Mails | PHPMailer (SMTP) avec repli sur `mail()` | Envoi authentifié et fiable |
| Graphiques | Chart.js (auto-hébergé) | Graphique de comparaison des menus |
| Hébergement | alwaysdata (PHP + MariaDB, en France) + MongoDB Atlas | Offres gratuites, HTTPS automatique, données hébergées en Europe |

## Architecture du projet

```
vite-et-gourmand/
├── public/                 # SEUL dossier exposé au web (racine du site)
│   ├── index.php           # Contrôleur frontal : en-têtes de sécurité + routes
│   ├── .htaccess           # Réécriture d'URL, HTTPS, cache
│   ├── assets/             # css, js, images, polices, vendor (Bootstrap, Chart.js)
│   └── uploads/            # Images téléversées (exécution de scripts interdite)
├── src/
│   ├── Core/               # Router, Database (PDO), Auth, Session, Csrf, Validator, View
│   ├── Controllers/        # Un contrôleur par espace fonctionnel
│   ├── Models/             # Accès aux données (requêtes préparées uniquement)
│   └── Services/           # Règles métier : PriceCalculator, OrderWorkflow, StatsService (MongoDB), Mailer…
├── views/                  # Gabarits PHP (sortie systématiquement échappée)
├── config/config.php       # Lecture du fichier .env
├── database/
│   ├── schema.sql          # Création des tables (SQL écrit à la main)
│   └── seed.sql            # Jeu de données de démonstration
├── tools/sync_mongo.php    # Synchronisation MySQL -> MongoDB
├── tests/                  # Tests unitaires (format PHPUnit)
└── docs/                   # Documentation PDF (manuel, charte, gestion de projet, technique)
```

## Installation en local

### Prérequis

- PHP 8.2 ou supérieur avec les extensions `pdo_mysql`, `mbstring`, `curl` et `mongodb` (pour les statistiques)
- MySQL 8 ou MariaDB 10.6+
- Facultatif : Composer (PHPMailer et PHPUnit), un compte MongoDB Atlas gratuit (ou MongoDB en local)

### Étapes

```bash
# 1. Cloner le dépôt
git clone https://github.com/Ekuejr/vite-et-gourmand.git
cd vite-et-gourmand

# 2. Créer la base et un utilisateur dédié (moindre privilège : pas de DROP/ALTER pour l'application)
mysql -u root -p -e "CREATE DATABASE vite_gourmand CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER 'vg_app'@'localhost' IDENTIFIED BY 'MotDePasseSolide#2026';
  GRANT SELECT, INSERT, UPDATE, DELETE ON vite_gourmand.* TO 'vg_app'@'localhost';"

# 3. Importer la structure puis les données
mysql -u root -p vite_gourmand < database/schema.sql
mysql -u root -p vite_gourmand < database/seed.sql

# 4. Configurer l'environnement
cp .env.example .env        # puis renseigner DB_*, MONGO_URI, MAIL_*
#    En local sans Internet : GEOCODER_FAKE_KM=12 simule la distance de livraison.
#    Sans serveur SMTP : laisser MAIL_HOST vide, les mails sont écrits dans storage/logs/mails.log

# 5. (Facultatif) Installer PHPMailer et PHPUnit
composer install

# 6. Alimenter MongoDB avec les commandes existantes
php tools/sync_mongo.php

# 7. Lancer le serveur de développement
php -S localhost:8000 -t public public/index.php
```

Ouvrir ensuite http://localhost:8000

## Comptes de démonstration

| Rôle | Identifiant | Mot de passe |
|---|---|---|
| Administrateur (José) | jose@vite-gourmand.fr | `Jose#Admin2026` |
| Employée (Julie) | julie@vite-gourmand.fr | `Julie#Employe2026` |
| Utilisateur | client@exemple.fr | `Client#Test2026` |
| Employé désactivé (test de refus) | ancien@vite-gourmand.fr | `Ancien#Employe2026` |

> Le compte administrateur n'est créé que par le script SQL : aucun formulaire de l'application ne permet de créer un administrateur.

## Tests

```bash
vendor/bin/phpunit --testdox tests   # avec Composer
php tests/run.php                    # sans Composer (lanceur intégré)
```

16 tests unitaires couvrent les règles de prix (minimum, remise, livraison), la politique de mot de passe, les validations et la machine à états des commandes.

## Sécurité

| Menace | Protection mise en place |
|---|---|
| Injection SQL | Requêtes préparées PDO (`EMULATE_PREPARES=false`), aucune concaténation de saisie utilisateur |
| XSS | Échappement systématique `htmlspecialchars` (fonction `e()`), DOM construit avec `textContent` en JS, Content-Security-Policy stricte (`script-src 'self'`) |
| CSRF | Jeton aléatoire par session vérifié sur **tous** les POST (`hash_equals`), cookie `SameSite=Lax` |
| Vol de session | Cookie `HttpOnly` et `Secure` (prod), `session_regenerate_id` à la connexion, mode strict |
| Mots de passe | `password_hash` (bcrypt) et `password_verify`, politique de 10 caractères avec 4 types de caractères, rehash automatique |
| Force brute | Blocage après 5 échecs en 15 minutes par adresse IP |
| Énumération de comptes | Messages identiques à la connexion et au « mot de passe oublié » |
| Réinitialisation | Jeton de 256 bits, stocké haché (SHA-256), usage unique, expiration après 1 heure |
| Contrôle d'accès / IDOR | Rôles vérifiés dans le routeur ; une commande n'est lue qu'avec `utilisateur_id = session` |
| Élévation de privilèges | Rôle fixé côté serveur, admin uniquement par SQL, désactivation effective immédiatement |
| Upload | Type MIME réel (`finfo`), 2 Mo max, nom aléatoire, exécution PHP interdite dans `uploads/` |
| Fuite d'information | Erreurs masquées en prod (journalisées), `.env` hors du dépôt et hors de la racine web |
| Clickjacking / sniffing | `X-Frame-Options: DENY`, `frame-ancestors 'none'`, `X-Content-Type-Options: nosniff`, HSTS |
| Données faussées | Prix toujours recalculé côté serveur, transitions de statut contrôlées, stock verrouillé (`SELECT … FOR UPDATE`) |

## Déploiement

Voir `docs/documentation-technique.pdf`, partie « Déploiement ». En résumé (alwaysdata) :

1. Créer un site PHP dont la racine est le dossier `public/`.
2. Créer la base MariaDB et un utilisateur, puis importer `schema.sql` et `seed.sql` (phpMyAdmin ou SSH).
3. Envoyer le code (`git clone` en SSH), créer le fichier `.env` en production (`APP_ENV=prod`).
4. Créer le cluster MongoDB Atlas M0, autoriser l'IP du serveur, renseigner `MONGO_URI`, puis lancer `php tools/sync_mongo.php`.
5. Activer le certificat HTTPS (Let's Encrypt) et vérifier tous les parcours.

## Workflow Git

- `main` : branche de production, toujours stable et déployée.
- `develop` : branche d'intégration.
- `feature/<nom>` : une branche par fonctionnalité, créée depuis `develop`, fusionnée dans `develop` après test (`git merge --no-ff`).
- Quand `develop` est testée : fusion dans `main`, puis déploiement.

Messages de commit au format *Conventional Commits* (`feat:`, `fix:`, `docs:`, `test:`…).
