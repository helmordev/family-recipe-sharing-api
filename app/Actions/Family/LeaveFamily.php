<?php

declare(strict_types=1);

namespace App\Actions\Family;

use App\Models\Family;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use Symfony\Component\HttpFoundation\JsonResponse;

final class LeaveFamily
{
    use AsAction;

    /**
     * @throws ValidationException
     */
    public function handle(Family $family, User $user): void
    {
        if ($family->owner_id === $user->id) {
            throw ValidationException::withMessages([
                'family' => 'The owner of the family cannot leave the family. Please transfer ownership or delete the family.',
            ]);
        }

        if (! $family->members()->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'family' => 'You are not a member of this family.',
            ]);
        }

        $family->members()->detach($user->id);
    }

    public function asController(ActionRequest $request, Family $family): void
    {
        /** @var User $user */
        $user = $request->user();

        $this->handle($family, $user);
    }

    public function jsonResponse(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'You have left the family successfully.',
        ]);
    }
}
