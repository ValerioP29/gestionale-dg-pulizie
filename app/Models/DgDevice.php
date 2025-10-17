<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DgDevice extends Model
{
    use HasFactory;

    protected $table = 'dg_devices';

    protected $fillable = [
        'user_id',
        'device_id',
        'platform',
        'registered_at',
        'last_sync_at',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
