<?php

namespace App\Services;

/**
 * Presentational metadata (color, short description) for each main area,
 * keyed by Area::$key. Kept here rather than in the database since it's
 * report copy, not methodology data the admin needs to edit (see
 * docs/rds-methodology.md §2). Shared by the results dashboard and the PDF
 * export so both show the same colors/descriptions.
 */
class AreaPresentation
{
    /**
     * @var array<string, array{color: string, description: string}>
     */
    private const DATA = [
        'digitalna_prisutnost' => [
            'color' => '#2563eb',
            'description' => 'Kvalitet i profesionalnost digitalne osnove — web, Google Business, SEO, reputacija, brend i povjerenje.',
        ],
        'korisnicko_iskustvo' => [
            'color' => '#d97706',
            'description' => 'Koliko je digitalni nastup optimizovan da posjetioca pretvori u kupca ili upit.',
        ],
        'digitalna_efikasnost' => [
            'color' => '#0d9488',
            'description' => 'Koliko digitalni alati i procesi stvarno podržavaju svakodnevno poslovanje.',
        ],
        'marketing_i_rast' => [
            'color' => '#7c3aed',
            'description' => 'Da li se marketing vodi planski — od strategije, preko akvizicije, do mjerenja rezultata.',
        ],
    ];

    private const DEFAULT_COLOR = '#71717a';

    public static function color(string $areaKey): string
    {
        return self::DATA[$areaKey]['color'] ?? self::DEFAULT_COLOR;
    }

    public static function description(string $areaKey): string
    {
        return self::DATA[$areaKey]['description'] ?? '';
    }
}
