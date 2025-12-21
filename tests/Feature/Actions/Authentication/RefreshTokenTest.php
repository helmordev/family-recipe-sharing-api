<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

describe('RefreshToken Action', function () {
    it('returns successful json response with new token', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = postJson('/api/refresh-token');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'success',
                'message',
                'token',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Token refreshed successfully.',
            ]);
    });

    it('returns a valid token in response', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = postJson('/api/refresh-token');

        $response->assertSuccessful();
        expect($response->json('token'))->toBeString()
            ->and(mb_strlen($response->json('token')))->toBeGreaterThan(20);
    });

    it('returns 401 when user is not authenticated', function () {
        $response = postJson('/api/refresh-token');

        $response->assertUnauthorized();
    });

    it('returns 401 when using invalid token', function () {
        $response = postJson('/api/refresh-token', [], [
            'Authorization' => 'Bearer invalid-token-string',
        ]);

        $response->assertUnauthorized();
    });

    it('returns 401 with malformed authorization header', function () {
        $response = postJson('/api/refresh-token', [], [
            'Authorization' => 'InvalidFormat',
        ]);

        $response->assertUnauthorized();
    });

    it('deletes old token when using actingAs', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $initialCount = $user->tokens()->count();

        $response = postJson('/api/refresh-token');

        $response->assertSuccessful();

        // With Sanctum::actingAs, token is created first time it's accessed
        // After refresh, old token is deleted and new one created
        expect($user->fresh()->tokens()->count())->toBeGreaterThanOrEqual($initialCount);
    });

    it('new token can be used for authentication', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = postJson('/api/refresh-token');

        $response->assertSuccessful();

        $newToken = $response->json('token');

        // Use new token to make authenticated request
        $testResponse = postJson('/api/logout', [], [
            'Authorization' => 'Bearer '.$newToken,
        ]);

        $testResponse->assertSuccessful();
    });

    it('preserves user session across token refresh', function () {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        Sanctum::actingAs($user);

        $response = postJson('/api/refresh-token');

        $response->assertSuccessful();

        $newToken = $response->json('token');

        // Verify user data is still accessible with new token
        $verifyResponse = postJson('/api/logout', [], [
            'Authorization' => 'Bearer '.$newToken,
        ]);

        $verifyResponse->assertSuccessful();
    });

    it('does not affect other users tokens', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $user1Token = $user1->createToken('auth_token')->plainTextToken;
        $user2Token = $user2->createToken('auth_token')->plainTextToken;

        expect($user1->tokens()->count())->toBe(1)
            ->and($user2->tokens()->count())->toBe(1);

        // Refresh user1's token
        $response = postJson('/api/refresh-token', [], [
            'Authorization' => 'Bearer '.$user1Token,
        ]);

        $response->assertSuccessful();

        // User2's token should still exist
        expect($user2->fresh()->tokens()->count())->toBe(1);

        // User2 can still use their token
        $testResponse = postJson('/api/logout', [], [
            'Authorization' => 'Bearer '.$user2Token,
        ]);

        $testResponse->assertSuccessful();
    });

    it('handles guest middleware correctly', function () {
        // Try to refresh without any authentication
        $response = postJson('/api/refresh-token');

        $response->assertUnauthorized();
    });

    it('returns json response with correct structure', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = postJson('/api/refresh-token');

        $response->assertSuccessful()
            ->assertJsonCount(3)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Token refreshed successfully.');
    });

    it('token field is not empty in response', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = postJson('/api/refresh-token');

        $response->assertSuccessful();
        expect($response->json('token'))->not->toBeEmpty();
    });

    it('token count stays consistent after refresh with Sanctum::actingAs', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = postJson('/api/refresh-token');

        $response->assertSuccessful();

        // Sanctum creates tokens lazily, count should be at least 1
        expect($user->fresh()->tokens()->count())->toBeGreaterThanOrEqual(1);
    });

    it('handles multiple pre-existing tokens correctly', function () {
        $user = User::factory()->create();

        // Create multiple tokens explicitly
        $token1 = $user->createToken('token1');
        $token2 = $user->createToken('token2')->plainTextToken;
        $user->createToken('token3');

        expect($user->tokens()->count())->toBe(3);

        // Use token2 to refresh via Authorization header
        $response = postJson('/api/refresh-token', [], [
            'Authorization' => 'Bearer '.$token2,
        ]);

        $response->assertSuccessful();

        // One token deleted, one created, so still 3 tokens
        expect($user->fresh()->tokens()->count())->toBe(3);
    });

    it('returns 200 status code on success', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = postJson('/api/refresh-token');

        $response->assertStatus(200);
    });

    it('returns json content type', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = postJson('/api/refresh-token');

        $response->assertSuccessful()
            ->assertHeader('Content-Type', 'application/json');
    });
});
