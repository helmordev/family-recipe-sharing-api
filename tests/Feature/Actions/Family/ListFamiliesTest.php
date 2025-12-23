<?php

declare(strict_types=1);

use App\Actions\Family\ListFamilies;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('ListFamilies Action - handle method', function () {
    it('returns all families for a user', function () {
        $user = User::factory()->create();
        $family1 = Family::factory()->create(['owner_id' => $user->id]);
        $family2 = Family::factory()->create(['owner_id' => $user->id]);

        $user->families()->attach($family1->id, ['role' => 'admin', 'joined_at' => now()]);
        $user->families()->attach($family2->id, ['role' => 'admin', 'joined_at' => now()]);

        $action = new ListFamilies();
        $families = $action->handle($user);

        expect($families->count())->toBe(2)
            ->and($families->pluck('id')->toArray())->toContain($family1->id, $family2->id);
    });

    it('returns empty collection when user has no families', function () {
        $user = User::factory()->create();

        $action = new ListFamilies();
        $families = $action->handle($user);

        expect($families->count())->toBe(0)
            ->and($families->isEmpty())->toBeTrue();
    });

    it('eager loads owner relationship', function () {
        $user = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $user->id]);
        $user->families()->attach($family->id, ['role' => 'admin', 'joined_at' => now()]);

        $action = new ListFamilies();
        $families = $action->handle($user);

        expect($families->first()->relationLoaded('owner'))->toBeTrue()
            ->and($families->first()->owner->id)->toBe($user->id);
    });

    it('eager loads members relationship', function () {
        $user = User::factory()->create();
        $member = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $user->id]);

        $user->families()->attach($family->id, ['role' => 'admin', 'joined_at' => now()]);
        $family->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $action = new ListFamilies();
        $families = $action->handle($user);

        expect($families->first()->relationLoaded('members'))->toBeTrue()
            ->and($families->first()->members->count())->toBe(2);
    });

    it('returns families where user is a member but not owner', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $owner->id]);

        $owner->families()->attach($family->id, ['role' => 'admin', 'joined_at' => now()]);
        $member->families()->attach($family->id, ['role' => 'member', 'joined_at' => now()]);

        $action = new ListFamilies();
        $families = $action->handle($member);

        expect($families->count())->toBe(1)
            ->and($families->first()->id)->toBe($family->id)
            ->and($families->first()->owner_id)->toBe($owner->id);
    });

    it('returns only families the user belongs to', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $family1 = Family::factory()->create(['owner_id' => $user1->id]);
        $family2 = Family::factory()->create(['owner_id' => $user2->id]);

        $user1->families()->attach($family1->id, ['role' => 'admin', 'joined_at' => now()]);
        $user2->families()->attach($family2->id, ['role' => 'admin', 'joined_at' => now()]);

        $action = new ListFamilies();
        $families = $action->handle($user1);

        expect($families->count())->toBe(1)
            ->and($families->first()->id)->toBe($family1->id)
            ->and($families->pluck('id')->toArray())->not->toContain($family2->id);
    });

    it('handles multiple families with multiple members', function () {
        $owner = User::factory()->create();
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();

        $family1 = Family::factory()->create(['owner_id' => $owner->id]);
        $family2 = Family::factory()->create(['owner_id' => $owner->id]);

        $owner->families()->attach($family1->id, ['role' => 'admin', 'joined_at' => now()]);
        $owner->families()->attach($family2->id, ['role' => 'admin', 'joined_at' => now()]);

        $family1->members()->attach($member1->id, ['role' => 'member', 'joined_at' => now()]);
        $family1->members()->attach($member2->id, ['role' => 'member', 'joined_at' => now()]);

        $action = new ListFamilies();
        $families = $action->handle($owner);

        expect($families->count())->toBe(2);
    });
});

describe('ListFamilies Action - asController method', function () {
    it('returns families for authenticated user via controller', function () {
        $user = User::factory()->create();
        $family1 = Family::factory()->create(['owner_id' => $user->id]);
        $family2 = Family::factory()->create(['owner_id' => $user->id]);

        $user->families()->attach($family1->id, ['role' => 'admin', 'joined_at' => now()]);
        $user->families()->attach($family2->id, ['role' => 'admin', 'joined_at' => now()]);

        $response = $this->actingAs($user)->getJson('/api/families');

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'message' => 'Families retrieved successfully',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'owner_id',
                        'created_at',
                        'updated_at',
                        'owner',
                        'members',
                    ],
                ],
            ])
            ->assertJsonCount(2, 'data');
    });

    it('returns empty array when user has no families', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/families');

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'message' => 'Families retrieved successfully',
                'data' => [],
            ]);
    });

    it('requires authentication to list families', function () {
        $response = $this->getJson('/api/families');

        $response->assertUnauthorized();
    });

    it('includes owner details in response', function () {
        $user = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $user->id]);
        $user->families()->attach($family->id, ['role' => 'admin', 'joined_at' => now()]);

        $response = $this->actingAs($user)->getJson('/api/families');

        $response->assertSuccessful()
            ->assertJsonPath('data.0.owner.id', $user->id);
    });

    it('includes members details in response', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $owner->id]);

        $owner->families()->attach($family->id, ['role' => 'admin', 'joined_at' => now()]);
        $family->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $response = $this->actingAs($owner)->getJson('/api/families');

        $response->assertSuccessful()
            ->assertJsonPath('data.0.members.0.id', $owner->id)
            ->assertJsonPath('data.0.members.1.id', $member->id);
    });

    it('returns different families for different users', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $family1 = Family::factory()->create(['owner_id' => $user1->id]);
        $family2 = Family::factory()->create(['owner_id' => $user2->id]);

        $user1->families()->attach($family1->id, ['role' => 'admin', 'joined_at' => now()]);
        $user2->families()->attach($family2->id, ['role' => 'admin', 'joined_at' => now()]);

        $response1 = $this->actingAs($user1)->getJson('/api/families');
        $response2 = $this->actingAs($user2)->getJson('/api/families');

        $response1->assertJsonPath('data.0.id', $family1->id);
        $response2->assertJsonPath('data.0.id', $family2->id);
    });
});

describe('ListFamilies Action - jsonResponse method', function () {
    it('returns correct JSON response structure', function () {
        $user = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $user->id]);
        $user->families()->attach($family->id, ['role' => 'admin', 'joined_at' => now()]);

        $action = new ListFamilies();
        $families = $action->handle($user);
        $response = $action->jsonResponse($families);

        expect($response->getStatusCode())->toBe(200)
            ->and($response->getData(true))->toMatchArray([
                'success' => true,
                'message' => 'Families retrieved successfully',
            ])
            ->and($response->getData(true)['data'])->toBeArray()
            ->and(count($response->getData(true)['data']))->toBe(1);
    });

    it('returns empty data array when no families', function () {
        $user = User::factory()->create();

        $action = new ListFamilies();
        $families = $action->handle($user);
        $response = $action->jsonResponse($families);

        expect($response->getStatusCode())->toBe(200)
            ->and($response->getData(true)['data'])->toBeArray()
            ->and(count($response->getData(true)['data']))->toBe(0);
    });
});
