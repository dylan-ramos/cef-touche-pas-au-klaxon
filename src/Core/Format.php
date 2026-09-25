<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeInterface;

/**
 * Formats d'affichage communs aux gabarits (conventions françaises).
 */
final class Format
{
    /**
     * Date au format jour/mois/année (25/09/2026).
     *
     * @param DateTimeInterface $date Date à afficher.
     *
     * @return string
     */
    public static function date(DateTimeInterface $date): string
    {
        return $date->format('d/m/Y');
    }

    /**
     * Heure au format 24 h (08:30).
     *
     * @param DateTimeInterface $date Date dont on affiche l'heure.
     *
     * @return string
     */
    public static function time(DateTimeInterface $date): string
    {
        return $date->format('H:i');
    }

    /**
     * Numéro de téléphone à 10 chiffres groupés par paires (06 12 34 56 78),
     * séparées par des espaces insécables pour ne jamais être coupé en fin de
     * ligne. Toute autre valeur est retournée telle quelle.
     *
     * @param string $phone Numéro brut.
     *
     * @return string
     */
    public static function phone(string $phone): string
    {
        return preg_match('/^\d{10}$/', $phone) === 1 ? implode("\u{00A0}", str_split($phone, 2)) : $phone;
    }
}
