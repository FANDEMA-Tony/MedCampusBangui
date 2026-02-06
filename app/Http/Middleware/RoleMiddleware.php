<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Vérifie si l'utilisateur a le rôle requis.
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'error' => 'Accès interdit : vous devez être authentifié.'
            ], 401);
        }

        if (!in_array($user->role, $roles)) {
            return response()->json([
                'error' => "Accès interdit : vous devez être " . implode(' ou ', $roles) . "."
            ], 403);
        }

        return $next($request);
    }
}
