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
        'job_title_id',

        // nuovi campi contratto
        'mon','tue','wed','thu','fri','sat','sun',
        'rules',
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
        'rules'             => 'array',
        'mon'=>'float','tue'=>'float','wed'=>'float','thu'=>'float',
        'fri'=>'float','sat'=>'float','sun'=>'float',
    ];

    /* -------------------------------
     | Password
     |-------------------------------- */
    public function setPasswordAttribute($value): void
    {
        if (!$value) return;

        $this->attributes['password'] = Hash::needsRehash($value)
            ? Hash::make($value)
            : $value;
    }

    /* -------------------------------
     | Nome completo
     |-------------------------------- */
    public function getFullNameAttribute(): string
    {
        $parts = array_filter([$this->first_name, $this->last_name]);
        $name = trim(implode(' ', $parts));
        return $name ?: ($this->name ?: ($this->email ?? 'Utente'));
    }

    /* -------------------------------
     | Filament access
     |-------------------------------- */
    public function canAccessPanel(Panel $panel): bool
    {
        if (!$this->can_login) return false;
        return in_array($this->role, ['admin','supervisor','viewer']);
    }

    public function getFilamentName(): string
    {
        return $this->full_name ?: 'Utente';
    }

    /* -------------------------------
     | Auto calcoli prima del save
     |-------------------------------- */
    protected static function booted(): void
    {
        static::saving(function ($user) {

            // aggiorna name
            $user->name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
            if ($user->name === '') $user->name = $user->email ?? 'Utente';

            // calcolo ore mese
            $settimana = ($user->mon + $user->tue + $user->wed + $user->thu + $user->fri + $user->sat + $user->sun);

            if ($settimana > 0) {
                $user->contract_hours_monthly = round($settimana * 4);
            }
            else if ($user->contractSchedule) {
                $user->contract_hours_monthly = $user->contractSchedule->contract_hours_monthly;
            }
            else {
                $user->contract_hours_monthly = null;
            }
        });
    }

    /* -------------------------------
     | Relazioni
     |-------------------------------- */
    public function mainSite()
    {
        return $this->belongsTo(DgSite::class, 'main_site_id');
    }

    public function contractSchedule()
    {
        return $this->belongsTo(DgContractSchedule::class, 'contract_schedule_id');
    }

    public function jobTitle()
    {
        return $this->belongsTo(\App\Models\DgJobTitle::class, 'job_title_id');
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

    /* -------------------------------
     | Scope
     |-------------------------------- */
    public function scopeEmployees($query)
    {
        return $query->where('role', 'employee');
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array(
            strtolower($this->role),
            array_map('strtolower', $roles)
        );
    }

    public function isRole(string $role): bool
    {
        return $this->hasAnyRole([$role]);
    }

}
