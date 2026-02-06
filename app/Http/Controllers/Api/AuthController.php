<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Utilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register(Request $request)
    {
        // ✅ Validation avec messages personnalisés
        $data = $request->validate([
            'nom' => 'required|string|max:255',
            'email' => 'required|email|unique:utilisateurs,email',
            'mot_de_passe' => 'required|string|min:6',
            'role' => 'in:admin,enseignant,etudiant,invite'
        ], [
            'nom.required' => 'Le nom est obligatoire.',
            'email.required' => 'L’email est obligatoire.',
            'email.email' => 'L’email doit être valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'mot_de_passe.required' => 'Le mot de passe est obligatoire.',
            'mot_de_passe.min' => 'Le mot de passe doit contenir au moins 6 caractères.',
            'role.in' => 'Le rôle doit être admin, enseignant, etudiant ou invite.'
        ]);

        $user = Utilisateur::create([
            'nom' => $data['nom'],
            'email' => $data['email'],
            'mot_de_passe' => Hash::make($data['mot_de_passe']),
            'role' => $data['role'] ?? 'invite',
        ]);

        return response()->json([
            'message' => 'Utilisateur créé avec succès',
            'user' => $user
        ], 201);
    }

    /**
     * Connexion utilisateur et génération du token JWT
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'mot_de_passe' => 'required|string|min:6'
        ], [
            'email.required' => 'L’email est obligatoire.',
            'email.email' => 'L’email doit être valide.',
            'mot_de_passe.required' => 'Le mot de passe est obligatoire.',
            'mot_de_passe.min' => 'Le mot de passe doit contenir au moins 6 caractères.'
        ]);

        $loginData = [
            'email' => $credentials['email'],
            'password' => $credentials['mot_de_passe']
        ];

        if (!$token = auth('api')->attempt($loginData)) {
            return response()->json(['error' => 'Identifiants invalides'], 401);
        }

        return response()->json([
            'message' => 'Connexion réussie',
            'token' => $token,
            'user' => auth('api')->user()
        ]);
    }

    /**
     * Déconnexion utilisateur
     */
    public function logout()
    {
        auth('api')->logout();
        return response()->json(['message' => 'Déconnexion réussie']);
    }
}