<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'session_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function site()
    {
        return $this->belongsTo(DgSite::class, 'site_id');
    }

    /* -------- Helpers -------- */

    public function getWorkedHoursAttribute(): float
    {
        return round(($this->worked_minutes ?? 0) / 60, 2);
    }

    public function getDurationLabelAttribute(): string
    {
        $hours = floor($this->worked_minutes / 60);
        $minutes = $this->worked_minutes % 60;
        return sprintf('%02dh %02dm', $hours, $minutes);
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
