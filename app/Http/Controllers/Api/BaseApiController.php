<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class BaseApiController extends Controller
{
    use AuthorizesRequests;  // 🔹 AJOUTE CETTE LIGNE IMPORTANTE
    
    protected function successResponse($data, string $message = "Succès", int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'message' => $message
        ], $status);
    }

    protected function errorResponse($errors, string $message = "Erreur de validation", int $status = 422): JsonResponse
    {
        return response()->json([
            'errors' => $errors,
            'message' => $message
        ], $status);
    }
}