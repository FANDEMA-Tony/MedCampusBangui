<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Utilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:utilisateurs,email',
            'mot_de_passe' => 'required|string|min:6',
            'role' => 'required|in:admin,enseignant,etudiant,invite',
        ], [
            'nom.required' => 'Le nom est obligatoire.',
            'nom.max' => 'Le nom ne doit pas dépasser 255 caractères.',
            'email.required' => 'L\'email est obligatoire.',
            'email.email' => 'L\'email doit être valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'mot_de_passe.required' => 'Le mot de passe est obligatoire.',
            'mot_de_passe.min' => 'Le mot de passe doit contenir au moins 6 caractères.',
            'role.required' => 'Le rôle est obligatoire.',
            'role.in' => 'Le rôle doit être admin, enseignant, etudiant ou invite.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // 🔹 Créer l'utilisateur
            $utilisateur = Utilisateur::create([
                'nom' => $request->nom,
                'email' => $request->email,
                'mot_de_passe' => Hash::make($request->mot_de_passe),
                'role' => $request->role ?? 'invite',
                'statut' => 'actif',
            ]);

            // 🔹 Générer le token JWT
            $token = JWTAuth::fromUser($utilisateur);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur créé avec succès',
                'utilisateur' => [
                    'id' => $utilisateur->id_utilisateur,
                    'nom' => $utilisateur->nom,
                    'email' => $utilisateur->email,
                    'role' => $utilisateur->role,
                    'statut' => $utilisateur->statut,
                ],
                'access_token' => $token,
                'token_type' => 'bearer',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la création de l\'utilisateur. Veuillez réessayer.',
            ], 500);
        }
    }

    /**
     * Connexion d'un utilisateur
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'mot_de_passe' => 'required|string|min:6',
        ], [
            'email.required' => 'L\'email est obligatoire.',
            'email.email' => 'L\'email doit être valide.',
            'mot_de_passe.required' => 'Le mot de passe est obligatoire.',
            'mot_de_passe.min' => 'Le mot de passe doit contenir au moins 6 caractères.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // 🔹 Chercher l'utilisateur par email
            $utilisateur = Utilisateur::where('email', $request->email)->first();

            // 🔹 Vérifier si l'utilisateur existe et si le mot de passe est correct
            if (!$utilisateur || !Hash::check($request->mot_de_passe, $utilisateur->mot_de_passe)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email ou mot de passe incorrect. Veuillez vérifier vos identifiants.'
                ], 401);
            }

            // 🔹 Générer le token JWT
            $token = JWTAuth::fromUser($utilisateur);

            return response()->json([
                'success' => true,
                'message' => 'Connexion réussie',
                'utilisateur' => [
                    'id' => $utilisateur->id_utilisateur,
                    'nom' => $utilisateur->nom,
                    'email' => $utilisateur->email,
                    'role' => $utilisateur->role,
                ],
                'access_token' => $token,
                'token_type' => 'bearer',
            ], 200);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la génération du token. Veuillez réessayer.',
            ], 500);
        }
    }

    /**
     * Déconnexion d'un utilisateur
     */
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie'
            ], 200);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la déconnexion. Veuillez réessayer.',
            ], 500);
        }
    }

    /**
     * Obtenir les informations de l'utilisateur connecté
     */
    public function me()
    {
        try {
            $utilisateur = JWTAuth::parseToken()->authenticate();

            if (!$utilisateur) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé. Veuillez vous reconnecter.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'utilisateur' => [
                    'id' => $utilisateur->id_utilisateur,
                    'nom' => $utilisateur->nom,
                    'email' => $utilisateur->email,
                    'role' => $utilisateur->role,
                    'statut' => $utilisateur->statut,
                ]
            ], 200);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide ou expiré. Veuillez vous reconnecter.',
            ], 401);
        }
    }
}