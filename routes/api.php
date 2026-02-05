<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth.jwt'])->group(function () {
    Route::get('/cours', [CoursController::class, 'index']);
});
