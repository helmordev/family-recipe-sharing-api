<?php

declare(strict_types=1);

namespace App\Actions\Family;

use App\Models\Family;
use App\Models\User;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use Symfony\Component\HttpFoundation\JsonResponse;

final class InviteMember
{
    use AsAction;

    public function handle(Family $family, ?string $email = null): string
    {
        $code = mb_strtoupper(bin2hex(random_bytes(4)));

        $family->invitations()->create([
            'email' => $email,
            'code' => $code,
            'expires_at' => now()->addDays(3),
        ]);

        return $code;
    }

    /**
     * @return array<string,list<int|string>>
     */
    public function rules(): array
    {
        return [
            'family_id' => ['required', 'exists:families,id'],
            'email' => ['nullable', 'email'],
        ];
    }

    public function asController(ActionRequest $request): JsonResponse
    {
        $request->validate();

        $family = Family::findOrFail($request->family_id);

        /** @var User */
        $user = $request->user();

        if ($family->owner_id !== $user->id) {
            abort(403, 'You are not authorized to invite members to this family.');
        }

        $code = $this->handle($family, $request->email);

        return response()->json([
            'success' => true,
            'message' => 'Invatation created.',
            'data' => [
                'code' => $code,
            ],
        ]);
    }
}
