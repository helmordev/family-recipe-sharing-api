<?php

declare(strict_types=1);

namespace App\Actions\Authentication;

use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsAction;
use Symfony\Component\HttpFoundation\JsonResponse;

final class LogoutUser
{
    use AsAction;

    public function handle(Request $request): void
    {
        $request->user()->currentAccessToken()->delete();
    }

    public function asController(Request $request): JsonResponse
    {
        $this->handle($request);

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ], 200);
    }
}
