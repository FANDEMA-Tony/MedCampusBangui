<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Utilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $user = Utilisateur::create([
            'nom' => $request->nom,
            'email' => $request->email,
            'mot_de_passe' => Hash::make($request->mot_de_passe),
            'role' => $request->role ?? 'invite',
        ]);

        return response()->json($user, 201);
    }

   public function login(Request $request)
    {
        // On passe "password" comme clé, même si la colonne est mot_de_passe
        $credentials = [
            'email' => $request->email,
            'password' => $request->mot_de_passe
        ];

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Identifiants invalides'], 401);
        }

        return response()->json([
            'token' => $token,
            'user' => auth('api')->user()
        ]);
    }
}
