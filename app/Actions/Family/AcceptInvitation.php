<?php

declare(strict_types=1);

namespace App\Actions\Family;

use App\Models\Family;
use App\Models\FamilyInvitation;
use App\Models\User;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use Symfony\Component\HttpFoundation\JsonResponse;

final class AcceptInvitation
{
    use AsAction;

    public function handle(string $code, User $user): Family
    {
        $invitation = FamilyInvitation::where('code', $code)
            ->valid()
            ->firstOrFail();

        $family = $invitation->family;

        // Attach user to family
        $family->members()->syncWithoutDetaching([
            $user->id => [
                'role' => 'member',
                'joined_at' => now(),
            ],
        ]);

        $invitation->update(['used_at' => now()]);

        return $family;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'exists:family_invitations,code'],
        ];
    }

    public function jsonResponse(Family $family): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Joined family successfully.',
            'data' => $family,
        ]);
    }

    public function asController(ActionRequest $request): Family
    {
        $request->validate();

        /** @var User $user */
        $user = $request->user();

        return $this->handle($request->code, $user);
    }
}
