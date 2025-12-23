<?php

declare(strict_types=1);

use App\Actions\Family\CreateFamily;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

describe('CreateFamily Action - handle method', function () {
    it('creates a family with valid data', function () {
        $user = User::factory()->create();
        $action = new CreateFamily();

        $family = $action->handle([
            'name' => 'Smith Family',
        ], $user);

        expect($family)->toBeInstanceOf(Family::class)
            ->and($family->name)->toBe('Smith Family')
            ->and($family->owner_id)->toBe($user->id);

        assertDatabaseHas('families', [
            'name' => 'Smith Family',
            'owner_id' => $user->id,
        ]);
    });

    it('attaches the creator as a family member with owner role', function () {
        $user = User::factory()->create();
        $action = new CreateFamily();

        $family = $action->handle([
            'name' => 'Johnson Family',
        ], $user);

        assertDatabaseHas('family_user', [
            'family_id' => $family->id,
            'user_id' => $user->id,
            'role' => 'admin',
        ]);

        expect($family->members()->count())->toBe(1)
            ->and($family->members->first()->id)->toBe($user->id);
    });

    it('sets joined_at timestamp when adding member', function () {
        $user = User::factory()->create();
        $action = new CreateFamily();

        $family = $action->handle([
            'name' => 'Williams Family',
        ], $user);

        $member = $family->members()->wherePivot('user_id', $user->id)->first();

        expect($member->pivot->joined_at)->not->toBeNull();
    });

    it('creates family within a database transaction', function () {
        $user = User::factory()->create();
        $action = new CreateFamily();

        // This ensures that if an error occurs, nothing is saved
        $family = $action->handle([
            'name' => 'Brown Family',
        ], $user);

        // Check both family and membership were created
        assertDatabaseHas('families', ['id' => $family->id]);
        assertDatabaseHas('family_user', ['family_id' => $family->id, 'user_id' => $user->id]);
    });

    it('handles long family names within limit', function () {
        $user = User::factory()->create();
        $action = new CreateFamily();
        $longName = str_repeat('a', 255);

        $family = $action->handle([
            'name' => $longName,
        ], $user);

        expect($family->name)->toBe($longName)
            ->and(mb_strlen($family->name))->toBe(255);
    });

    it('handles family names with special characters', function () {
        $user = User::factory()->create();
        $action = new CreateFamily();

        $family = $action->handle([
            'name' => "O'Brien-Smith Family & Friends",
        ], $user);

        expect($family->name)->toBe("O'Brien-Smith Family & Friends");
    });
});

describe('CreateFamily Action - validation rules', function () {
    it('returns correct validation rules', function () {
        $action = new CreateFamily();
        $rules = $action->rules();

        expect($rules)->toHaveKey('name')
            ->and($rules['name'])->toContain('required')
            ->and($rules['name'])->toContain('string')
            ->and($rules['name'])->toContain('min:3')
            ->and($rules['name'])->toContain('max:255');
    });
});

describe('CreateFamily Action - asController method', function () {
    it('creates a family via controller with authenticated user', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/families', [
            'name' => 'Davis Family',
        ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Family created successfully.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'owner_id',
                    'created_at',
                    'updated_at',
                ],
            ]);

        assertDatabaseHas('families', [
            'name' => 'Davis Family',
            'owner_id' => $user->id,
        ]);
    });

    it('fails to create family without authentication', function () {
        $response = $this->postJson('/api/families', [
            'name' => 'Miller Family',
        ]);

        $response->assertUnauthorized();
    });

    it('fails validation when name is missing', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/families', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('fails validation when name is too short', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/families', [
            'name' => 'AB',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('fails validation when name is too long', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/families', [
            'name' => str_repeat('a', 256),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('fails validation when name is not a string', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/families', [
            'name' => 12345,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('allows minimum length name', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/families', [
            'name' => 'ABC',
        ]);

        $response->assertCreated();
    });

    it('allows maximum length name', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/families', [
            'name' => str_repeat('a', 255),
        ]);

        $response->assertCreated();
    });
});

describe('CreateFamily Action - jsonResponse method', function () {
    it('returns correct JSON response structure', function () {
        $user = User::factory()->create();
        $action = new CreateFamily();

        $family = $action->handle(['name' => 'Wilson Family'], $user);
        $response = $action->jsonResponse($family);

        expect($response->getStatusCode())->toBe(201)
            ->and($response->getData(true))->toMatchArray([
                'success' => true,
                'message' => 'Family created successfully.',
            ])
            ->and($response->getData(true)['data'])->toHaveKeys([
                'id',
                'name',
                'owner_id',
                'created_at',
                'updated_at',
            ]);
    });
});
