<?php

use App\Http\Controllers\JWTAuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WordController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::get('/', function () {
    response()->json(['message' => 'Backend Challenge 🏅 - Dictionary']);
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function () {
    Route::post('/signup', [JWTAuthController::class, 'register'])->name('signup');
    Route::post('/signin', [JWTAuthController::class, 'login'])->name('signin');
});

Route::middleware('auth:api')->group(function () {
    Route::get('user/me', [UserController::class, 'me']);
    Route::get('user/me/history', [UserController::class, 'getSearchHistory']);
    Route::get('user/me/favorites', [UserController::class, 'getFavoriteWords']);

    Route::get('entries/en', [WordController::class, 'index']);
    Route::get('entries/en/{word}', [WordController::class, 'show']);
    Route::post('entries/en/{word}/favorite', [WordController::class, 'favorite']);
    Route::delete('entries/en/{word}/unfavorite', [WordController::class, 'unfavorite']);
});