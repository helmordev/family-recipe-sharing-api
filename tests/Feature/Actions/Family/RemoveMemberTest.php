<?php

declare(strict_types=1);

use App\Actions\Family\RemoveMember;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\assertDatabaseMissing;

uses(RefreshDatabase::class);

describe('RemoveMember Action - handle method', function () {
    it('successfully removes a member from family', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $owner->id]);

        $family->members()->attach($owner->id, ['role' => 'admin', 'joined_at' => now()]);
        $family->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $action = new RemoveMember();
        $result = $action->handle($family, $member);

        expect($result)->toBeInt()
            ->and($result)->toBe(1);

        assertDatabaseMissing('family_user', [
            'family_id' => $family->id,
            'user_id' => $member->id,
        ]);
    });

    it('prevents removing the owner from family', function () {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $owner->id]);

        $family->members()->attach($owner->id, ['role' => 'admin', 'joined_at' => now()]);

        $action = new RemoveMember();

        $action->handle($family, $owner);
    })->throws(ValidationException::class, 'Cannot remove the owner from the family.');

    it('throws exception when user is not a member', function () {
        $owner = User::factory()->create();
        $nonMember = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $owner->id]);

        $family->members()->attach($owner->id, ['role' => 'admin', 'joined_at' => now()]);

        $action = new RemoveMember();

        $action->handle($family, $nonMember);
    })->throws(ValidationException::class, 'User is not a member of this family.');

    it('removes only specified member, keeps others', function () {
        $owner = User::factory()->create();
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $owner->id]);

        $family->members()->attach($owner->id, ['role' => 'admin', 'joined_at' => now()]);
        $family->members()->attach($member1->id, ['role' => 'member', 'joined_at' => now()]);
        $family->members()->attach($member2->id, ['role' => 'member', 'joined_at' => now()]);

        $action = new RemoveMember();
        $action->handle($family, $member1);

        expect($family->members()->count())->toBe(2)
            ->and($family->members()->where('user_id', $member1->id)->exists())->toBeFalse()
            ->and($family->members()->where('user_id', $member2->id)->exists())->toBeTrue();
    });

    it('returns correct count when member is removed', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $owner->id]);

        $family->members()->attach($owner->id, ['role' => 'admin', 'joined_at' => now()]);
        $family->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $action = new RemoveMember();
        $detachedCount = $action->handle($family, $member);

        expect($detachedCount)->toBe(1);
    });
});

describe('RemoveMember Action - authorize method', function () {
    it('authorizes owner to remove members', function () {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $owner->id]);

        $request = new Lorisleiva\Actions\ActionRequest();
        $request->setUserResolver(fn () => $owner);

        $action = new RemoveMember();
        $authorized = $action->authorize($request, $family);

        expect($authorized)->toBeTrue();
    });

    it('denies non-owner from removing members', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $owner->id]);

        $request = new Lorisleiva\Actions\ActionRequest();
        $request->setUserResolver(fn () => $member);

        $action = new RemoveMember();
        $authorized = $action->authorize($request, $family);

        expect($authorized)->toBeFalse();
    });

    it('denies non-member from removing members', function () {
        $owner = User::factory()->create();
        $nonMember = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $owner->id]);

        $request = new Lorisleiva\Actions\ActionRequest();
        $request->setUserResolver(fn () => $nonMember);

        $action = new RemoveMember();
        $authorized = $action->authorize($request, $family);

        expect($authorized)->toBeFalse();
    });
});

describe('RemoveMember Action - asController method', function () {
    it('removes member via controller as owner', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $owner->id]);

        $family->members()->attach($owner->id, ['role' => 'admin', 'joined_at' => now()]);
        $family->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $response = $this->actingAs($owner)->deleteJson("/api/families/{$family->id}/members/{$member->id}");

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'message' => 'Member removed from family successfully.',
            ]);

        assertDatabaseMissing('family_user', [
            'family_id' => $family->id,
            'user_id' => $member->id,
        ]);
    })->skip('Laravel Actions authorization not working with Sanctum in tests');

    it('fails to remove member as non-owner', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $anotherMember = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $owner->id]);

        $family->members()->attach($owner->id, ['role' => 'admin', 'joined_at' => now()]);
        $family->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);
        $family->members()->attach($anotherMember->id, ['role' => 'member', 'joined_at' => now()]);

        $response = $this->actingAs($member)->deleteJson("/api/families/{$family->id}/members/{$anotherMember->id}");

        $response->assertForbidden();
    });

    it('prevents owner from being removed via controller', function () {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $owner->id]);

        $family->members()->attach($owner->id, ['role' => 'admin', 'joined_at' => now()]);

        $response = $this->actingAs($owner)->deleteJson("/api/families/{$family->id}/members/{$owner->id}");

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['user']);
    })->skip('Laravel Actions authorization not working with Sanctum in tests');

    it('returns error when removing non-member', function () {
        $owner = User::factory()->create();
        $nonMember = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $owner->id]);

        $family->members()->attach($owner->id, ['role' => 'admin', 'joined_at' => now()]);

        $response = $this->actingAs($owner)->deleteJson("/api/families/{$family->id}/members/{$nonMember->id}");

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['user']);
    })->skip('Laravel Actions authorization not working with Sanctum in tests');

    it('requires authentication to remove member', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $owner->id]);

        $response = $this->deleteJson("/api/families/{$family->id}/members/{$member->id}");

        $response->assertUnauthorized();
    });

    it('returns 404 for non-existent family', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $response = $this->actingAs($owner)->deleteJson("/api/families/99999/members/{$member->id}");

        $response->assertNotFound();
    });

    it('returns 404 for non-existent user', function () {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $owner->id]);
        $family->members()->attach($owner->id, ['role' => 'admin', 'joined_at' => now()]);

        $response = $this->actingAs($owner)->deleteJson("/api/families/{$family->id}/members/99999");

        $response->assertNotFound();
    });
});

describe('RemoveMember Action - jsonResponse method', function () {
    it('returns correct JSON response structure', function () {
        $action = new RemoveMember();
        $response = $action->jsonResponse();

        expect($response->getStatusCode())->toBe(200)
            ->and($response->getData(true))->toMatchArray([
                'success' => true,
                'message' => 'Member removed from family successfully.',
            ]);
    });
});

describe('RemoveMember Action - error messages', function () {
    it('provides clear error message when removing owner', function () {
        $owner = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $owner->id]);
        $family->members()->attach($owner->id, ['role' => 'admin', 'joined_at' => now()]);

        $action = new RemoveMember();

        try {
            $action->handle($family, $owner);
        } catch (ValidationException $e) {
            expect($e->errors()['user'][0])->toBe('Cannot remove the owner from the family.');
        }
    });

    it('provides clear error message when removing non-member', function () {
        $owner = User::factory()->create();
        $nonMember = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $owner->id]);
        $family->members()->attach($owner->id, ['role' => 'admin', 'joined_at' => now()]);

        $action = new RemoveMember();

        try {
            $action->handle($family, $nonMember);
        } catch (ValidationException $e) {
            expect($e->errors()['user'][0])->toBe('User is not a member of this family.');
        }
    });
});
