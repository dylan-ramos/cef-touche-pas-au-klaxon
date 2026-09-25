# Touche pas au klaxon

Application intranet de covoiturage inter-sites. Elle diffuse au sein de l'entreprise les trajets prévus entre agences afin d'augmenter le taux d'occupation des véhicules.

## Fonctionnalités

| Profil | Possibilités |
| --- | --- |
| Visiteur | Consulter les trajets à venir disposant de places (départ, destination, dates, heures, places disponibles), triés par date de départ |
| Employé connecté | Voir le détail d'un trajet (auteur, téléphone, e-mail, nombre total de places) ; proposer un trajet ; modifier et supprimer ses propres trajets |
| Administrateur | Tableau de bord ; liste des utilisateurs ; création, modification et suppression des agences ; liste de tous les trajets et suppression |

Chaque opération d'écriture redirige vers la liste concernée avec un message de confirmation. Les employés proviennent du système d'information RH : l'application ne les crée, ne les modifie ni ne les supprime.

## Technologies

- PHP 8.3, architecture MVC, routeur [`izniburak/router`](https://packagist.org/packages/izniburak/router), Symfony HttpFoundation
- MySQL 8.4 (compatible MariaDB 11.4), accès PDO par requêtes préparées
- Bootstrap 5.3, Bootstrap Icons et Sass (palette définie par les variables Bootstrap)
- PHPUnit 12, PHPStan 2 (niveau max, règles strictes), PHP_CodeSniffer (PSR-12 et DocBlock obligatoires), phpDocumentor
- Docker Compose (Apache + PHP, MySQL, Node pour la compilation Sass)

## Installation

### Prérequis

- Docker avec Compose v2
- `make` et Git

### Étapes

```bash
git clone https://github.com/dylan-ramos/cef-touche-pas-au-klaxon.git
cd cef-touche-pas-au-klaxon
make install
```

`make install` :

1. crée `.env` à partir de `.env.example` ;
2. construit et démarre les conteneurs ; au premier démarrage, MySQL exécute `database/01_schema.sql`, `database/02_seed.sql` et crée les comptes MySQL de l'application ;
3. installe les dépendances PHP (Composer) et front (npm) ;
4. compile les styles et copie les ressources front dans `public/assets/`.

L'application est alors disponible sur **<http://localhost:8085>**.

Avant un déploiement, modifier dans `.env` les mots de passe MySQL (`DB_PASSWORD`, `DB_TEST_PASSWORD`, `MYSQL_ROOT_PASSWORD`), puis passer `APP_ENV=prod`, `APP_DEBUG=false` et, en HTTPS, `SESSION_SECURE=true`. Le port se règle avec `APP_PORT`.

### Installation sans Docker

Serveur Apache (avec `mod_rewrite` et `mod_headers`) dont la racine web est `public/`, PHP 8.3 avec `pdo_mysql`, MySQL 8.4 :

```bash
composer install --no-dev
npm ci && npm run build
mysql -u root -p < database/01_schema.sql
mysql -u root -p < database/02_seed.sql
cp .env.example .env   # renseigner DB_HOST, DB_NAME, DB_USER, DB_PASSWORD
```

Créer un compte MySQL dédié limité à `SELECT, INSERT, UPDATE, DELETE` sur `touche_pas_au_klaxon` (voir `docker/mysql/10-app-users.sh`) et le renseigner dans `DB_USER` / `DB_PASSWORD`.

## Comptes de démonstration

| Profil | Identifiant | Mot de passe |
| --- | --- | --- |
| Administrateur | `admin@touche-pas-au-klaxon.fr` | `Admin#Klaxon2026` |
| Employé | `alexandre.martin@email.fr` | `Covoiturage#2026` |

Les 20 employés du jeu d'essais partagent le mot de passe `Covoiturage#2026`. Le compte `alexandre.martin@email.fr` est l'auteur de plusieurs trajets à venir : il permet de tester la modification et la suppression.

## Utilisation

| Commande | Effet |
| --- | --- |
| `make up` / `make down` | Démarre / arrête les conteneurs (les données sont conservées) |
| `make ps`, `make logs` | État et journaux des conteneurs |
| `make sh` | Terminal dans le conteneur applicatif |
| `make db-reset` | Recrée le schéma et recharge le jeu d'essais (confirmation demandée) |
| `make db-shell` | Console MySQL |
| `make css` / `make css-watch` | Compile les styles Sass |
| `make docs` | Génère la documentation technique dans `var/docs/index.html` |
| `make help` | Liste toutes les commandes |

## Base de données

| Fichier | Rôle |
| --- | --- |
| `database/01_schema.sql` | Création de la base `touche_pas_au_klaxon`, des tables, contraintes et index (rejouable) |
| `database/02_seed.sql` | Jeu d'essais : 12 agences, 20 employés, 1 administrateur, 16 trajets |
| `database/03_checks.sql` | Requêtes de contrôle (volumétrie, liste d'accueil) |

Les dates des trajets de démonstration sont calculées à partir de la date d'import : le jeu d'essais contient toujours des trajets à venir, des trajets complets et des trajets passés.

Conception : [MCD](docs/conception/mcd.md) ([image](docs/conception/mcd.png)), [MLD](docs/conception/mld.md), [dictionnaire des données](docs/conception/dictionnaire.md).

## Qualité et tests

```bash
make lint       # PSR-12 et DocBlock (PHP_CodeSniffer)
make stan       # analyse statique PHPStan, niveau max + règles strictes
make test       # PHPUnit : suites Unit et Integration
make coverage   # rapport de couverture (var/coverage/index.html)
make verify     # lint + stan + test
```

Les tests d'intégration s'exécutent sur une base distincte (`DB_TEST_NAME`), rechargée à partir des scripts de `database/` ; chaque test est isolé dans une transaction annulée. Toutes les opérations d'écriture (dépôts et services) sont couvertes, y compris les conflits d'accès concurrents.

## Documentation

- [Architecture et réutilisation du socle](docs/architecture.md)
- [Sécurité : mesures et recette](docs/securite.md)
- Documentation technique des classes : `make docs`

## Dépannage

| Symptôme | Solution |
| --- | --- |
| Port 8085 déjà utilisé | Modifier `APP_PORT` dans `.env`, puis `make up` |
| Page sans mise en forme | `make css` |
| « Service inconnu » ou classe introuvable | `make composer-install` |
| Données de démonstration à réinitialiser | `make db-reset` |
| Mots de passe MySQL modifiés après le premier démarrage | Les comptes sont créés à l'initialisation du volume : `docker compose down -v` puis `make up` (efface les données) |
