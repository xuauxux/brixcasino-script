<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Role gate for the panel: `access:admin` lets staff in, `access:owner` only
 * the owner. The legacy `users.admin` flag still counts as staff so existing
 * admin accounts keep working.
 */
class RoleGate
{
    public function handle(Request $request, Closure $next, string $role = 'admin')
    {
        $user = Auth::user();

        if ($user === null) {
            return redirect('/')->with('error', 'Please sign in.');
        }

        $userRole = $user->role ?: ((int) $user->admin === 1 ? 'admin' : 'user');
        $isOwner = $userRole === 'owner';
        $isStaff = $isOwner || $userRole === 'admin' || (int) $user->admin === 1;

        if ($role === 'owner' && ! $isOwner) {
            abort(403, 'Only the owner can open this panel.');
        }

        if ($role === 'admin' && ! $isStaff) {
            abort(404);
        }

        return $next($request);
    }
}
