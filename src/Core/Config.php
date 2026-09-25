<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exception\ConfigException;
use Dotenv\Dotenv;

/**
 * Accès typé aux paramètres de configuration de l'application.
 *
 * Les valeurs proviennent du fichier `.env` situé à la racine du projet ;
 * les variables d'environnement réelles du processus (conteneur, serveur)
 * sont prioritaires sur celles du fichier.
 */
final class Config
{
    /**
     * @param array<string, string> $values Paramètres indexés par nom.
     */
    public function __construct(private readonly array $values)
    {
    }

    /**
     * Charge la configuration depuis le fichier `.env` et l'environnement.
     *
     * @param string $rootDir Répertoire racine du projet contenant le fichier `.env`.
     *
     * @return self
     */
    public static function load(string $rootDir): self
    {
        $fromFile = Dotenv::createArrayBacked($rootDir)->safeLoad();
        $values = [];

        foreach ([$fromFile, getenv()] as $source) {
            foreach ($source as $key => $value) {
                if ($value !== null) {
                    $values[$key] = $value;
                }
            }
        }

        return new self($values);
    }

    /**
     * Indique si un paramètre est défini.
     *
     * @param string $key Nom du paramètre.
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    /**
     * Retourne un paramètre sous forme de chaîne.
     *
     * @param string      $key     Nom du paramètre.
     * @param string|null $default Valeur utilisée si le paramètre est absent ; null le rend obligatoire.
     *
     * @return string
     *
     * @throws ConfigException Si le paramètre est absent et sans valeur par défaut.
     */
    public function string(string $key, ?string $default = null): string
    {
        if ($this->has($key)) {
            return $this->values[$key];
        }

        if ($default === null) {
            throw new ConfigException(sprintf('Le paramètre de configuration « %s » est manquant.', $key));
        }

        return $default;
    }

    /**
     * Retourne un paramètre booléen (`true`, `1`, `yes`, `on` sont considérés comme vrais).
     *
     * @param string $key     Nom du paramètre.
     * @param bool   $default Valeur utilisée si le paramètre est absent.
     *
     * @return bool
     */
    public function bool(string $key, bool $default = false): bool
    {
        if (!$this->has($key)) {
            return $default;
        }

        return in_array(strtolower(trim($this->values[$key])), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Retourne un paramètre entier.
     *
     * @param string   $key     Nom du paramètre.
     * @param int|null $default Valeur utilisée si le paramètre est absent ; null le rend obligatoire.
     *
     * @return int
     *
     * @throws ConfigException Si le paramètre est absent sans valeur par défaut ou n'est pas un entier.
     */
    public function int(string $key, ?int $default = null): int
    {
        if (!$this->has($key)) {
            return (int) $this->string($key, $default === null ? null : (string) $default);
        }

        $value = filter_var($this->values[$key], FILTER_VALIDATE_INT);
        if ($value === false) {
            throw new ConfigException(sprintf('Le paramètre de configuration « %s » doit être un entier.', $key));
        }

        return $value;
    }
}
