<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\AuthenticatedUserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::guard('web')->attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::guard('web')->user();

        $user->loadMissing('mainSite');

        $user->tokens()->delete();

        $token = $user->createToken('api')->plainTextToken;

        Log::info('API login successful', ['user_id' => $user->id]);

        return response()->json([
            'token' => $token,
            'user' => AuthenticatedUserResource::make($user),
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

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

        if ($user) {
            $user->loadMissing('mainSite');
        }

        Log::info('API me requested', ['user_id' => optional($user)->id]);

        return AuthenticatedUserResource::make($user);
    }
}
