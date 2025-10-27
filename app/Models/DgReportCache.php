<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DgReportCache extends Model
{
    protected $table = 'dg_reports_cache';

    protected $fillable = [
        'user_id',
        'site_id',
        'period_start',
        'period_end',
        'worked_hours',
        'days_present',
        'days_absent',
        'late_entries',
        'early_exits',
        'generated_at',
        'is_final',
        // FASE 3
        'resolved_site_id',
        'overtime_minutes',
        'anomaly_flags',
    ];

    protected $casts = [
        'period_start'     => 'date',
        'period_end'       => 'date',
        'worked_hours'     => 'float',
        'days_present'     => 'integer',
        'days_absent'      => 'integer',
        'late_entries'     => 'integer',
        'early_exits'      => 'integer',
        'generated_at'     => 'datetime',
        'is_final'         => 'boolean',
        // FASE 3
        'overtime_minutes' => 'integer',
        'anomaly_flags'    => 'array',
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

    // FASE 3: collegamento al cantiere “risolto”
    public function resolvedSite()
    {
        return $this->belongsTo(DgSite::class, 'resolved_site_id');
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

    public function scopeBetween($query, $from, $to)
    {
        return $query
            ->where('period_start', '>=', $from)
            ->where('period_end', '<=', $to);
    }

    public function scopeFinalOnly($query)
    {
        return $query->where('is_final', true);
    }

    /* -------- Helpers -------- */

    public function getOvertimeHoursAttribute(): float
    {
        return round(($this->overtime_minutes ?? 0) / 60, 2);
    }
}
