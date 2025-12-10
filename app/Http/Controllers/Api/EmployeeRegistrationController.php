<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\AuthenticatedUserResource;
use App\Models\DgUserConsent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmployeeRegistrationController
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'codice_fiscale' => ['required', 'string', 'max:16', 'regex:/^[A-Z0-9]{16}$/i'],
            'matricola' => ['nullable', 'string', 'max:64'],
            'privacy_accepted' => ['required', 'accepted'],
            'location_consent' => ['nullable', 'boolean'],
        ]);

        if (!$data['privacy_accepted']) {
            throw ValidationException::withMessages([
                'privacy_accepted' => __('You must accept the privacy policy to proceed.'),
            ]);
        }

        $password = Str::random(16);

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'cf' => strtoupper($data['codice_fiscale']),
            'payroll_code' => $data['matricola'] ?? null,
            'password' => $password,
            'role' => 'employee',
            'can_login' => true,
            'active' => true,
        ]);

        DgUserConsent::create([
            'user_id' => $user->id,
            'type' => 'privacy',
            'accepted' => true,
            'accepted_at' => now(),
            'source' => 'registration',
        ]);

        if ($data['location_consent'] ?? false) {
            DgUserConsent::create([
                'user_id' => $user->id,
                'type' => 'localization',
                'accepted' => true,
                'accepted_at' => now(),
                'source' => 'registration',
            ]);
        }

        $user->loadMissing('mainSite');

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => AuthenticatedUserResource::make($user)->resolve(),
        ], 201);
    }
}
