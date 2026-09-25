# Architecture

## Vue d'ensemble

L'application suit une architecture **MVC** (Modèle – Vue – Contrôleur) construite sur un noyau réutilisable.

```text
Navigateur
   │  HTTP
   ▼
Apache (racine web : public/)
   │  toute adresse inconnue → public/index.php (contrôleur frontal)
   ▼
App\Core\Kernel
   ├─ configuration (.env) et conteneur de services (config/services.php)
   ├─ vérification du jeton CSRF de toute requête POST
   └─ routeur izniburak/router (config/routes.php)
         │
         ▼
   ControllerDispatcher ── gardes d'accès (visiteur / employé / administrateur)
         │
         ▼
   Contrôleur (App\Controller)          ← lit la requête, choisit la réponse
         │
         ├─ Validateur (App\Validator)   ← contrôle et normalise la saisie
         ├─ Service (App\Service)        ← règles de gestion et droits
         └─ Dépôt (App\Repository)       ← requêtes SQL préparées (PDO)
                    │
                    ▼
                 MySQL
         │
         ▼
   Vue (templates/)  → réponse HTML, ou redirection + message flash
```

Toute exception non traitée remonte au `Kernel`, qui la confie à `ErrorHandler` : page d'erreur adaptée au code HTTP (400, 403, 404, 405, 419, 500), sans détail technique hors mode débogage.

## Organisation du code

| Répertoire | Rôle |
| --- | --- |
| `public/` | Contrôleur frontal, `.htaccess` (réécriture et en-têtes de sécurité), ressources statiques |
| `config/` | Déclaration des services (`services.php`) et des routes (`routes.php`) |
| `src/Core/` | Noyau réutilisable, indépendant du métier |
| `src/Entity/` | Objets métier typés : `User`, `Agency`, `Trip`, `TripData`, `Role` |
| `src/Repository/` | Accès aux données : une classe par table |
| `src/Validator/` | Validation des formulaires (connexion, trajet, agence) |
| `src/Service/` | Règles de gestion : droits, enchaînement validation / écriture |
| `src/Security/` | Authentification et gardes d'accès |
| `src/Controller/` | Contrôleurs publics et d'administration (`Admin/`) |
| `templates/` | Vues PHP : mise en page, fragments (`partials/`) et pages |
| `resources/scss/` | Styles Sass ; la palette est isolée dans `_variables.scss` |
| `database/` | Scripts SQL de création, d'alimentation et de contrôle |
| `tests/` | Tests unitaires (`Unit/`) et d'intégration sur base de test (`Integration/`) |

## Noyau réutilisable (`App\Core`)

| Composant | Responsabilité |
| --- | --- |
| `Kernel` | Amorçage, contrôle CSRF, routage, gestion globale des erreurs |
| `Config` | Paramètres typés depuis `.env` et l'environnement |
| `Container` | Construction des services et de leurs dépendances |
| `Database` | Connexion PDO sécurisée, transactions |
| `Data\AbstractRepository`, `Data\Row` | Requêtes préparées, lecture typée des résultats, traduction des erreurs d'intégrité |
| `View`, `helpers.php` (`e()`) | Rendu des gabarits et échappement HTML |
| `Routing\ControllerDispatcher` | Liaison route → contrôleur, gardes, conversion des identifiants |
| `Session\*`, `Session\Flash` | Session sécurisée et messages affichés après redirection |
| `Security\Csrf`, `Security\Throttle\*` | Jetons de formulaire, limitation des tentatives de connexion |
| `Validation\*` | Lecture défensive des formulaires et erreurs par champ |
| `Clock\*` | Heure courante injectable (tests déterministes) |
| `Format` | Formats d'affichage (dates, heures, téléphones) |

## Créer un nouveau site intranet à partir de ce socle

1. Copier le dépôt et adapter `APP_NAME`, `APP_COPYRIGHT_HOLDER` et les paramètres de base de données dans `.env`.
2. Remplacer les six couleurs de `resources/scss/_variables.scss`, puis `make css` : tous les composants suivent la nouvelle palette.
3. Conserver `src/Core/`, `src/Security/`, `src/Entity/User.php` et `src/Repository/UserRepository.php` (référentiel des employés commun à l'intranet).
4. Ajouter les entités, dépôts, validateurs, services et contrôleurs du nouveau métier, puis les déclarer dans `config/services.php`.
5. Déclarer les routes dans `config/routes.php` avec la garde adaptée (`$guest`, `$user`, `$admin`).
6. Créer les vues dans `templates/` en réutilisant `layout.php` et les fragments existants.

## Parcours d'une requête : modification d'un trajet

1. `POST /trips/12` : le `Kernel` vérifie le jeton CSRF.
2. Le routeur associe la route à `TripController::update` ; la garde `guardUser` vérifie la connexion.
3. `ControllerDispatcher` convertit `12` en entier et récupère le contrôleur dans le conteneur.
4. `TripService::update` charge le trajet (404 s'il n'existe pas), vérifie que l'utilisateur en est l'auteur et que le trajet n'est pas parti (403 sinon).
5. `TripValidator` contrôle la saisie ; en cas d'erreur, le formulaire est réaffiché (422) avec les messages par champ.
6. `TripRepository::update` exécute la requête préparée.
7. Le contrôleur ajoute le message flash « Le trajet a été modifié. » et redirige vers la liste des trajets.
