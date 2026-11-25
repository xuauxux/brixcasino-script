<?php

namespace App\Services\Platform;

use App\Models\Settings;

/** The game catalogue, merged with the live enabled/disabled switches. */
class Catalogue
{
    /** @return array<string, array{slug: string, name: string, tag: string, description: string, image: string, enabled: bool, setting: ?string}> */
    public static function all(): array
    {
        $settings = Settings::where('id', 1)->first();
        $out = [];

        foreach (config('games', []) as $slug => $game) {
            $enabled = true;
            $setting = $game['setting'] ?? null;

            // Legacy convention: 1 means "temporarily disabled".
            if ($setting !== null && $settings !== null) {
                $enabled = (int) ($settings->{$setting} ?? 0) === 0;
            }

            $out[$slug] = $game + ['slug' => $slug, 'enabled' => $enabled];
        }

        return $out;
    }

    public static function enabled(): array
    {
        return array_filter(self::all(), fn ($game) => $game['enabled']);
    }

    public static function find(string $slug): ?array
    {
        return self::all()[$slug] ?? null;
    }
}
