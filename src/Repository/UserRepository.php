<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Data\AbstractRepository;
use App\Core\Data\Row;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserCredentials;
use UnexpectedValueException;

/**
 * Accès en lecture aux utilisateurs.
 *
 * Les employés sont gérés par le SI RH : aucune écriture n'est proposée.
 */
final class UserRepository extends AbstractRepository
{
    private const string COLUMNS = 'id, nom, prenom, telephone, email, role';

    /**
     * Liste tous les utilisateurs par nom puis prénom.
     *
     * @return list<User>
     */
    public function findAll(): array
    {
        return array_map(
            self::hydrate(...),
            $this->fetchAll('SELECT ' . self::COLUMNS . ' FROM utilisateur ORDER BY nom, prenom, id'),
        );
    }

    /**
     * Recherche un utilisateur par identifiant.
     *
     * @param int $id Identifiant.
     *
     * @return User|null
     */
    public function findById(int $id): ?User
    {
        $row = $this->fetchOne('SELECT ' . self::COLUMNS . ' FROM utilisateur WHERE id = :id', ['id' => $id]);

        return $row === null ? null : self::hydrate($row);
    }

    /**
     * Recherche un utilisateur et l'empreinte de son mot de passe par e-mail.
     *
     * @param string $email Adresse e-mail (comparaison insensible à la casse).
     *
     * @return UserCredentials|null
     */
    public function findCredentialsByEmail(string $email): ?UserCredentials
    {
        $row = $this->fetchOne(
            'SELECT ' . self::COLUMNS . ', mot_de_passe FROM utilisateur WHERE email = :email',
            ['email' => $email],
        );

        return $row === null ? null : new UserCredentials(self::hydrate($row), $row->string('mot_de_passe'));
    }

    /**
     * Nombre total d'utilisateurs.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->fetchInt('SELECT COUNT(*) FROM utilisateur');
    }

    /**
     * Construit un utilisateur à partir d'une ligne de résultat.
     *
     * @param Row    $row    Ligne de résultat.
     * @param string $prefix Préfixe des colonnes (cas d'une jointure).
     *
     * @return User
     *
     * @throws UnexpectedValueException Si le rôle est inconnu.
     */
    public static function hydrate(Row $row, string $prefix = ''): User
    {
        $role = Role::tryFrom($row->string($prefix . 'role'));
        if ($role === null) {
            throw new UnexpectedValueException('Rôle utilisateur inconnu.');
        }

        return new User(
            $row->int($prefix . 'id'),
            $row->string($prefix . 'nom'),
            $row->string($prefix . 'prenom'),
            $row->string($prefix . 'telephone'),
            $row->string($prefix . 'email'),
            $role,
        );
    }
}
