<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DgSite extends Model
{
    use HasFactory;

    protected $table = 'dg_sites';

    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'radius_m',
        'active',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'active' => 'boolean',
    ];

    // Relazione: cantiere → assegnazioni
    public function assignments()
    {
        return $this->hasMany(DgSiteAssignment::class, 'site_id');
    }

    // Relazione: cantiere → utenti (tramite pivot)
    public function users()
    {
        return $this->belongsToMany(User::class, 'dg_site_assignments', 'site_id', 'user_id')
                    ->withPivot(['assigned_from', 'assigned_to', 'notes', 'assigned_by'])
                    ->withTimestamps();
    }

    public function punches()
    {
        return $this->hasMany(DgPunch::class, 'site_id');
    }

    public function workSessions()
    {
        return $this->hasMany(DgWorkSession::class, 'site_id');
    }

}
