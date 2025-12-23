<?php

declare(strict_types=1);

namespace App\Actions\Family;

use App\Models\Family;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;
use Symfony\Component\HttpFoundation\JsonResponse;

final class RemoveMember
{
    use AsAction;

    public function handle(Family $family, User $user): int
    {
        return $family->members()->detach($user->id);
    }

    public function authorize(User $user, Family $family): bool
    {
        return $family->owner_id === $user->id;
    }

    public function asController(Family $family, User $user): int
    {
        return $this->handle($family, $user);
    }

    public function jsonResponse(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Member removed from family successfully.',
        ]);
    }
}
