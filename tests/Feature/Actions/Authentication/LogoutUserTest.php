<?php

declare(strict_types=1);

use App\Actions\Authentication\LogoutUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

describe('LogoutUser Action - handle method', function () {
    it('deletes all user tokens', function () {
        $user = User::factory()->create();
        $user->createToken('token_1');
        $user->createToken('token_2');
        $user->createToken('token_3');

        expect($user->tokens()->count())->toBe(3);

        $request = request();
        $request->setUserResolver(fn () => $user);

        $action = new LogoutUser();
        $action->handle($request);

        expect($user->fresh()->tokens()->count())->toBe(0);
    });

    it('deletes only the authenticated user tokens', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $user1->createToken('user1_token');
        $user2->createToken('user2_token_1');
        $user2->createToken('user2_token_2');

        $request = request();
        $request->setUserResolver(fn () => $user1);

        $action = new LogoutUser();
        $action->handle($request);

        expect($user1->fresh()->tokens()->count())->toBe(0)
            ->and($user2->fresh()->tokens()->count())->toBe(2);
    });

    it('handles user with no tokens', function () {
        $user = User::factory()->create();

        expect($user->tokens()->count())->toBe(0);

        $request = request();
        $request->setUserResolver(fn () => $user);

        $action = new LogoutUser();
        $action->handle($request);

        expect($user->fresh()->tokens()->count())->toBe(0);
    });
});

describe('LogoutUser Action - as controller', function () {
    it('returns successful json response when user logs out', function () {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = postJson('/api/logout', [], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
            ]);
    });

    it('deletes all user tokens after logout', function () {
        $user = User::factory()->create();
        $user->createToken('token_1');
        $user->createToken('token_2');
        $token = $user->createToken('auth_token')->plainTextToken;

        expect($user->tokens()->count())->toBe(3);

        $response = postJson('/api/logout', [], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertSuccessful();
        expect($user->fresh()->tokens()->count())->toBe(0);
    });

    it('returns 401 when user is not authenticated', function () {
        $response = postJson('/api/logout');

        $response->assertUnauthorized();
    });

    it('returns 401 with invalid token', function () {
        $response = postJson('/api/logout', [], [
            'Authorization' => 'Bearer invalid_token_here',
        ]);

        $response->assertUnauthorized();
    });

    it('returns 401 with malformed authorization header', function () {
        $response = postJson('/api/logout', [], [
            'Authorization' => 'InvalidFormat',
        ]);

        $response->assertUnauthorized();
    });

    it('returns 401 with expired or deleted token', function () {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token');
        $plainTextToken = $token->plainTextToken;

        // Delete the token
        $token->accessToken->delete();

        $response = postJson('/api/logout', [], [
            'Authorization' => 'Bearer '.$plainTextToken,
        ]);

        $response->assertUnauthorized();
    });

    it('cannot use token after logout', function () {
        $user = User::factory()->create();
        $tokenResult = $user->createToken('auth_token');
        $token = $tokenResult->plainTextToken;

        // Verify token exists and works
        expect($user->tokens()->count())->toBe(1);

        // Logout - should succeed
        $response = postJson('/api/logout', [], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertSuccessful();

        // Verify token was deleted from database
        $user = $user->fresh();
        expect($user->tokens()->count())->toBe(0);

        // Verify the token is not valid anymore by checking Sanctum directly
        $foundToken = Laravel\Sanctum\PersonalAccessToken::findToken($token);
        expect($foundToken)->toBeNull();
    });

    it('allows different users to logout independently', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $tokenResult1 = $user1->createToken('auth_token');
        $token1 = $tokenResult1->plainTextToken;

        $tokenResult2 = $user2->createToken('auth_token');
        $token2 = $tokenResult2->plainTextToken;

        // Verify both users have tokens
        expect($user1->tokens()->count())->toBe(1)
            ->and($user2->tokens()->count())->toBe(1);

        // User 1 logs out
        $response1 = postJson('/api/logout', [], [
            'Authorization' => 'Bearer '.$token1,
        ]);

        $response1->assertSuccessful();

        // Refresh models from database
        $user1 = $user1->fresh();
        $user2 = $user2->fresh();

        // User 1's token should be deleted, User 2's should remain
        expect($user1->tokens()->count())->toBe(0)
            ->and($user2->tokens()->count())->toBe(1);
    });

    it('returns 200 status code on successful logout', function () {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = postJson('/api/logout', [], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(200);
    });

    it('returns correct content type', function () {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = postJson('/api/logout', [], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertHeader('Content-Type', 'application/json');
    });
});
