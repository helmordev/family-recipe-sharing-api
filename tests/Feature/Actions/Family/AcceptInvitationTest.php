<?php

declare(strict_types=1);

use App\Actions\Family\AcceptInvitation;
use App\Models\Family;
use App\Models\FamilyInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Route::middleware('auth:sanctum')->post('/api/families/accept-invitation', AcceptInvitation::class);
});

describe('AcceptInvitation Action - handle method', function () {
    it('attaches user to family and marks invitation used', function () {
        $family = Family::factory()->create();
        $user = User::factory()->create();
        $invitation = FamilyInvitation::create([
            'family_id' => $family->id,
            'code' => 'ACCEPT01',
            'email' => 'member@example.com',
            'expires_at' => now()->addDay(),
        ]);

        $action = new AcceptInvitation();
        $result = $action->handle($invitation->code, $user);

        expect($result->id)->toBe($family->id);

        assertDatabaseHas('family_user', [
            'family_id' => $family->id,
            'user_id' => $user->id,
            'role' => 'member',
        ]);

        $family->refresh();
        $membership = $family->members()->where('users.id', $user->id)->first();
        expect($membership?->pivot->joined_at)->not->toBeNull();

        $invitation->refresh();
        expect($invitation->used_at)->not->toBeNull();
    });

    it('throws exception when invitation is already used', function () {
        $family = Family::factory()->create();
        $user = User::factory()->create();
        $invitation = FamilyInvitation::create([
            'family_id' => $family->id,
            'code' => 'USED0001',
            'used_at' => now(),
        ]);

        $action = new AcceptInvitation();
        $action->handle($invitation->code, $user);
    })->expectException(ModelNotFoundException::class);

    it('throws exception when invitation is expired', function () {
        $family = Family::factory()->create();
        $user = User::factory()->create();
        $invitation = FamilyInvitation::create([
            'family_id' => $family->id,
            'code' => 'EXPIRE01',
            'expires_at' => now()->subDay(),
        ]);

        $action = new AcceptInvitation();
        $action->handle($invitation->code, $user);
    })->expectException(ModelNotFoundException::class);
});

describe('AcceptInvitation Action - validation rules', function () {
    it('returns correct validation rules', function () {
        $action = new AcceptInvitation();
        $rules = $action->rules();

        expect($rules)->toHaveKey('code')
            ->and($rules['code'])->toContain('required')
            ->and($rules['code'])->toContain('string')
            ->and($rules['code'])->toContain('exists:family_invitations,code');
    });
});

describe('AcceptInvitation Action - jsonResponse method', function () {
    it('returns expected json structure', function () {
        $family = Family::factory()->create();

        $response = (new AcceptInvitation())->jsonResponse($family);

        expect($response->getStatusCode())->toBe(200);

        $data = $response->getData(true);

        expect($data)->toMatchArray([
            'success' => true,
            'message' => 'Joined family successfully.',
        ]);

        expect($data['data']['id'] ?? null)->toBe($family->id);
    });
});

describe('AcceptInvitation Action - asController method', function () {
    it('accepts invitation via http request', function () {
        $family = Family::factory()->create();
        $user = User::factory()->create();
        $invitation = FamilyInvitation::create([
            'family_id' => $family->id,
            'code' => 'JOIN0001',
            'expires_at' => now()->addDay(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/families/accept-invitation', [
            'code' => $invitation->code,
        ]);

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'message' => 'Joined family successfully.',
            ])
            ->assertJsonPath('data.id', $family->id);

        assertDatabaseHas('family_user', [
            'family_id' => $family->id,
            'user_id' => $user->id,
        ]);

        $invitation->refresh();
        expect($invitation->used_at)->not->toBeNull();
    });

    it('validates presence of code', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/families/accept-invitation', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);
    });

    it('returns not found for invalid or unusable invitation', function () {
        $family = Family::factory()->create();
        $user = User::factory()->create();
        $invitation = FamilyInvitation::create([
            'family_id' => $family->id,
            'code' => 'STALE001',
            'used_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/families/accept-invitation', [
            'code' => $invitation->code,
        ]);

        $response->assertNotFound();
    });

    it('rejects non-existent invitation code', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/families/accept-invitation', [
            'code' => 'MISSING01',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);
    });
});
