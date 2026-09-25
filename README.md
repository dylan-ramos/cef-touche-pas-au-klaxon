# Touche pas au klaxon

Application intranet de covoiturage inter-sites. Elle diffuse au sein de l'entreprise les trajets prévus entre agences afin d'augmenter le taux d'occupation des véhicules.

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
