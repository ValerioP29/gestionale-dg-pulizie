<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DgReportCache extends Model
{
    protected $table = 'dg_reports_cache';

    protected $fillable = [
        'user_id', 'site_id', 'period_start', 'period_end',
        'worked_hours', 'days_present', 'days_absent',
        'late_entries', 'early_exits', 'generated_at', 'is_final'
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'generated_at' => 'datetime',
        'is_final' => 'boolean',
    ];

    // RELAZIONI per Filament
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function site()
    {
        return $this->belongsTo(DgSite::class, 'site_id');
    }
}
