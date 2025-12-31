<?php

declare(strict_types=1);

use App\Actions\Authentication\LoginUser;
use App\Actions\Authentication\LogoutUser;
use App\Actions\Authentication\RefreshToken;
use App\Actions\Authentication\RegisterUser;
use App\Actions\Family\CreateFamily;
use App\Actions\Family\DeleteFamily;
use App\Actions\Family\InviteMember;
use App\Actions\Family\LeaveFamily;
use App\Actions\Family\ListFamilies;
use App\Actions\Family\RemoveMember;
use App\Actions\Recipe\CreateRecipe;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:sanctum')->group(function (): void {
    Route::post('/register', RegisterUser::class);
    Route::post('/login', LoginUser::class);
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', LogoutUser::class);
    Route::post('/refresh-token', RefreshToken::class);

    // Family Routes
    Route::post('/families', CreateFamily::class);
    Route::get('/families', ListFamilies::class);
    Route::delete('/families/{family}', DeleteFamily::class);
    Route::post('/families/invite', InviteMember::class);
    Route::delete('/families/{family}/members/{user}', RemoveMember::class);
    Route::post('/families/{family}/leave', LeaveFamily::class);

    // Recipe Routes
    Route::post('/recipes', CreateRecipe::class);
});
