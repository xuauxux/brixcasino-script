<?php

namespace App\Services\Platform;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Small typed key/value store for platform switches (maintenance, bot desired
 * state, rain interval, DONUTTRIX event window). One row per key, cached for a
 * minute so a busy page does not hammer the table.
 */
class Settings
{
    private const TTL = 60;

    /** @return array<string, string> */
    public static function all(): array
    {
        if (! Schema::hasTable('platform_settings')) {
            return [];
        }

        return Cache::remember('platform:settings', self::TTL, function () {
            $out = [];

            foreach (DB::table('platform_settings')->get() as $row) {
                $out[$row->name] = (string) $row->value;
            }

            return $out;
        });
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $all = self::all();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);

        return $value === null ? $default : $value === '1';
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::get($key);

        return $value === null || $value === '' ? $default : (int) $value;
    }

    public static function set(string $key, $value): void
    {
        if (! Schema::hasTable('platform_settings')) {
            return;
        }

        DB::table('platform_settings')->updateOrInsert(
            ['name' => $key],
            ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value, 'updated_at' => now()],
        );

        Cache::forget('platform:settings');
    }

    /* ------------------------------------------------------- maintenance */

    public static function maintenance(): bool
    {
        return self::bool('maintenance', false);
    }

    public static function setMaintenance(bool $on, ?string $message = null): void
    {
        self::set('maintenance', $on);

        if ($message !== null) {
            self::set('maintenance_message', $message);
        }
    }

    public static function maintenanceMessage(): string
    {
        return (string) self::get('maintenance_message', 'The arcade is being worked on. We will be back shortly.');
    }

    /* --------------------------------------------------------------- bot */

    /** Accounts that become staff as soon as they verify their IGN. */
    public const DEFAULT_ADMIN_IGNS = 'L_ray423,.nelisonfire';

    /** @return array<int, string> lower-case IGNs allowed to reach the panel */
    public static function adminIgns(): array
    {
        $raw = (string) self::get('admin_igns', self::DEFAULT_ADMIN_IGNS);

        $igns = array_filter(array_map(
            fn ($ign) => strtolower(trim((string) $ign)),
            preg_split('/[,\s]+/', $raw) ?: [],
        ));

        return array_values(array_unique($igns));
    }

    public static function isAdminIgn(string $ign): bool
    {
        $ign = strtolower(trim($ign));

        return $ign !== '' && in_array($ign, self::adminIgns(), true);
    }

    public static function setAdminIgns(string $list): void
    {
        $clean = implode(',', array_filter(array_map(
            fn ($ign) => trim((string) $ign),
            preg_split('/[,\s]+/', $list) ?: [],
        )));

        self::set('admin_igns', $clean !== '' ? $clean : self::DEFAULT_ADMIN_IGNS);
    }

    public static function botEnabled(): bool
    {
        return self::bool('bot_enabled', true);
    }

    public static function setBotEnabled(bool $on): void
    {
        self::set('bot_enabled', $on);
    }

    /* -------------------------------------------------------------- rain */

    /** @return array{enabled: bool, interval: int, amount: float, duration: int} */
    public static function rain(): array
    {
        return [
            'enabled' => self::bool('rain_enabled', true),
            'interval' => max(1, self::int('rain_interval_minutes', 30)),
            'amount' => max(0, (float) self::get('rain_amount', '500000')),
            'duration' => max(15, self::int('rain_duration_seconds', 90)),
        ];
    }

    /* ------------------------------------------------------------- event */

    /** @return array{start: ?string, end: ?string} */
    public static function eventWindow(): array
    {
        return [
            'start' => self::get('event_start'),
            'end' => self::get('event_end'),
        ];
    }
}
