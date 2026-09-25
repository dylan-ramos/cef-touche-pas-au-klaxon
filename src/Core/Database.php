<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use PDO;
use Throwable;

/**
 * Point d'accès unique à la base de données relationnelle.
 *
 * La connexion PDO est ouverte à la première utilisation et configurée de
 * façon sécurisée : exceptions systématiques, requêtes réellement préparées
 * côté serveur et encodage utf8mb4. Le nombre de lignes affectées par une
 * mise à jour compte les lignes trouvées, même inchangées.
 */
final class Database
{
    private ?PDO $pdo = null;

    /**
     * @param string $dsn      Chaîne de connexion PDO.
     * @param string $user     Utilisateur SGBD.
     * @param string $password Mot de passe SGBD.
     */
    public function __construct(
        private readonly string $dsn,
        private readonly string $user,
        private readonly string $password,
    ) {
    }

    /**
     * Construit l'accès MySQL à partir de la configuration.
     *
     * @param Config $config      Configuration de l'application.
     * @param string $nameKey     Paramètre contenant le nom de la base.
     * @param string $userKey     Paramètre contenant l'utilisateur.
     * @param string $passwordKey Paramètre contenant le mot de passe.
     *
     * @return self
     */
    public static function fromConfig(
        Config $config,
        string $nameKey = 'DB_NAME',
        string $userKey = 'DB_USER',
        string $passwordKey = 'DB_PASSWORD',
    ): self {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config->string('DB_HOST'),
            $config->int('DB_PORT', 3306),
            $config->string($nameKey),
        );

        return new self($dsn, $config->string($userKey), $config->string($passwordKey));
    }

    /**
     * Retourne la connexion PDO, ouverte à la demande.
     *
     * @return PDO
     */
    public function pdo(): PDO
    {
        return $this->pdo ??= new PDO($this->dsn, $this->user, $this->password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::MYSQL_ATTR_FOUND_ROWS => true,
        ]);
    }

    /**
     * Exécute un traitement dans une transaction : validée en cas de succès,
     * annulée puis relancée en cas d'exception.
     *
     * @template T
     *
     * @param Closure(PDO): T $operation Traitement recevant la connexion.
     *
     * @return T
     *
     * @throws Throwable Exception d'origine après annulation de la transaction.
     */
    public function transactional(Closure $operation): mixed
    {
        $pdo = $this->pdo();
        $pdo->beginTransaction();

        try {
            $result = $operation($pdo);
            $pdo->commit();

            return $result;
        } catch (Throwable $exception) {
            $pdo->rollBack();

            throw $exception;
        }
    }
}
