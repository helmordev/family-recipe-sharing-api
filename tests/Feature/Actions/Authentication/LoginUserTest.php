<?php

declare(strict_types=1);

use App\Actions\Authentication\LoginUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

describe('LoginUser Action - handle method', function () {
    it('logs in a user with valid credentials', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $action = new LoginUser();
        $result = $action->handle([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        expect($result)->toHaveKeys(['user', 'token'])
            ->and($result['user']->id)->toBe($user->id)
            ->and($result['token'])->toBeString();
    });

    it('throws validation exception with invalid email', function () {
        $action = new LoginUser();

        $action->handle([
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);
    })->throws(ValidationException::class, 'Invalid credentials.');

    it('throws validation exception with wrong password', function () {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct_password'),
        ]);

        $action = new LoginUser();

        $action->handle([
            'email' => 'test@example.com',
            'password' => 'wrong_password',
        ]);
    })->throws(ValidationException::class, 'Invalid credentials.');

    it('revokes old tokens when logging in', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Create some old tokens
        $user->createToken('old_token_1');
        $user->createToken('old_token_2');

        expect($user->tokens()->count())->toBe(2);

        $action = new LoginUser();
        $result = $action->handle([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Old tokens should be deleted, only new token exists
        expect($user->fresh()->tokens()->count())->toBe(1);
    });

    it('creates a new token with auth_token name', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $action = new LoginUser();
        $result = $action->handle([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $token = $user->fresh()->tokens()->first();
        expect($token->name)->toBe('auth_token');
    });

    it('returns a plain text token', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $action = new LoginUser();
        $result = $action->handle([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        expect($result['token'])->toBeString()
            ->and(mb_strlen($result['token']))->toBeGreaterThan(20);
    });
});

describe('LoginUser Action - validation rules', function () {
    it('requires email field', function () {
        $response = postJson('/api/login', [
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('requires email to be a valid email format', function () {
        $response = postJson('/api/login', [
            'email' => 'not-an-email',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('requires email to exist in users table', function () {
        $response = postJson('/api/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('requires password field', function () {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $response = postJson('/api/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('requires password to be a string', function () {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $response = postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 12345,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });
});

describe('LoginUser Action - as controller', function () {
    it('returns successful json response with valid credentials', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertSuccessful()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'User logged in successfully.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                    ],
                ],
            ]);
    });

    it('returns token in response', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertSuccessful();
        expect($response->json('data.token'))->toBeString()
            ->and(mb_strlen($response->json('data.token')))->toBeGreaterThan(20);
    });

    it('returns 422 with invalid credentials', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct_password'),
        ]);

        $response = postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('does not expose password in response', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertSuccessful()
            ->assertJsonMissing(['password']);
    });

    it('handles case sensitivity correctly for email', function () {
        $user = User::factory()->create([
            'email' => 'Test@Example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // This will fail if email comparison is case-sensitive
        // Adjust test expectation based on your requirements
        $response->assertUnprocessable();
    });
});
