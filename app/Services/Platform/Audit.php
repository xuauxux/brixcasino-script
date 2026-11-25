<?php

namespace App\Services\Platform;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Append-only trail of what admins did, for the owner to review. */
class Audit
{
    public static function log(
        int $adminId,
        string $action,
        ?int $targetUserId = null,
        ?string $details = null,
        array $meta = [],
        ?string $ip = null
    ): void {
        if (! Schema::hasTable('admin_logs')) {
            return;
        }

        DB::table('admin_logs')->insert([
            'admin_id' => $adminId,
            'action' => mb_substr($action, 0, 48),
            'target_user_id' => $targetUserId,
            'details' => $details !== null ? mb_substr($details, 0, 255) : null,
            'meta' => $meta === [] ? null : json_encode($meta),
            'ip' => $ip,
            'created_at' => now(),
        ]);
    }

    public static function recent(int $limit = 50): array
    {
        if (! Schema::hasTable('admin_logs')) {
            return [];
        }

        return DB::table('admin_logs as l')
            ->leftJoin('users as a', 'a.id', '=', 'l.admin_id')
            ->leftJoin('users as t', 't.id', '=', 'l.target_user_id')
            ->orderByDesc('l.id')
            ->limit($limit)
            ->get(['l.*', 'a.username as admin_name', 't.username as target_name'])
            ->all();
    }
}
