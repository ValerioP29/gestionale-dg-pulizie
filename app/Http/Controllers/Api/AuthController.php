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

        $guard = Auth::guard('web');

        if (! $guard->attempt($credentials, $request->boolean('remember'))) {
            Log::info('API login failed', ['email' => $credentials['email']]);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        Log::info('API login successful', ['user_id' => $guard->id()]);

        return AuthenticatedUserResource::make($request->user());
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('API logout', ['user_id' => optional($user)->id]);

        return response()->noContent();
    }

    public function me(Request $request)
    {
        $user = $request->user();

        Log::info('API me requested', ['user_id' => optional($user)->id]);

        return AuthenticatedUserResource::make($user);
    }
}
