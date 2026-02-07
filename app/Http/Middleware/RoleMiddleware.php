<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Vérifie si l'utilisateur a le rôle requis.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 🔹 Vérifie si l'utilisateur est connecté
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous devez être connecté pour accéder à cette ressource. Veuillez vous authentifier.'
            ], 401);
        }

        // 🔹 Récupère l'utilisateur connecté
        $utilisateur = Auth::user();

        // 🔹 Vérifie si l'utilisateur a le bon rôle
        if (!in_array($utilisateur->role, $roles)) {
            // Message personnalisé selon le rôle demandé
            $rolesRequis = implode(' ou ', $roles);
            
            return response()->json([
                'success' => false,
                'message' => "Accès refusé. Cette ressource est réservée aux utilisateurs ayant le rôle : {$rolesRequis}. Votre rôle actuel est : {$utilisateur->role}."
            ], 403);
        }

        return $next($request);
    }
}