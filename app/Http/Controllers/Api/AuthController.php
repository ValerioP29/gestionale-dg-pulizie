<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\AuthenticatedUserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthController
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $user = $guard->user();

        $user->loadMissing('mainSite');

        // Regenerate session for web guard compatibility, even though the PWA relies on bearer tokens.
        $request->session()->regenerate();

        // Invalidate previous tokens to avoid multiple active sessions per user.
        $user->tokens()->delete();

        $token = $user->createToken('api')->plainTextToken;

        Log::info('API login successful', ['user_id' => $user->id]);

        return response()->json([
            'token' => $token,
            'user' => AuthenticatedUserResource::make($user)->resolve(),
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        // Revoke the current bearer token when present (PWA flow).
        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('API logout', ['user_id' => optional($user)->id]);

        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        $user?->loadMissing('mainSite');

        Log::info('API me requested', ['user_id' => optional($user)->id]);

        return AuthenticatedUserResource::make($user);
    }
}
