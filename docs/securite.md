# Sécurité

Ce document recense les risques identifiés pour l'application et les mesures mises en œuvre côté serveur. Chaque mesure est vérifiée par des tests automatisés ou par la procédure de recette décrite en fin de document.

## Matrice des risques

| Risque | Mesures | Emplacement | Vérification |
| --- | --- | --- | --- |
| Injection SQL | Requêtes préparées PDO exclusivement (paramètres nommés, `ATTR_EMULATE_PREPARES=false`) ; aucune donnée externe concaténée au SQL | `Core\Data\AbstractRepository`, `Core\Database` | Tests d'intégration des dépôts ; saisie de `' OR 1=1 --` dans les formulaires |
| Injection HTML / XSS | Échappement systématique des sorties par `e()` (`htmlspecialchars`, `ENT_QUOTES`, UTF-8) ; gabarits sans concaténation de données non échappées ; Content-Security-Policy sans script ni style en ligne | `Core/helpers.php`, `templates/`, `public/.htaccess` | `HelpersTest`, `ViewTest`, `LayoutTest` ; création d'une agence `<script>` refusée par le validateur |
| Falsification de requête (CSRF) | Jeton aléatoire de 256 bits par session, vérifié par le noyau sur **toute** requête POST (réponse 419) ; jeton renouvelé à la connexion ; aucune écriture via GET ; cookie `SameSite=Lax` | `Core\Security\Csrf`, `Core\Kernel` | `CsrfTest` ; POST sans jeton ou avec l'ancien jeton refusé |
| Vol ou fixation de session | Cookie `HttpOnly`, `SameSite=Lax`, `Secure` en HTTPS (`SESSION_SECURE`), mode strict ; identifiant régénéré à la connexion ; destruction complète à la déconnexion ; seul l'identifiant utilisateur est stocké | `Core\Session\NativeSession`, `Security\Auth` | `AuthTest` ; comparaison des cookies avant et après connexion |
| Force brute sur la connexion | Mots de passe hachés en bcrypt (coût 12) ; 5 échecs par couple e-mail / adresse IP bloquent les tentatives 15 minutes (429) ; compteurs stockés sous forme d'empreintes | `Core\Security\Throttle`, `AuthController` | `LoginThrottleTest`, `AuthControllerTest` |
| Énumération des comptes | Message d'échec unique ; vérification d'une empreinte factice pour un e-mail inconnu (temps de réponse constant) | `Security\Auth` | `AuthControllerTest` |
| Élévation de privilèges | Gardes de route côté serveur (`guardUser`, `guardAdmin`) ; droits sur chaque trajet contrôlés par le service (auteur pour la modification, auteur ou administrateur pour la suppression) ; auteur d'un trajet toujours issu de la session, jamais du formulaire | `config/routes.php`, `Service\TripService` | `TripServiceTest`, `AuthTest`, `TripControllerTest` (champ auteur forgé ignoré) |
| Référence directe à un objet | Identifiants d'URL limités à des entiers positifs ≤ 4 294 967 295 ; existence vérifiée (404) avant tout contrôle de droit ou écriture | `Core\Routing\ControllerDispatcher`, services | `ControllerDispatcherTest` |
| Données incohérentes | Validation serveur de chaque champ (types, bornes, cohérence) doublée de contraintes `CHECK`, `UNIQUE` et de clés étrangères en base ; violations concurrentes converties en erreur de formulaire | `Validator\*`, `database/01_schema.sql` | `TripValidatorTest`, `AgencyValidatorTest`, tests de concurrence des services |
| Fuite d'informations techniques | Pages d'erreur génériques (400, 403, 404, 405, 419, 500) ; détails uniquement journalisés, affichés seulement si `APP_DEBUG=true` ; version d'Apache et de PHP masquées ; méthode TRACE désactivée | `Core\ErrorHandler`, `docker/php/` | `ErrorHandlerTest` |
| Détournement d'affichage (clickjacking) | `X-Frame-Options: DENY` et `frame-ancestors 'none'` | `public/.htaccess` | En-têtes de réponse |
| Privilèges excessifs en base | Compte MySQL applicatif limité à `SELECT, INSERT, UPDATE, DELETE` sur la seule base de l'application ; compte d'administration réservé à l'initialisation | `docker/mysql/10-app-users.sh` | `SHOW GRANTS` ; `DROP TABLE` refusé (erreur 1142) |
| Secrets exposés | Configuration par variables d'environnement ; seul `.env.example` est versionné | `.gitignore`, `Core\Config` | Revue du dépôt |
| Dépendances vulnérables | Versions verrouillées (`composer.lock`, `package-lock.json`) ; audits `composer audit` et `npm audit` | Racine du dépôt | Aucun avis de sécurité à la date de publication |

## En-têtes HTTP

Définis dans `public/.htaccess`, donc appliqués par tout serveur Apache hébergeant l'application :

```text
Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; font-src 'self';
                         connect-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'; object-src 'none'
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Referrer-Policy: same-origin
Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()
Cross-Origin-Opener-Policy: same-origin
```

Toutes les ressources (Bootstrap, icônes, scripts) sont servies par l'application ; aucune ressource externe n'est chargée.

## Mise en production

- `APP_ENV=prod` et `APP_DEBUG=false`.
- Mots de passe MySQL propres à l'environnement dans `.env` (jamais les valeurs d'exemple).
- Service derrière HTTPS et `SESSION_SECURE=true`.
- Mots de passe de démonstration du jeu d'essais remplacés avant toute ouverture aux utilisateurs.

## Recette de sécurité

| Scénario | Résultat attendu |
| --- | --- |
| `' OR 1=1 --` comme e-mail de connexion | 422, e-mail invalide |
| Nom d'agence `<script>alert(1)</script>` | 422, caractères non autorisés ; rien n'est exécuté |
| POST sans jeton CSRF ou avec un jeton périmé | 419 |
| Employé sur `/admin/*` (GET ou POST) | 403 |
| Visiteur sur une page réservée | Redirection vers `/login` |
| Modification ou suppression du trajet d'un autre employé par URL directe | 403 |
| Identifiant non numérique ou inexistant dans l'URL | 404 |
| Méthode HTTP inattendue (GET `/logout`) | 405 avec en-tête `Allow` |
| Six tentatives de connexion erronées | 429 à la sixième, y compris avec le bon mot de passe |
| Suppression d'une agence utilisée par un trajet | Refus avec message explicite, agence conservée |
