<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DgPunch extends Model
{
    use HasFactory;

    protected $table = 'dg_punches';

    public $timestamps = false; // perché created_at lo gestiamo noi manualmente

    protected $fillable = [
        'uuid',
        'user_id',
        'site_id',
        'type',
        'latitude',
        'longitude',
        'accuracy_m',
        'device_id',
        'device_battery',
        'network_type',
        'created_at',
        'synced_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'synced_at' => 'datetime',
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
