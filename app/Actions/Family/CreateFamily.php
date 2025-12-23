<?php

declare(strict_types=1);

namespace App\Actions\Family;

use App\Models\Family;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use Symfony\Component\HttpFoundation\JsonResponse;

final class CreateFamily
{
    use AsAction;

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, User $user): Family
    {
        return DB::transaction(function () use ($data, $user) {
            $family = Family::create([
                'name' => $data['name'],
                'owner_id' => $user->id,
            ]);

            $family->members()->attach($user->id, [
                'role' => 'owner',
                'joined_at' => now(),
            ]);

            return $family;
        });
    }

    /**
     * Validation rules for the action.
     *
     * @return array<string, array<int|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
        ];
    }

    public function asController(ActionRequest $request): Family
    {
        /** @var User $user */
        $user = $request->user();

        return $this->handle($request->validated(), $user);
    }

    public function jsonResponse(Family $family): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Family created successfully.',
            'data' => $family,
        ], 201);
    }
}
