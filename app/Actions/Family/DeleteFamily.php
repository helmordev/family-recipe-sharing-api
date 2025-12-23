<?php

declare(strict_types=1);

namespace App\Actions\Family;

use App\Models\Family;
use App\Models\User;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use Symfony\Component\HttpFoundation\JsonResponse;

final class DeleteFamily
{
    use AsAction;

    public function handle(Family $family): void
    {
        $family->delete();
    }

    public function authorize(ActionRequest $request, Family $family): bool
    {
        /** @var User $user */
        $user = $request->user();

        return $family->owner_id === $user->id;
    }

    public function asController(Family $family): void
    {
        $this->handle($family);
    }

    public function jsonResponse(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Family deleted successfully.',
        ], 200);
    }
}
