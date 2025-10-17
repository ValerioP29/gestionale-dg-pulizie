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
        'check_in',
        'check_out',
        'worked_minutes',
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function site()
    {
        return $this->belongsTo(DgSite::class, 'site_id');
    }
}
