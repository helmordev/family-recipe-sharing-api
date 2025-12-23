<?php

declare(strict_types=1);

namespace App\Actions\Family;

use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ListFamilies
{
    use AsAction;

    /**
     * @return Collection<int, Family>
     */
    public function handle(User $user): Collection
    {
        return $user->families()->with('owner', 'members')->get();
    }

    /**
     * @return Collection<int, Family>
     */
    public function asController(ActionRequest $request): Collection
    {
        /** @var User $user */
        $user = $request->user();

        return $this->handle($user);
    }

    /**
     * @param  Collection<int, Family>  $families
     */
    public function jsonResponse(Collection $families): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Families retrieved successfully',
            'data' => $families,
        ]);
    }
}
