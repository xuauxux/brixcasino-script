<?php

namespace App\Http\Middleware;

use App\Services\Platform\Settings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Site-wide maintenance mode. The state lives in the database, so every server
 * and every visitor sees the same thing; the owner and staff keep full access,
 * and bridge callbacks keep working so in-game deposits do not get lost.
 */
class MaintenanceMode
{
    public function handle(Request $request, Closure $next)
    {
        if (! Settings::maintenance()) {
            return $next($request);
        }

        $path = trim($request->path(), '/');

        if (str_starts_with($path, 'api/') || str_starts_with($path, 'panel') || str_starts_with($path, 'admin')) {
            return $next($request);
        }

        $user = Auth::user();
        $role = $user?->role ?: ((int) ($user?->admin ?? 0) === 1 ? 'admin' : 'user');

        if ($user !== null && in_array($role, ['owner', 'admin'], true)) {
            return $next($request);
        }

        return response()->view('maintenance', [
            'message' => Settings::maintenanceMessage(),
            'endsAt' => null,
        ], 503);
    }
}
