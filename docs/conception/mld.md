# Modèle logique des données (MLD)

Notation : clé primaire soulignée (`_id_`), clé étrangère précédée de `#`.

```text
agence      (_id_, nom, created_at, updated_at)

utilisateur (_id_, nom, prenom, telephone, email, mot_de_passe, role, created_at)

trajet      (_id_, #agence_depart_id, #agence_arrivee_id, date_heure_depart, date_heure_arrivee,
             places_totales, places_disponibles, #auteur_id, created_at, updated_at)
```

## Clés étrangères

| Table | Colonne | Référence | Suppression |
| --- | --- | --- | --- |
| trajet | agence_depart_id | agence(id) | RESTRICT |
| trajet | agence_arrivee_id | agence(id) | RESTRICT |
| trajet | auteur_id | utilisateur(id) | RESTRICT |

## Passage MCD → MLD

- Chaque entité devient une table ; son identifiant devient la clé primaire `id`.
- Les trois associations de type (0,n)–(1,1) migrent en clé étrangère dans `trajet`, côté (1,1).
- Les colonnes techniques `created_at` / `updated_at` assurent la traçabilité.

## Contraintes d'intégrité

| Nom | Table | Règle |
| --- | --- | --- |
| uq_agence_nom | agence | `nom` unique |
| ck_agence_nom_non_vide | agence | `nom` non vide après suppression des espaces |
| uq_utilisateur_email | utilisateur | `email` unique |
| ck_utilisateur_email | utilisateur | format minimal `x@y.z` |
| ck_utilisateur_telephone | utilisateur | 10 chiffres |
| ck_trajet_agences_distinctes | trajet | `agence_depart_id <> agence_arrivee_id` |
| ck_trajet_chronologie | trajet | `date_heure_arrivee > date_heure_depart` |
| ck_trajet_places_totales | trajet | `places_totales` entre 1 et 9 |
| ck_trajet_places_disponibles | trajet | `places_disponibles <= places_totales` (non signé, donc ≥ 0) |

## Index

| Nom | Table | Colonnes | Usage |
| --- | --- | --- | --- |
| idx_trajet_depart_places | trajet | date_heure_depart, places_disponibles | liste d'accueil filtrée et triée |
| (index des clés étrangères) | trajet | agence_depart_id, agence_arrivee_id, auteur_id | jointures et contrôle d'intégrité |

Le modèle physique correspondant est `database/01_schema.sql`.
