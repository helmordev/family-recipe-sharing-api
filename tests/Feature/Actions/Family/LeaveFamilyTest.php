<?php

declare(strict_types=1);

use App\Actions\Family\LeaveFamily;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\assertDatabaseMissing;

uses(RefreshDatabase::class);

describe('LeaveFamily Action - handle method', function () {
    it('allows a member to leave the family', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $owner->id]);

        $family->members()->attach($owner->id, ['role' => 'admin', 'joined_at' => now()]);
        $family->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $action = new LeaveFamily();
        $action->handle($family, $member);

        assertDatabaseMissing('family_user', [
            'family_id' => $family->id,
            'user_id' => $member->id,
        ]);

        expect($family->members()->where('user_id', $member->id)->exists())->toBeFalse();
    });

    it('prevents owner from leaving the family', function () {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $owner->id]);

        $family->members()->attach($owner->id, ['role' => 'admin', 'joined_at' => now()]);

        $action = new LeaveFamily();

        $action->handle($family, $owner);
    })->throws(ValidationException::class, 'The owner of the family cannot leave the family. Please transfer ownership or delete the family.');

    it('throws exception when user is not a member', function () {
        $owner = User::factory()->create();
        $nonMember = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $owner->id]);

        $family->members()->attach($owner->id, ['role' => 'admin', 'joined_at' => now()]);

        $action = new LeaveFamily();

        $action->handle($family, $nonMember);
    })->throws(ValidationException::class, 'You are not a member of this family.');

    it('removes only the specified member', function () {
        $owner = User::factory()->create();
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $owner->id]);

        $family->members()->attach($owner->id, ['role' => 'admin', 'joined_at' => now()]);
        $family->members()->attach($member1->id, ['role' => 'member', 'joined_at' => now()]);
        $family->members()->attach($member2->id, ['role' => 'member', 'joined_at' => now()]);

        $action = new LeaveFamily();
        $action->handle($family, $member1);

        expect($family->members()->count())->toBe(2)
            ->and($family->members()->where('user_id', $member1->id)->exists())->toBeFalse()
            ->and($family->members()->where('user_id', $member2->id)->exists())->toBeTrue();
    });
});

describe('LeaveFamily Action - asController method', function () {
    it('allows member to leave family via controller', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $owner->id]);

        $family->members()->attach($owner->id, ['role' => 'admin', 'joined_at' => now()]);
        $family->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $response = $this->actingAs($member)->postJson("/api/families/{$family->id}/leave");

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'message' => 'You have left the family successfully.',
            ]);

        assertDatabaseMissing('family_user', [
            'family_id' => $family->id,
            'user_id' => $member->id,
        ]);
    });

    it('prevents owner from leaving family via controller', function () {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $owner->id]);

        $family->members()->attach($owner->id, ['role' => 'admin', 'joined_at' => now()]);

        $response = $this->actingAs($owner)->postJson("/api/families/{$family->id}/leave");

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['family']);
    });

    it('prevents non-member from leaving family via controller', function () {
        $owner = User::factory()->create();
        $nonMember = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $owner->id]);

        $family->members()->attach($owner->id, ['role' => 'admin', 'joined_at' => now()]);

        $response = $this->actingAs($nonMember)->postJson("/api/families/{$family->id}/leave");

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['family']);
    });

    it('requires authentication to leave family', function () {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $owner->id]);

        $response = $this->postJson("/api/families/{$family->id}/leave");

        $response->assertUnauthorized();
    });

    it('returns 404 for non-existent family', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/families/99999/leave');

        $response->assertNotFound();
    });
});

describe('LeaveFamily Action - jsonResponse method', function () {
    it('returns correct JSON response structure', function () {
        $action = new LeaveFamily();
        $response = $action->jsonResponse();

        expect($response->getStatusCode())->toBe(200)
            ->and($response->getData(true))->toMatchArray([
                'success' => true,
                'message' => 'You have left the family successfully.',
            ]);
    });
});

describe('LeaveFamily Action - error messages', function () {
    it('provides clear error message when owner tries to leave', function () {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $owner->id]);
        $family->members()->attach($owner->id, ['role' => 'admin', 'joined_at' => now()]);

        $action = new LeaveFamily();

        try {
            $action->handle($family, $owner);
        } catch (ValidationException $e) {
            expect($e->errors()['family'][0])->toBe('The owner of the family cannot leave the family. Please transfer ownership or delete the family.');
        }
    });

    it('provides clear error message when non-member tries to leave', function () {
        $owner = User::factory()->create();
        $nonMember = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $owner->id]);
        $family->members()->attach($owner->id, ['role' => 'admin', 'joined_at' => now()]);

        $action = new LeaveFamily();

        try {
            $action->handle($family, $nonMember);
        } catch (ValidationException $e) {
            expect($e->errors()['family'][0])->toBe('You are not a member of this family.');
        }
    });
});
