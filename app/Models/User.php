<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'active' => 'boolean',
        'password' => 'hashed',
    ];

    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    public function siteAssignments() {
    return $this->hasMany(DgSiteAssignment::class);
    }

    public function sites() {
        return $this->belongsToMany(DgSite::class, 'dg_site_assignments')
                    ->withPivot(['assigned_from', 'assigned_to', 'notes', 'assigned_by'])
                    ->withTimestamps();
    }

    public function punches()
    {
        return $this->hasMany(DgPunch::class);
    }

    public function workSessions()
    {
        return $this->hasMany(DgWorkSession::class);
    }

    public function payslips()
    {
        return $this->hasMany(\App\Models\DgPayslip::class);
    }

    public function consents()
    {
        return $this->hasMany(\App\Models\DgUserConsent::class);
    }

}
