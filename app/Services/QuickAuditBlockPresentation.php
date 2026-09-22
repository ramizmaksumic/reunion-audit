<?php

namespace App\Services;

/**
 * Presentational metadata (color, icon) for each quick_audit block, keyed by
 * block key (see QuickAuditScoringService::blocks()). Kept here rather than
 * inline in each view — the wizard and results page both need it — mirroring
 * how AreaPresentation serves the full audit's results/PDF views.
 */
class QuickAuditBlockPresentation
{
    /**
     * @var array<string, array{color: string, icon: string}>
     */
    private const DATA = [
        'pronalazljivost' => ['color' => '#2563eb', 'icon' => 'magnifying-glass'],
        'web' => ['color' => '#0d9488', 'icon' => 'computer-desktop'],
        'reputacija_povjerenje' => ['color' => '#d97706', 'icon' => 'star'],
        'drustvene_mreze' => ['color' => '#db2777', 'icon' => 'share'],
        'odziv_procesi' => ['color' => '#7c3aed', 'icon' => 'chat-bubble-left-right'],
        'mjerenje_rast' => ['color' => '#16a34a', 'icon' => 'chart-bar'],
    ];

    private const DEFAULT_COLOR = '#71717a';

    private const DEFAULT_ICON = 'sparkles';

    public static function color(string $blockKey): string
    {
        return self::DATA[$blockKey]['color'] ?? self::DEFAULT_COLOR;
    }

    public static function icon(string $blockKey): string
    {
        return self::DATA[$blockKey]['icon'] ?? self::DEFAULT_ICON;
    }
}
