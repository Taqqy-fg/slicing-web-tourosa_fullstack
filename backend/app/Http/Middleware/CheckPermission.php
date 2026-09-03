<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        // Super admin bypass all permission checks
        if ($user->is_superadmin) {
            return $next($request);
        }

        // Check if user has ANY of the required permissions
        $hasPermission = false;
        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                $hasPermission = true;
                break;
            }
        }

        if (! $hasPermission) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses fitur ini.');
        }

        return $next($request);
    }
}
