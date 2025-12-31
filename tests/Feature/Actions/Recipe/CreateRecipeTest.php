<?php

declare(strict_types=1);

use App\Enums\RecipeVisibility;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a recipe with all fields', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/recipes', [
        'title' => 'Grandma\'s Special Pasta',
        'description' => 'A delicious family recipe passed down through generations.',
        'visibility' => RecipeVisibility::FAMILY->value,
        'prep_time' => 15,
        'cook_time' => 30,
        'servings' => 4,
    ]);

    $response->assertCreated()
        ->assertJson([
            'success' => true,
            'message' => 'Recipe created successfully.',
            'data' => [
                'title' => 'Grandma\'s Special Pasta',
                'description' => 'A delicious family recipe passed down through generations.',
                'visibility' => RecipeVisibility::FAMILY->value,
                'visibility_label' => RecipeVisibility::FAMILY->label(),
                'prep_time' => 15,
                'cook_time' => 30,
                'servings' => 4,
            ],
        ]);

    $this->assertDatabaseHas('recipes', [
        'user_id' => $user->id,
        'title' => 'Grandma\'s Special Pasta',
        'visibility' => RecipeVisibility::FAMILY->value,
    ]);
});

it('can create a recipe with only required fields', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/recipes', [
        'title' => 'Simple Recipe',
    ]);

    $response->assertCreated()
        ->assertJson([
            'success' => true,
            'message' => 'Recipe created successfully.',
            'data' => [
                'title' => 'Simple Recipe',
                'visibility' => RecipeVisibility::PRIVATE->value,
            ],
        ]);
});

it('can create a recipe associated with a family', function (): void {
    $user = User::factory()->create();
    $family = Family::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user)->postJson('/api/recipes', [
        'title' => 'Family Secret Recipe',
        'family_id' => $family->id,
        'visibility' => RecipeVisibility::FAMILY->value,
    ]);

    $response->assertCreated()
        ->assertJson([
            'success' => true,
            'data' => [
                'family_id' => $family->id,
            ],
        ]);
});

it('returns unauthorized when not authenticated', function (): void {
    $response = $this->postJson('/api/recipes', [
        'title' => 'Test Recipe',
    ]);

    $response->assertUnauthorized();
});

it('validates title is required', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/recipes', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['title']);
});

it('validates title minimum length', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/recipes', [
        'title' => 'Ab',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['title']);
});

it('validates visibility is a valid enum value', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/recipes', [
        'title' => 'Test Recipe',
        'visibility' => 'invalid',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['visibility']);
});

it('validates family_id exists', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/recipes', [
        'title' => 'Test Recipe',
        'family_id' => 99999,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['family_id']);
});

it('validates prep_time is a non-negative integer', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/recipes', [
        'title' => 'Test Recipe',
        'prep_time' => -5,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['prep_time']);
});

it('validates servings is at least 1', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/recipes', [
        'title' => 'Test Recipe',
        'servings' => 0,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['servings']);
});
