<?php

declare(strict_types=1);

namespace App\Core\Data\Exception;

use PDOException;
use RuntimeException;

/**
 * Violation d'une contrainte d'intégrité de la base de données.
 *
 * Le message d'origine du SGBD est conservé dans l'exception précédente pour
 * la journalisation ; il ne doit jamais être présenté à l'utilisateur.
 */
class ConstraintViolationException extends RuntimeException
{
    /**
     * @param PDOException $previous Exception PDO d'origine.
     */
    public function __construct(PDOException $previous)
    {
        parent::__construct('Violation d\'une contrainte d\'intégrité.', 0, $previous);
    }
}
