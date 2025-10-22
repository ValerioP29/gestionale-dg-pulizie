<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class DgSiteAssignment extends Model
{
    use HasFactory;

    protected $table = 'dg_site_assignments';

    protected $fillable = [
        'user_id',
        'site_id',
        'assigned_from',
        'assigned_to',
        'assigned_by',
        'notes',
    ];

    protected $casts = [
        'assigned_from' => 'date',
        'assigned_to'   => 'date',
    ];

    // Relazioni
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function site()
    {
        return $this->belongsTo(DgSite::class, 'site_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // Attributo derivato: attivo se non ha fine oppure fine >= oggi
    public function getIsActiveAttribute(): bool
    {
        $today = Carbon::today();
        if (!$this->assigned_from) {
            return false;
        }
        return $this->assigned_to === null || $this->assigned_to->gte($today);
    }
}
