<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

class DgWorkSession extends Model
{
    use HasFactory;

    protected $table = 'dg_work_sessions';

    protected $fillable = [
        'user_id',
        'site_id',
        'session_date',
        'check_in',
        'check_out',
        'worked_minutes',
        'status',
        'source',
        'resolved_site_id',
        'overtime_minutes',
        'anomaly_flags',
        'approval_status',
        'approved_by',
        'approved_at',
        'extra_minutes',
        'extra_reason',
        'override_set_by',
        'override_reason',
    ];

    protected $casts = [
        'check_in'        => 'datetime',
        'check_out'       => 'datetime',
        'session_date'    => 'date',
        'worked_minutes'  => 'integer',
        'overtime_minutes'=> 'integer',
        'anomaly_flags'   => 'array', // importantissimo
    ];

    /* -------- Relations -------- */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function site()
    {
        return $this->belongsTo(DgSite::class, 'site_id');
    }

    // FASE 3
    public function resolvedSite()
    {
        return $this->belongsTo(DgSite::class, 'resolved_site_id');
    }

    public function punches()
    {
        return $this->hasMany(DgPunch::class, 'session_id');
    }

    /* -------- Helpers -------- */
    public function getWorkedHoursAttribute(): float
    {
        return round(($this->worked_minutes ?? 0) / 60, 2);
    }

    public function getOvertimeHoursAttribute(): float
    {
        return round(($this->overtime_minutes ?? 0) / 60, 2);
    }

    public function getDurationLabelAttribute(): string
    {
        $m = (int) ($this->worked_minutes ?? 0);
        return sprintf('%02dh %02dm', intdiv($m, 60), $m % 60);
    }

    /* -------- Scopes -------- */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForSite($query, int $siteId)
    {
        return $query->where('site_id', $siteId);
    }

    public function scopeBetweenDates($query, $from, $to)
    {
        return $query->whereBetween('session_date', [$from, $to]);
    }
}
