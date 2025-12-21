<?php

declare(strict_types=1);

namespace App\Actions\Authentication;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Validation\Rules\Password;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use Symfony\Component\HttpFoundation\JsonResponse;

final class RegisterUser
{
    use AsAction;

    /**
     * @param  array{name: string, email: string, password: string}  $credentials
     * @return array{user: User, token: string}
     */
    public function handle(array $credentials): array
    {
        $user = User::create([
            'name' => $credentials['name'],
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * @return array<string, array<int, string|Password>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array{user: User, token: string}
     */
    public function asController(ActionRequest $request): array
    {
        return $this->handle($request->validated());
    }

    /**
     * @param  array{user: User, token: string}  $result
     */
    public function jsonResponse(array $result): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'User registered successfully.',
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ],
        ], 201);
    }
}
