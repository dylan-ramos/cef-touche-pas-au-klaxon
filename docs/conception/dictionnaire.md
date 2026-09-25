# Dictionnaire des données

## agence

| Colonne | Type | Null | Contraintes | Description |
| --- | --- | --- | --- | --- |
| id | INT UNSIGNED | non | PK, AUTO_INCREMENT | Identifiant de l'agence |
| nom | VARCHAR(100) | non | UNIQUE, non vide | Ville de l'agence |
| created_at | DATETIME | non | défaut `CURRENT_TIMESTAMP` | Date de création |
| updated_at | DATETIME | non | mise à jour automatique | Date de dernière modification |

## utilisateur

| Colonne | Type | Null | Contraintes | Description |
| --- | --- | --- | --- | --- |
| id | INT UNSIGNED | non | PK, AUTO_INCREMENT | Identifiant de l'utilisateur |
| nom | VARCHAR(100) | non | | Nom de famille |
| prenom | VARCHAR(100) | non | | Prénom |
| telephone | VARCHAR(20) | non | 10 chiffres | Téléphone professionnel |
| email | VARCHAR(255) | non | UNIQUE | Identifiant de connexion |
| mot_de_passe | VARCHAR(255) | non | | Empreinte `password_hash()` |
| role | ENUM('user','admin') | non | défaut `user` | Profil d'autorisation |
| created_at | DATETIME | non | défaut `CURRENT_TIMESTAMP` | Date d'import |

## trajet

| Colonne | Type | Null | Contraintes | Description |
| --- | --- | --- | --- | --- |
| id | INT UNSIGNED | non | PK, AUTO_INCREMENT | Identifiant du trajet |
| agence_depart_id | INT UNSIGNED | non | FK agence, ≠ arrivée | Agence de départ |
| agence_arrivee_id | INT UNSIGNED | non | FK agence | Agence d'arrivée |
| date_heure_depart | DATETIME | non | | Groupe date-heure de départ |
| date_heure_arrivee | DATETIME | non | > départ | Groupe date-heure d'arrivée |
| places_totales | TINYINT UNSIGNED | non | 1 à 9 | Nombre total de places passagers |
| places_disponibles | TINYINT UNSIGNED | non | 0 à places_totales | Places encore libres |
| auteur_id | INT UNSIGNED | non | FK utilisateur | Employé ayant proposé le trajet, personne à contacter |
| created_at | DATETIME | non | défaut `CURRENT_TIMESTAMP` | Date de création |
| updated_at | DATETIME | non | mise à jour automatique | Date de dernière modification |
