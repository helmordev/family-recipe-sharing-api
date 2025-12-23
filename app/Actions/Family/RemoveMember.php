<?php

declare(strict_types=1);

namespace App\Actions\Family;

use App\Models\Family;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use Symfony\Component\HttpFoundation\JsonResponse;

final class RemoveMember
{
    use AsAction;

    /**
     * @throws ValidationException
     */
    public function handle(Family $family, User $user): int
    {
        if ($family->owner_id === $user->id) {
            throw ValidationException::withMessages([
                'user' => 'Cannot remove the owner from the family.',
            ]);
        }

        if (! $family->members()->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'user' => 'User is not a member of this family.',
            ]);
        }

        return $family->members()->detach($user->id);
    }

    public function authorize(ActionRequest $request, Family $family): bool
    {
        /** @var User $authUser */
        $authUser = $request->user();

        return $family->owner_id === $authUser->id;
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
