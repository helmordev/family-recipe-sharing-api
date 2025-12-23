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

    public function handle(Family $family, User $user): Family
    {
        if ($family->owner_id === $user->id) {
            throw ValidationException::withMessages([
                'message' => 'The owner of the family cannot leave the family. Please transfer ownership or delete the family.',
            ]);
        }

        $family->members()->detach($user->id);

        return $family;
    }

    public function asController(ActionRequest $request, Family $family): Family
    {
        /** @var User $user */
        $user = $request->user();

        return $this->handle($family, $user);
    }

    public function jsonResponse(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'You have left the family successfully.',
        ]);
    }
}
