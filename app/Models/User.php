<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'name',
        'email',
        'phone',
        'password',
        'role',
        'can_login',
        'last_login_at',
        'created_by',
        'active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'can_login' => 'boolean',
        'active' => 'boolean',
    ];

    /* -------------------------------
     |  Utility methods
     |-------------------------------- */

    // Hash automatico della password
    public function setPasswordAttribute($value): void
    {
        if (is_null($value) || $value === '') {
            return;
        }

        $this->attributes['password'] = Hash::needsRehash($value)
            ? Hash::make($value)
            : $value;
    }

   public function getFullNameAttribute(): string
    {
        $parts = array_filter([$this->first_name, $this->last_name]);
        $name = trim(implode(' ', $parts));

        return $name !== '' ? $name : ($this->name ?: ($this->email ?? 'Utente'));
    }


    /* -------------------------------
     |  Role helpers
     |-------------------------------- */

    public function isRole(string $role): bool
    {
        return strtolower($this->role) === strtolower($role);
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array(strtolower($this->role), array_map('strtolower', $roles));
    }

    /* -------------------------------
     |  Filament panel access
     |-------------------------------- */

    public function canAccessPanel(Panel $panel): bool
    {
        if (!$this->can_login) {
            return false;
        }

        // Ruoli ammessi al gestionale
        return $this->hasAnyRole(['admin', 'supervisor', 'viewer']);
    }
    
    public function getFilamentName(): string
    {
        // Fallback sicuro e garantito: mai null, mai vuoto
        return trim($this->getFullNameAttribute()) !== ''
            ? $this->getFullNameAttribute()
            : ($this->email ?? 'Utente');
    }

    public function getUserName(): string
    {
        // Filament 3 fallback method — evita TypeError
        return $this->getFilamentName();
    }


    protected static function booted(): void
    {
        static::saving(function ($user) {
            $user->name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            if ($user->name === '') {
                $user->name = $user->email ?? 'Utente';
            }
        });
    }
}