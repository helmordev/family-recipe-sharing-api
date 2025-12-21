<?php

declare(strict_types=1);

namespace App\Actions\Authentication;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsAction;

final class LogoutUser
{
    use AsAction;

    public function handle(Request $request): void
    {
        $request->user()->tokens()->delete();
    }

    public function asController(Request $request): JsonResponse
    {
        $this->handle($request);

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }
}
