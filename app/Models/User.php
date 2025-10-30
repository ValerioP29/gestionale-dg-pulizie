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
        'main_site_id',
        'contract_schedule_id',
        'payroll_code',             
        'hired_at',                 
        'contract_end_at',          
        'contract_hours_monthly',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at'     => 'datetime',
        'can_login'         => 'boolean',
        'active'            => 'boolean',
        'hired_at'          => 'date',  
        'contract_end_at'   => 'date', 
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
        return trim($this->getFullNameAttribute()) !== ''
            ? $this->getFullNameAttribute()
            : ($this->email ?? 'Utente');
    }

    public function getUserName(): string
    {
        return $this->getFilamentName();
    }

    /* -------------------------------
     |  Lifecycle hooks
     |-------------------------------- */

    protected static function booted(): void
    {
        static::saving(function ($user) {
            $user->name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            if ($user->name === '') {
                $user->name = $user->email ?? 'Utente';
            }
        });
    }

    /* -------------------------------
     |  FASE 3 – Relazioni e scope
     |-------------------------------- */

    public function mainSite()
    {
        return $this->belongsTo(DgSite::class, 'main_site_id');
    }

    public function siteAssignments()
    {
        return $this->hasMany(DgSiteAssignment::class);
    }

    public function activeSite($date = null)
    {
        $date = $date ?? now();
        return $this->siteAssignments()
            ->whereDate('assigned_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('assigned_to')->orWhereDate('assigned_to', '>=', $date);
            })
            ->latest('assigned_from')
            ->first();
    }

    public function workSessions()
    {
        return $this->hasMany(DgWorkSession::class);
    }

    public function punches()
    {
        return $this->hasMany(DgPunch::class);
    }

    public function reports()
    {
        return $this->hasMany(DgReportCache::class, 'user_id');
    }

    public function payslips()
    {
        return $this->hasMany(DgPayslip::class, 'user_id');
    }

    /* -------- Scope -------- */

    public function scopeEmployees($query)
    {
        return $query->where('role', 'employee');
    }
}
