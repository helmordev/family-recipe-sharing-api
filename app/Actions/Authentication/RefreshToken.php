<?php

declare(strict_types=1);

namespace App\Actions\Authentication;

use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsAction;
use Symfony\Component\HttpFoundation\JsonResponse;

final class RefreshToken
{
    use AsAction;

    public function handle(Request $request): string
    {
        $request->user()->currentAccessToken()->delete();

        return $request->user()->createToken('auth_token')->plainTextToken;
    }

    public function asController(Request $request): JsonResponse
    {
        $token = $this->handle($request);

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully.',
            'token' => $token,
        ], 200);
    }
}
