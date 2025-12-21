<?php

declare(strict_types=1);

use App\Actions\Authentication\RegisterUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

describe('RegisterUser Action - handle method', function () {
    it('creates a user with valid credentials', function () {
        $credentials = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        $result = (new RegisterUser())->handle($credentials);

        expect($result)
            ->toBeArray()
            ->toHaveKeys(['user', 'token']);

        expect($result['user'])
            ->toBeInstanceOf(User::class)
            ->name->toBe('John Doe')
            ->email->toBe('john@example.com');

        expect($result['token'])->toBeString()->not->toBeEmpty();

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        expect(Hash::check('password123', $result['user']->password))->toBeTrue();
    });

    it('hashes the password correctly', function () {
        $credentials = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'SecurePassword123!',
        ];

        $result = (new RegisterUser())->handle($credentials);

        expect($result['user']->password)
            ->not->toBe('SecurePassword123!')
            ->and(Hash::check('SecurePassword123!', $result['user']->password))
            ->toBeTrue();
    });

    it('generates a valid sanctum token', function () {
        $credentials = [
            'name' => 'Token User',
            'email' => 'token@example.com',
            'password' => 'password123',
        ];

        $result = (new RegisterUser())->handle($credentials);

        expect($result['token'])
            ->toBeString()
            ->not->toBeEmpty();

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $result['user']->id,
            'tokenable_type' => User::class,
            'name' => 'auth_token',
        ]);
    });
});

describe('RegisterUser Action - validation rules', function () {
    it('requires name field', function () {
        $response = $this->postJson('/api/register', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('requires name to be a string', function () {
        $response = $this->postJson('/api/register', [
            'name' => 12345,
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('requires name to not exceed 255 characters', function () {
        $response = $this->postJson('/api/register', [
            'name' => str_repeat('a', 256),
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('requires email field', function () {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('requires email to be a string', function () {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 12345,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('requires email to be a valid email format', function () {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('requires email to not exceed 255 characters', function () {
        $longEmail = str_repeat('a', 250) . '@test.com';

        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => $longEmail,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('requires email to be unique', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('requires password field', function () {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'test@example.com',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('requires password to be a string', function () {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 12345678,
            'password_confirmation' => 12345678,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('requires password confirmation', function () {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('requires password confirmation to match', function () {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'DifferentPassword123!',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('validates password meets default requirements', function () {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });
});

describe('RegisterUser Action - controller endpoint', function () {
    it('successfully registers a user via POST request', function () {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                    ],
                    'token',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'User registered successfully.',
                'data' => [
                    'user' => [
                        'name' => 'John Doe',
                        'email' => 'john@example.com',
                    ],
                ],
            ]);

        expect($response->json('data.token'))->toBeString()->not->toBeEmpty();

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    });

    it('returns 201 status code on successful registration', function () {
        $response = $this->postJson('/api/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201);
    });

    it('does not expose password in response', function () {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertCreated()
            ->assertJsonMissing(['password']);

        expect($response->json('data.user'))->not->toHaveKey('password');
    });

    it('returns valid token format', function () {
        $response = $this->postJson('/api/register', [
            'name' => 'Auth User',
            'email' => 'auth@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertCreated();

        $token = $response->json('data.token');

        expect($token)
            ->toBeString()
            ->not->toBeEmpty()
            ->and(str_contains($token, '|'))
            ->toBeTrue();
    });

    it('creates only one user per registration', function () {
        $initialCount = User::count();

        $this->postJson('/api/register', [
            'name' => 'Single User',
            'email' => 'single@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        expect(User::count())->toBe($initialCount + 1);
    });

    it('handles multiple validation errors simultaneously', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => '',
            'email' => 'invalid-format',
            'password' => 'weak',
            'password_confirmation' => 'different',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    });

    it('accepts name with whitespace', function () {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'whitespace@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => 'whitespace@example.com',
        ]);
    });

    it('accepts uppercase email', function () {
        $response = $this->postJson('/api/register', [
            'name' => 'Case User',
            'email' => 'TEST@EXAMPLE.COM',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => 'TEST@EXAMPLE.COM',
        ]);
    });
});

describe('RegisterUser Action - edge cases', function () {
    it('handles special characters in name', function () {
        $response = $this->postJson('/api/register', [
            'name' => "O'Brien-Smith Jr.",
            'email' => 'special@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('users', [
            'name' => "O'Brien-Smith Jr.",
            'email' => 'special@example.com',
        ]);
    });

    it('handles unicode characters in name', function () {
        $response = $this->postJson('/api/register', [
            'name' => 'José María García',
            'email' => 'unicode@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('users', [
            'name' => 'José María García',
        ]);
    });

    it('handles complex email formats', function () {
        $response = $this->postJson('/api/register', [
            'name' => 'Complex Email',
            'email' => 'user+tag@sub.example.co.uk',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => 'user+tag@sub.example.co.uk',
        ]);
    });

    it('rejects when all fields are missing', function () {
        $response = $this->postJson('/api/register', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    });

    it('rejects empty request body', function () {
        $response = $this->json('POST', '/api/register', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    });
});
