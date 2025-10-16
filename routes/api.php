<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/sanctum/csrf-cookie', fn() => response()->noContent());

Route::post('/login', function (Request $r) {
    $cred = $r->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (!auth()->attempt($cred)) {
        return response()->json(['message' => 'Invalid credentials'], 422);
    }

    $r->session()->regenerate();

    return response()->json(['user' => auth()->user()]);
});

Route::post('/logout', function (Request $r) {
    auth()->guard('web')->logout();
    $r->session()->invalidate();
    $r->session()->regenerateToken();
    return response()->noContent();
});

Route::get('/me', fn() => auth()->user())->middleware('auth:web');
