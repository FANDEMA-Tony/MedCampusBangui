<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtMiddleware
{
    /**
     * Vérifie si le token JWT est valide
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // 🔹 Vérifie le token et récupère l'utilisateur
            $utilisateur = JWTAuth::parseToken()->authenticate();

            if (!$utilisateur) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide ou expiré',
                'error' => $e->getMessage()
            ], 401);
        }

        return $next($request);
    }
}