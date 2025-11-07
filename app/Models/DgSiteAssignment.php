<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Traits\LogsActivity;

class DgSiteAssignment extends Model
{
    use HasFactory, LogsActivity;

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

    /* -------- Relazioni -------- */

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

    /* -------- Scope -------- */

    public function scopeActiveAt($query, $date)
    {
        return $query
            ->whereDate('assigned_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('assigned_to')
                  ->orWhereDate('assigned_to', '>=', $date);
            });
    }

    /* -------- Attributo derivato -------- */

    public function getIsActiveAttribute(): bool
    {
        $today = Carbon::today();

        if (!$this->assigned_from) {
            return false;
        }

        return $this->assigned_from->lte($today)
            && ($this->assigned_to === null || $this->assigned_to->gte($today));
    }

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('Assegnazioni cantieri');
    }
}
