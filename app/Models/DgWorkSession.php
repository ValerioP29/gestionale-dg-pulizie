<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DgWorkSession extends Model
{
    use HasFactory;

    protected $table = 'dg_work_sessions';
    protected $touches = ['user'];
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
        'session_date'     => 'date',
        'check_in'         => 'datetime',
        'check_out'        => 'datetime',
        'approved_at'      => 'datetime',
        'worked_minutes'   => 'integer',
        'overtime_minutes' => 'integer',
        'anomaly_flags'    => 'array',
    ];

    /* -------- Relations -------- */
    public function user() { return $this->belongsTo(User::class); }
    public function site() { return $this->belongsTo(DgSite::class, 'site_id'); }
    public function resolvedSite() { return $this->belongsTo(DgSite::class, 'resolved_site_id'); }
    public function punches() { return $this->hasMany(DgPunch::class, 'session_id'); }

    /* -------- Computed -------- */
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

    // Questi due li stai usando nel resource come $record->has_anomalies / ->anomaly_summary
    public function getHasAnomaliesAttribute(): bool
    {
        $flags = $this->anomaly_flags ?? [];
        return is_array($flags) && !empty($flags);
    }

    public function getAnomalySummaryAttribute(): ?string
    {
        $flags = $this->anomaly_flags ?? [];
        if (!is_array($flags) || empty($flags)) return null;

        // compatto in una stringa “tipo:minuti” se presente
        $parts = [];
        foreach ($flags as $flag) {
            if (is_array($flag)) {
                $type = $flag['type'] ?? 'anomalia';
                $min  = isset($flag['minutes']) ? (int) $flag['minutes'] : null;
                $parts[] = $min !== null ? "{$type}: {$min}m" : $type;
            } else {
                $parts[] = (string) $flag;
            }
        }
        return implode(' | ', $parts);
    }

    public function markApproved(): void
    {
        $this->approval_status = 'approved';
        $this->approved_at = now();
        $this->approved_by = auth()->id();
        $this->anomaly_flags = []; // pulisce anomalie risolte
        $this->save();
    }

    public function markRejected(?string $reason = null): void
    {
        $this->approval_status = 'rejected';
        $this->approved_at = now();
        $this->approved_by = auth()->id();
        if ($reason) {
            $this->override_reason = $reason;
        }
        $this->save();
    }

}
