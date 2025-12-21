<?php

declare(strict_types=1);

use App\Actions\Authentication\RegisterUser;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:sanctum')->group(function (): void {
    Route::post('/register', RegisterUser::class);
});
