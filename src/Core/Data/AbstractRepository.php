<?php

declare(strict_types=1);

namespace App\Core\Data;

use App\Core\Data\Exception\ConstraintViolationException;
use App\Core\Database;
use PDOException;
use PDOStatement;

/**
 * Base des dépôts d'accès aux données.
 *
 * Toutes les requêtes passent par des requêtes préparées avec paramètres
 * nommés ; aucune donnée externe n'est concaténée au SQL. Les violations de
 * contraintes sont traduites en exceptions typées.
 */
abstract class AbstractRepository
{
    /**
     * @param Database $database Accès à la base de données.
     */
    public function __construct(protected readonly Database $database)
    {
    }

    /**
     * Exécute une requête de lecture et retourne toutes les lignes.
     *
     * @param string                                  $sql    Requête SQL avec paramètres nommés.
     * @param array<string, int|string|bool|null>     $params Valeurs des paramètres.
     *
     * @return list<Row>
     */
    protected function fetchAll(string $sql, array $params = []): array
    {
        $rows = [];
        foreach ($this->run($sql, $params)->fetchAll() as $row) {
            if (is_array($row)) {
                $rows[] = new Row($row);
            }
        }

        return $rows;
    }

    /**
     * Exécute une requête de lecture et retourne la première ligne.
     *
     * @param string                              $sql    Requête SQL avec paramètres nommés.
     * @param array<string, int|string|bool|null> $params Valeurs des paramètres.
     *
     * @return Row|null Null si aucune ligne ne correspond.
     */
    protected function fetchOne(string $sql, array $params = []): ?Row
    {
        $row = $this->run($sql, $params)->fetch();

        return is_array($row) ? new Row($row) : null;
    }

    /**
     * Exécute une requête retournant une valeur entière unique (COUNT, EXISTS…).
     *
     * @param string                              $sql    Requête SQL avec paramètres nommés.
     * @param array<string, int|string|bool|null> $params Valeurs des paramètres.
     *
     * @return int
     */
    protected function fetchInt(string $sql, array $params = []): int
    {
        $value = $this->run($sql, $params)->fetchColumn();

        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * Exécute une requête d'écriture.
     *
     * @param string                              $sql    Requête SQL avec paramètres nommés.
     * @param array<string, int|string|bool|null> $params Valeurs des paramètres.
     *
     * @return int Nombre de lignes trouvées par la requête.
     *
     * @throws ConstraintViolationException Si une contrainte d'intégrité est violée.
     */
    protected function execute(string $sql, array $params = []): int
    {
        return $this->run($sql, $params)->rowCount();
    }

    /**
     * Retourne l'identifiant généré par la dernière insertion.
     *
     * @return int
     */
    protected function lastInsertId(): int
    {
        return (int) $this->database->pdo()->lastInsertId();
    }

    /**
     * Prépare et exécute une requête.
     *
     * @param string                              $sql    Requête SQL avec paramètres nommés.
     * @param array<string, int|string|bool|null> $params Valeurs des paramètres.
     *
     * @return PDOStatement
     *
     * @throws ConstraintViolationException Si une contrainte d'intégrité est violée.
     * @throws PDOException                 Pour toute autre erreur SGBD.
     */
    private function run(string $sql, array $params): PDOStatement
    {
        try {
            $statement = $this->database->pdo()->prepare($sql);
            $statement->execute($params);

            return $statement;
        } catch (PDOException $exception) {
            throw PdoExceptionTranslator::translate($exception);
        }
    }
}
