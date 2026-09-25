# Touche pas au klaxon

Application intranet de covoiturage inter-sites. Elle diffuse au sein de l'entreprise les trajets prévus entre agences afin d'augmenter le taux d'occupation des véhicules.

## Démarrage rapide

Prérequis : Docker (Compose v2) et `make`.

```bash
make install        # crée .env, construit et démarre les conteneurs, installe les dépendances
```

L'application est servie sur <http://localhost:8085> (port modifiable via `APP_PORT` dans `.env`). `make help` liste toutes les commandes.

| Service | Rôle |
| --- | --- |
| `app` | PHP 8.3 + Apache, racine web `public/` |
| `db` | MySQL 8.4 ; au premier démarrage, exécute les scripts de `database/` et crée les comptes MySQL de l'application |

Le compte MySQL applicatif (`DB_USER`) ne dispose que des droits `SELECT`, `INSERT`, `UPDATE` et `DELETE` sur la base. Les tests d'intégration utilisent une base distincte (`DB_TEST_NAME`).

## Qualité

```bash
make lint           # norme PSR-12 (PHP_CodeSniffer)
make stan           # analyse statique PHPStan, niveau max + règles strictes
make test           # tests PHPUnit (suites Unit et Integration)
make verify         # l'ensemble des contrôles
```

## Base de données

Scripts dans `database/` (MySQL 8.4 ou MariaDB 11.4) :

| Fichier | Rôle |
| --- | --- |
| `01_schema.sql` | Création de la base `touche_pas_au_klaxon`, des tables, contraintes et index (rejouable) |
| `02_seed.sql` | Jeu d'essais : 12 agences, 20 employés, 1 administrateur, 16 trajets |
| `03_checks.sql` | Requêtes de contrôle de la volumétrie et de la liste d'accueil |

Import manuel :

```bash
mysql -u root -p < database/01_schema.sql
mysql -u root -p < database/02_seed.sql
mysql -u root -p < database/03_checks.sql
```

Conception : [MCD](docs/conception/mcd.md), [MLD](docs/conception/mld.md), [dictionnaire des données](docs/conception/dictionnaire.md).
