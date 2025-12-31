<?php

declare(strict_types=1);

namespace App\Actions\Recipe;

use App\Enums\RecipeVisibility;
use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use Symfony\Component\HttpFoundation\JsonResponse;

final class CreateRecipe
{
    use AsAction;

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, User $user): Recipe
    {
        return Recipe::create([
            'user_id' => $user->id,
            'family_id' => $data['family_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'visibility' => $data['visibility'] ?? RecipeVisibility::PRIVATE,
            'image_path' => $data['image_path'] ?? null,
            'prep_time' => $data['prep_time'] ?? null,
            'cook_time' => $data['cook_time'] ?? null,
            'servings' => $data['servings'] ?? null,
        ]);
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user() !== null;
    }

    /**
     * Validation rules for the action.
     *
     * @return array<string, array<int, Enum|string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
            'visibility' => ['nullable', Rule::enum(RecipeVisibility::class)],
            'family_id' => ['nullable', 'integer', 'exists:families,id'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'prep_time' => ['nullable', 'integer', 'min:0'],
            'cook_time' => ['nullable', 'integer', 'min:0'],
            'servings' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function asController(ActionRequest $request): Recipe
    {
        /** @var User $user */
        $user = $request->user();

        return $this->handle($request->validated(), $user);
    }

    public function jsonResponse(Recipe $recipe): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Recipe created successfully.',
            'data' => new RecipeResource($recipe),
        ], 201);
    }
}
