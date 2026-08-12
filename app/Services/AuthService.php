<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthService
{
    public function __construct(protected TelegramWebAppAuthService $telegramWebAppAuth) {}

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('phone', $credentials['phone'])->first();

        if (! $user || ! $user->password || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        return response()->json([
            'message' => 'Logged in successfully.',
            'user' => $user,
            'token' => $user->createToken('auth-token')->plainTextToken,
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create($data);

        return response()->json([
            'message' => 'Registered successfully.',
            'user' => $user,
            'token' => $user->createToken('auth-token')->plainTextToken,
        ], 201);
    }

    public function telegram(Request $request)
    {
        $initData = $request->input('init_data');

        if (! is_string($initData) || $initData === '') {
            return response()->json([
                'message' => 'Telegram init data is required.',
            ], 422);
        }

        $telegramUser = $this->telegramWebAppAuth->verify($initData);

        if ($telegramUser === null) {
            Log::warning('Telegram Web App authentication failed.');

            return response()->json([
                'message' => 'Invalid or expired Telegram authentication data.',
            ], 401);
        }

        $telegramId = (string) $telegramUser['id'];
        $user = User::where('telegram_id', $telegramId)->first();

        if (! $user) {
            try {
                $user = User::create([
                    'telegram_id' => $telegramId,
                    'username' => $this->makeUniqueTelegramUsername($telegramUser['username'] ?? null, $telegramId),
                    'phone' => null,
                    'email' => null,
                    'password' => null,
                ]);
            } catch (QueryException) {
                $user = User::where('telegram_id', $telegramId)->first();

                if (! $user) {
                    Log::warning('Unable to create a Weeplay account from verified Telegram data.');

                    return response()->json([
                        'message' => 'Unable to create a Weeplay account for this Telegram user.',
                    ], 409);
                }
            }
        }

        return response()->json([
            'message' => 'Telegram authentication successful.',
            'user' => $user,
            'token' => $user->createToken('auth-token')->plainTextToken,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    private function makeUniqueTelegramUsername(mixed $username, string $telegramId): string
    {
        $base = is_string($username) && $username !== ''
            ? Str::limit($username, 230, '')
            : "telegram_{$telegramId}";

        $candidate = $base;
        $suffix = 1;

        while (User::where('username', $candidate)->exists()) {
            $candidate = Str::limit($base, 240 - strlen((string) $suffix), '')."_{$suffix}";
            $suffix++;
        }

        return $candidate;
    }
}
