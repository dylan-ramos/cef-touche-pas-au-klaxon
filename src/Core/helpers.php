<?php

/**
 * Fonctions utilitaires globales destinées aux gabarits.
 */

declare(strict_types=1);

if (!function_exists('e')) {
    /**
     * Échappe une valeur pour un affichage sûr dans du HTML (contenu ou attribut).
     *
     * @param string|int|float|bool|Stringable|null $value Valeur à afficher.
     *
     * @return string Valeur échappée ; chaîne vide pour null.
     */
    function e(string|int|float|bool|Stringable|null $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
