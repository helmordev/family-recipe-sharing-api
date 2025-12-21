<?php

declare(strict_types=1);

use App\Actions\Authentication\LoginUser;
use App\Actions\Authentication\LogoutUser;
use App\Actions\Authentication\RefreshToken;
use App\Actions\Authentication\RegisterUser;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:sanctum')->group(function (): void {
    Route::post('/register', RegisterUser::class);
    Route::post('/login', LoginUser::class);
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', LogoutUser::class);
    Route::post('/refresh-token', RefreshToken::class);
});
